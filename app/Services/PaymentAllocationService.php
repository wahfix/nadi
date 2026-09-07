<?php

namespace App\Services;

use App\Models\Installment;
use App\Models\Loan;
use App\Models\Payment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PaymentAllocationService
{
    public function __construct(
        private readonly SequentialNumberService $sequentialNumberService,
        private readonly AuditLogService $auditLogService,
        private readonly LoanService $loanService,
    ) {}

    /**
     * Strict hierarchical allocation. First the loan-level penalty (if any),
     * then interest and principal absorbed installment-by-installment in
     * chronological due order. Pure function — never mutates state.
     *
     * @return array{penalty: int, interest: int, principal: int, unallocated: int}
     */
    public function allocate(Loan $loan, int $amount): array
    {
        if ($amount < 1) {
            throw new InvalidArgumentException('Nominal pembayaran harus lebih dari nol.');
        }

        // 1. Denda (penalty) dialokasikan PERTAMA terhadap saldo denda pinjaman.
        $penalty = min($amount, max(0, $loan->outstanding_penalty));
        $remaining = $amount - $penalty;

        // 2 dan 3. Bunga sebelum pokok, diserap per angsuran sesuai urutan jatuh tempo.
        $interestSum = 0;
        $principalSum = 0;

        $installments = $loan->installments()
            ->orderBy('installment_number')
            ->get();

        foreach ($installments as $installment) {
            if ($installment->remaining_amount <= 0) {
                continue;
            }

            $takeInterest = min($remaining, max(0, $installment->interest_due - $installment->interest_paid));
            $takePrincipal = min($remaining - $takeInterest, max(0, $installment->principal_due - $installment->principal_paid));

            $interestSum += $takeInterest;
            $principalSum += $takePrincipal;
            $remaining -= $takeInterest + $takePrincipal;

            if ($remaining === 0) {
                break;
            }
        }

        return [
            'penalty' => $penalty,
            'interest' => $interestSum,
            'principal' => $principalSum,
            'unallocated' => $remaining,
        ];
    }

    /**
     * Record a cashier payment against a loan, applying the strict allocation
     * hierarchy and locking the real components in the immutable payment row.
     */
    public function recordPayment(
        Loan $loan,
        int $amount,
        string $paymentMethod,
        string $paymentDate,
        ?string $referenceNumber = null,
        ?string $notes = null,
        ?int $receivedBy = null,
    ): Payment {
        return DB::transaction(function () use ($loan, $amount, $paymentMethod, $paymentDate, $referenceNumber, $notes, $receivedBy) {
            $actorId = $receivedBy ?? (int) Auth::id();

            if ($loan->outstanding_total < 1) {
                throw new InvalidArgumentException('Pinjaman ini tidak memiliki sisa tagihan yang harus dibayar.');
            }

            $allocation = $this->allocate($loan, $amount);

            if ($allocation['unallocated'] > 0) {
                throw new InvalidArgumentException(
                    'Nominal pembayaran melebihi sisa kewajiban pinjaman sebesar Rp '
                    .number_format($loan->outstanding_total, 0, ',', '.').'.'
                );
            }

            $firstInstallmentId = $this->applyToInstallmentsChronologically(
                $loan,
                $allocation['interest'],
                $allocation['principal'],
            );

            $loan->update([
                'outstanding_principal' => max(0, $loan->outstanding_principal - $allocation['principal']),
                'outstanding_interest' => max(0, $loan->outstanding_interest - $allocation['interest']),
                'outstanding_penalty' => max(0, $loan->outstanding_penalty - $allocation['penalty']),
                'outstanding_total' => max(0, $loan->outstanding_principal - $allocation['principal'])
                    + max(0, $loan->outstanding_interest - $allocation['interest'])
                    + max(0, $loan->outstanding_penalty - $allocation['penalty']),
            ]);

            $payment = Payment::create([
                'payment_number' => $this->sequentialNumberService->generatePaymentNumber(),
                'loan_id' => $loan->id,
                'customer_id' => $loan->customer_id,
                'installment_id' => $firstInstallmentId,
                'payment_date' => $paymentDate,
                'amount' => $amount,
                'principal_component' => $allocation['principal'],
                'interest_component' => $allocation['interest'],
                'penalty_component' => $allocation['penalty'],
                'payment_method' => $paymentMethod,
                'reference_number' => $referenceNumber,
                'received_by' => $actorId,
                'notes' => $notes,
            ]);

            $this->auditLogService->log(
                AuditLogService::PAYMENT_CREATED,
                $payment,
                null,
                null,
                $this->snapshot($payment),
                $actorId,
            );

            // Pelunasan otomatis manakala seluruh kewajiban terbayar.
            if ($loan->fresh()->isPaidOff()) {
                $this->loanService->completeLoan($loan->fresh(), $actorId);
            }

            return $payment->refresh()->load(['loan', 'customer', 'receivedBy', 'installment']);
        });
    }

    /**
     * Absorb the interest and principal components into unpaid installments in
     * chronological order. Returns the id of the first affected installment.
     */
    private function applyToInstallmentsChronologically(Loan $loan, int $interestAmount, int $principalAmount): ?int
    {
        if ($interestAmount + $principalAmount === 0) {
            return null;
        }

        $installments = $loan->installments()
            ->orderBy('installment_number')
            ->get();

        $firstAffected = null;

        foreach ($installments as $installment) {
            if ($installment->remaining_amount <= 0) {
                continue;
            }

            $paidInterest = min($interestAmount, max(0, $installment->interest_due - $installment->interest_paid));
            $paidPrincipal = min($principalAmount, max(0, $installment->principal_due - $installment->principal_paid));

            if ($paidInterest + $paidPrincipal === 0) {
                continue;
            }

            $installment->interest_paid = max(0, $installment->interest_paid + $paidInterest);
            $installment->principal_paid = max(0, $installment->principal_paid + $paidPrincipal);
            $installment->total_paid = $installment->penalty_paid + $installment->interest_paid + $installment->principal_paid;
            $newRemaining = max(0, $installment->total_due - $installment->total_paid);
            $installment->remaining_amount = $newRemaining;
            $installment->status = $this->statusFor($installment);
            $installment->paid_at = $newRemaining === 0 ? now() : $installment->paid_at;
            $installment->save();

            $firstAffected ??= $installment->id;

            $interestAmount -= $paidInterest;
            $principalAmount -= $paidPrincipal;

            if ($interestAmount + $principalAmount === 0) {
                break;
            }
        }

        return $firstAffected;
    }

    private function statusFor(Installment $installment): string
    {
        if ($installment->remaining_amount === 0) {
            return Installment::STATUS_PAID;
        }

        if ($installment->total_paid > 0) {
            return Installment::STATUS_PARTIALLY_PAID;
        }

        return CarbonImmutable::parse($installment->due_date)->lt(CarbonImmutable::today())
            ? Installment::STATUS_OVERDUE
            : Installment::STATUS_PENDING;
    }

    /**
     * @return array<string, int|string|null>
     */
    private function snapshot(Payment $payment): array
    {
        return [
            'payment_number' => $payment->payment_number,
            'loan_id' => $payment->loan_id,
            'customer_id' => $payment->customer_id,
            'installment_id' => $payment->installment_id,
            'amount' => $payment->amount,
            'principal_component' => $payment->principal_component,
            'interest_component' => $payment->interest_component,
            'penalty_component' => $payment->penalty_component,
            'payment_method' => $payment->payment_method,
            'reference_number' => $payment->reference_number,
            'payment_date' => CarbonImmutable::parse($payment->payment_date)->toDateString(),
            'notes' => $payment->notes,
        ];
    }
}

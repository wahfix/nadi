<?php

namespace App\Services;

use App\Models\Installment;
use App\Models\Loan;
use App\Models\Payment;
use App\Models\PaymentReversal;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PaymentReversalService
{
    public function __construct(
        private readonly LoanService $loanService,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * Reverse an immutable payment: the original row is never touched, the
     * affected balances are restored symmetrically and a full audit trail is
     * written to the payment_reversals and audit_logs tables.
     */
    public function reverse(
        Payment $payment,
        string $reason,
        ?int $userId = null,
    ): PaymentReversal {
        return DB::transaction(function () use ($payment, $reason, $userId) {
            $actorId = $userId ?? (int) Auth::id();

            if ($payment->isReversed()) {
                throw new InvalidArgumentException('Pembayaran ini sudah dibalikkan sebelumnya.');
            }

            $loan = $payment->loan;
            $beforeSnapshot = $this->snapshotLoan($loan);

            // 1. Restore the loan-level outstanding balances.
            $loan->update([
                'outstanding_principal' => $loan->outstanding_principal + $payment->principal_component,
                'outstanding_interest' => $loan->outstanding_interest + $payment->interest_component,
                'outstanding_penalty' => $loan->outstanding_penalty + $payment->penalty_component,
                'outstanding_total' => $loan->outstanding_principal + $payment->principal_component
                    + $loan->outstanding_interest + $payment->interest_component
                    + $loan->outstanding_penalty + $payment->penalty_component,
            ]);

            // 2. Restore installment paid amounts symmetrically (chronological order).
            $this->restoreInstallments($loan, $payment);

            // 3. A COMPLETED loan that no longer is paid off returns to ACTIVE.
            if ($loan->status === Loan::STATUS_COMPLETED && ! $loan->fresh()->isPaidOff()) {
                $this->loanService->reopenCompletedLoan($loan->fresh(), $actorId);
            }

            // 4. Persist the reversal record (immutability — original payment untouched).
            $reversal = PaymentReversal::create([
                'payment_id' => $payment->id,
                'reason' => trim($reason),
                'reversed_by' => $actorId,
                'reversed_at' => now(),
                'created_at' => now(),
            ]);

            // 5. Audit trail for the reversal event.
            $this->auditLogService->log(
                AuditLogService::PAYMENT_REVERSED,
                $payment,
                null,
                [
                    'reason' => $reversal->reason,
                    'reversed_by' => $reversal->reversed_by,
                    'reversed_at' => CarbonImmutable::parse($reversal->reversed_at)->toDateTimeString(),
                    'amount' => $payment->amount,
                ],
                $beforeSnapshot,
                $actorId,
            );

            return $reversal->refresh()->load('payment', 'reversedBy');
        });
    }

    /**
     * Subtract the payment components back off the installments in the same
     * chronological order they were applied, never driving a value below zero.
     */
    private function restoreInstallments(Loan $loan, Payment $payment): void
    {
        $installments = $loan->installments()
            ->orderBy('installment_number')
            ->get();

        $penalty = $payment->penalty_component;
        $interest = $payment->interest_component;
        $principal = $payment->principal_component;

        foreach ($installments as $installment) {
            if ($installment->total_paid <= 0) {
                continue;
            }

            $takePenalty = min($penalty, $installment->penalty_paid);
            $takeInterest = min($interest, $installment->interest_paid);
            $takePrincipal = min($principal, $installment->principal_paid);

            if ($takePenalty + $takeInterest + $takePrincipal === 0) {
                continue;
            }

            $installment->penalty_paid = max(0, $installment->penalty_paid - $takePenalty);
            $installment->interest_paid = max(0, $installment->interest_paid - $takeInterest);
            $installment->principal_paid = max(0, $installment->principal_paid - $takePrincipal);
            $installment->total_paid = $installment->penalty_paid + $installment->interest_paid + $installment->principal_paid;
            $newRemaining = max(0, $installment->total_due - $installment->total_paid);
            $installment->remaining_amount = $newRemaining;
            $installment->status = $this->statusFor($installment);
            $installment->paid_at = $newRemaining === 0 ? $installment->paid_at : null;
            $installment->save();

            $penalty -= $takePenalty;
            $interest -= $takeInterest;
            $principal -= $takePrincipal;

            if ($penalty + $interest + $principal === 0) {
                break;
            }
        }
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
    private function snapshotLoan(Loan $loan): array
    {
        return [
            'loan_number' => $loan->loan_number,
            'outstanding_principal' => $loan->outstanding_principal,
            'outstanding_interest' => $loan->outstanding_interest,
            'outstanding_penalty' => $loan->outstanding_penalty,
            'outstanding_total' => $loan->outstanding_total,
            'status' => $loan->status,
        ];
    }
}

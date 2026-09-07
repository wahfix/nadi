<?php

namespace App\Services;

use App\Models\Installment;
use App\Models\Loan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LoanService
{
    public const LOAN_UPDATED = 'LOAN_UPDATED';

    public const LOAN_COMPLETED = 'LOAN_COMPLETED';

    public const LOAN_CANCELLED = 'LOAN_CANCELLED';

    public function __construct(
        private readonly LoanCalculationService $loanCalculationService,
        private readonly LoanStatusService $loanStatusService,
        private readonly InstallmentScheduleService $installmentScheduleService,
        private readonly SequentialNumberService $sequentialNumberService,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * Create a new loan application in DRAFT state.
     *
     * @param  array<string, mixed>  $data
     */
    public function createDraft(array $data, ?int $userId = null): Loan
    {
        return DB::transaction(function () use ($data, $userId) {
            $principal = (int) $data['principal_amount'];
            $rateBps = (int) $data['interest_rate_bps'];
            $method = (string) $data['interest_method'];
            $tenor = (int) $data['tenor'];
            $frequency = (string) ($data['installment_frequency'] ?? Loan::FREQUENCY_MONTHLY);

            $calculation = $this->loanCalculationService->calculate($principal, $rateBps, $method, $tenor);

            $loan = Loan::create([
                'loan_number' => $this->sequentialNumberService->generateLoanNumber(),
                'customer_id' => (int) $data['customer_id'],
                'principal_amount' => $principal,
                'interest_rate' => $rateBps,
                'interest_method' => $method,
                'tenor' => $tenor,
                'installment_frequency' => $frequency,
                'disbursement_date' => $data['disbursement_date'] ?? null,
                'first_due_date' => $data['first_due_date'],
                'maturity_date' => $this->installmentScheduleService
                    ->dueDateFor($data['first_due_date'], $frequency, $tenor),
                'total_interest' => $calculation['total_interest'],
                'total_payable' => $calculation['total_payable'],
                'installment_amount' => $calculation['installment_amount'],
                'outstanding_principal' => $principal,
                'outstanding_interest' => $calculation['total_interest'],
                'outstanding_penalty' => 0,
                'outstanding_total' => $calculation['total_payable'],
                'status' => Loan::STATUS_DRAFT,
                'created_by' => $userId ?? Auth::id(),
            ]);

            $this->auditLogService->log(
                AuditLogService::LOAN_CREATED,
                $loan,
                null,
                null,
                $this->snapshot($loan),
                $userId,
            );

            return $loan;
        });
    }

    /**
     * Update a DRAFT loan application, recalculating the locked financial values.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateDraft(Loan $loan, array $data, ?int $userId = null): Loan
    {
        return DB::transaction(function () use ($loan, $data, $userId) {
            if ($loan->status !== Loan::STATUS_DRAFT) {
                throw new InvalidArgumentException('Hanya pengajuan berstatus DRAFT yang dapat diubah.');
            }

            $oldSnapshot = $this->snapshot($loan);

            $principal = (int) $data['principal_amount'];
            $rateBps = (int) $data['interest_rate_bps'];
            $method = (string) $data['interest_method'];
            $tenor = (int) $data['tenor'];
            $frequency = (string) ($data['installment_frequency'] ?? $loan->installment_frequency);

            $calculation = $this->loanCalculationService->calculate($principal, $rateBps, $method, $tenor);

            $loan->update([
                'customer_id' => (int) $data['customer_id'],
                'principal_amount' => $principal,
                'interest_rate' => $rateBps,
                'interest_method' => $method,
                'tenor' => $tenor,
                'installment_frequency' => $frequency,
                'disbursement_date' => $data['disbursement_date'] ?? null,
                'first_due_date' => $data['first_due_date'],
                'maturity_date' => $this->installmentScheduleService
                    ->dueDateFor($data['first_due_date'], $frequency, $tenor),
                'total_interest' => $calculation['total_interest'],
                'total_payable' => $calculation['total_payable'],
                'installment_amount' => $calculation['installment_amount'],
                'outstanding_principal' => $principal,
                'outstanding_interest' => $calculation['total_interest'],
                'outstanding_penalty' => 0,
                'outstanding_total' => $calculation['total_payable'],
            ]);

            $this->auditLogService->log(
                self::LOAN_UPDATED,
                $loan,
                null,
                $oldSnapshot,
                $this->snapshot($loan),
                $userId,
            );

            return $loan->refresh();
        });
    }

    /**
     * Navigate a loan from DRAFT to SUBMITTED.
     */
    public function submitLoan(Loan $loan, ?int $userId = null, ?string $reason = null): Loan
    {
        return $this->transition($loan, Loan::STATUS_SUBMITTED, $userId, $reason, AuditLogService::LOAN_SUBMITTED);
    }

    /**
     * Navigate a loan from SUBMITTED to UNDER_REVIEW.
     */
    public function startReview(Loan $loan, ?int $userId = null): Loan
    {
        return $this->transition($loan, Loan::STATUS_UNDER_REVIEW, $userId, 'Pengajuan diambil untuk proses review.');
    }

    /**
     * Approve a loan: UNDER_REVIEW → APPROVED.
     */
    public function approveLoan(Loan $loan, ?int $userId = null, ?string $reason = null): Loan
    {
        return DB::transaction(function () use ($loan, $userId, $reason) {
            $this->transition($loan, Loan::STATUS_APPROVED, $userId, $reason, AuditLogService::LOAN_APPROVED);

            $loan->update([
                'approved_by' => $userId ?? Auth::id(),
                'approved_at' => now(),
            ]);

            return $loan->refresh();
        });
    }

    /**
     * Reject a loan: UNDER_REVIEW → REJECTED.
     */
    public function rejectLoan(Loan $loan, ?int $userId = null, ?string $reason = null): Loan
    {
        return $this->transition($loan, Loan::STATUS_REJECTED, $userId, $reason, AuditLogService::LOAN_REJECTED);
    }

    /**
     * Mark an approved loan as ready for disbursement: APPROVED → READY_FOR_DISBURSEMENT.
     */
    public function prepareDisbursement(Loan $loan, ?int $userId = null): Loan
    {
        return $this->transition($loan, Loan::STATUS_READY_FOR_DISBURSEMENT, $userId, 'Pinjaman disiapkan untuk pencairan.');
    }

    /**
     * Disburse a loan: READY_FOR_DISBURSEMENT → ACTIVE and generate installments.
     */
    public function disburseLoan(Loan $loan, ?int $userId = null): Loan
    {
        return DB::transaction(function () use ($loan, $userId) {
            $this->transition($loan, Loan::STATUS_ACTIVE, $userId, 'Pinjaman dicairkan.', AuditLogService::LOAN_DISBURSED);

            $loan->update([
                'disbursement_date' => $loan->disbursement_date ?? now()->toDateString(),
                'disbursed_at' => now(),
            ]);

            $this->installmentScheduleService->generateFor($loan);

            return $loan->refresh()->load('installments');
        });
    }

    /**
     * Cancel a non-finalized loan.
     */
    public function cancelLoan(Loan $loan, ?int $userId = null, ?string $reason = null): Loan
    {
        return $this->transition($loan, Loan::STATUS_CANCELLED, $userId, $reason, self::LOAN_CANCELLED);
    }

    /**
     * Complete a fully paid-off loan: ACTIVE/OVERDUE → COMPLETED.
     */
    public function completeLoan(Loan $loan, ?int $userId = null): Loan
    {
        return DB::transaction(function () use ($loan, $userId) {
            if (! in_array($loan->status, [Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE], true)) {
                throw new InvalidArgumentException('Hanya pinjaman berstatus ACTIVE atau OVERDUE yang dapat diselesaikan.');
            }

            if (! $loan->isPaidOff()) {
                throw new InvalidArgumentException(
                    'Pinjaman belum dapat diselesaikan karena masih terdapat sisa tagihan sebesar Rp '
                    .number_format($loan->outstanding_total, 0, ',', '.').'.'
                );
            }

            $this->transition($loan, Loan::STATUS_COMPLETED, $userId, 'Seluruh kewajiban telah lunas.', self::LOAN_COMPLETED);

            $loan->update(['completed_at' => now()]);

            return $loan->refresh();
        });
    }

    /**
     * Detect overdue installments and promote the loan to OVERDUE when needed.
     */
    public function syncOverdue(Loan $loan, ?int $userId = null): Loan
    {
        return DB::transaction(function () use ($loan, $userId) {
            if (in_array($loan->status, [Loan::STATUS_ACTIVE], true)) {
                $this->installmentScheduleService->refreshOverdueStatuses($loan);

                $hasOverdue = $loan->installments()
                    ->where('status', Installment::STATUS_OVERDUE)
                    ->exists();

                if ($hasOverdue) {
                    $this->transition(
                        $loan,
                        Loan::STATUS_OVERDUE,
                        $userId ?? (int) Auth::id(),
                        'Teridentifikasi memiliki angsuran yang menunggak.',
                        AuditLogService::LOAN_STATUS_CHANGED,
                    );
                }
            }

            return $loan->refresh();
        });
    }

    /**
     * Run a validated state transition through the LoanStatusService.
     */
    private function transition(
        Loan $loan,
        string $toStatus,
        ?int $userId = null,
        ?string $reason = null,
        ?string $auditAction = null,
    ): Loan {
        return $this->loanStatusService->transition(
            $loan,
            $toStatus,
            $userId,
            $reason,
            $auditAction,
            $this->snapshot($loan),
        );
    }

    /**
     * @return array<string, int|string|null>
     */
    public function snapshot(Loan $loan): array
    {
        return [
            'loan_number' => $loan->loan_number,
            'customer_id' => $loan->customer_id,
            'principal_amount' => $loan->principal_amount,
            'interest_rate' => $loan->interest_rate,
            'interest_method' => $loan->interest_method,
            'tenor' => $loan->tenor,
            'installment_frequency' => $loan->installment_frequency,
            'total_interest' => $loan->total_interest,
            'total_payable' => $loan->total_payable,
            'installment_amount' => $loan->installment_amount,
            'outstanding_principal' => $loan->outstanding_principal,
            'outstanding_interest' => $loan->outstanding_interest,
            'outstanding_penalty' => $loan->outstanding_penalty,
            'outstanding_total' => $loan->outstanding_total,
            'status' => $loan->status,
        ];
    }
}

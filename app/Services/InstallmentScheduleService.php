<?php

namespace App\Services;

use App\Models\Installment;
use App\Models\Loan;
use Carbon\CarbonImmutable;

class InstallmentScheduleService
{
    public function __construct(
        private readonly LoanCalculationService $loanCalculationService,
    ) {}

    /**
     * Build (and persist) the full installment schedule for a disbursed loan.
     *
     * The per-period breakdown is re-derived deterministically from the loan's
     * locked contractual values (principal, rate, method, tenor) — never from
     * mutable runtime state — to honour financial immutability.
     */
    public function generateFor(Loan $loan): void
    {
        $breakdown = $this->loanCalculationService->calculate(
            $loan->principal_amount,
            $loan->interest_rate,
            $loan->interest_method,
            $loan->tenor,
        );

        $rows = [];

        foreach ($breakdown['schedule_rows'] as $index => $row) {
            $totalDue = $row['principal_due'] + $row['interest_due'];

            $rows[] = [
                'loan_id' => $loan->id,
                'installment_number' => $index + 1,
                'due_date' => $this->dueDateFor($loan->first_due_date, $loan->installment_frequency, $index + 1),
                'principal_due' => $row['principal_due'],
                'interest_due' => $row['interest_due'],
                'penalty_due' => 0,
                'total_due' => $totalDue,
                'principal_paid' => 0,
                'interest_paid' => 0,
                'penalty_paid' => 0,
                'total_paid' => 0,
                'remaining_amount' => $totalDue,
                'status' => Installment::STATUS_PENDING,
                'paid_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Installment::insert($rows);

        $this->refreshOverdueStatuses($loan);
    }

    /**
     * Compute the due date for a given installment number (1-based).
     */
    public function dueDateFor(mixed $firstDueDate, string $installmentFrequency, int $installmentNumber): CarbonImmutable
    {
        $date = CarbonImmutable::parse($firstDueDate);
        $offset = max($installmentNumber, 1) - 1;

        return $installmentFrequency === Loan::FREQUENCY_WEEKLY
            ? $date->addWeeks($offset)
            : $date->addMonths($offset);
    }

    /**
     * Flag installments whose due date already passed and remain unpaid as OVERDUE.
     */
    public function refreshOverdueStatuses(Loan $loan): void
    {
        Installment::query()
            ->where('loan_id', $loan->id)
            ->whereDate('due_date', '<', CarbonImmutable::today())
            ->where('remaining_amount', '>', 0)
            ->whereNotIn('status', [
                Installment::STATUS_PAID,
                Installment::STATUS_WAIVED,
                Installment::STATUS_OVERDUE,
            ])
            ->update(['status' => Installment::STATUS_OVERDUE]);
    }
}
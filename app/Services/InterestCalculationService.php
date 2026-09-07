<?php

namespace App\Services;

/**
 * Official interest service responsible for FLAT and REDUCING_BALANCE formulas.
 *
 * Delegates the precise integer/bcmath math to LoanCalculationService so no
 * financial formula ever lives inside a controller or Blade view.
 */
class InterestCalculationService
{
    public function __construct(
        private readonly LoanCalculationService $loanCalculationService,
    ) {}

    /**
     * @return array{
     *     total_interest: int,
     *     total_payable: int,
     *     installment_amount: int,
     *     schedule_rows: array<int, array{principal_due: int, interest_due: int}>,
     * }
     */
    public function calculate(int $principal, int $interestRateBps, string $interestMethod, int $tenor): array
    {
        return $this->loanCalculationService->calculate($principal, $interestRateBps, $interestMethod, $tenor);
    }
}
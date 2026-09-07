<?php

namespace App\Services;

use App\Models\Loan;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

class LoanCalculationService
{
    /**
     * Calculate the complete financial breakdown for a loan.
     *
     * All monetary values are INTEGER Rupiah (never float/double).
     * Interest is expressed in basis points (1% = 100 bps).
     *
     * @return array{
     *     total_interest: int,
     *     total_payable: int,
     *     installment_amount: int,
     *     schedule_rows: array<int, array{principal_due: int, interest_due: int}>,
     * }
     */
    public function calculate(int $principal, int $interestRateBps, string $interestMethod, int $tenor): array
    {
        return match ($interestMethod) {
            Loan::METHOD_FLAT => $this->calculateFlat($principal, $interestRateBps, $tenor),
            Loan::METHOD_REDUCING_BALANCE => $this->calculateReducingBalance($principal, $interestRateBps, $tenor),
            default => throw new InvalidArgumentException("Metode bunga tidak dikenal: {$interestMethod}."),
        };
    }

    /**
     * Flat interest: total_interest = principal × rate × tenor.
     *
     * @return array{
     *     total_interest: int,
     *     total_payable: int,
     *     installment_amount: int,
     *     schedule_rows: array<int, array{principal_due: int, interest_due: int}>,
     * }
     */
    public function calculateFlat(int $principal, int $interestRateBps, int $tenor): array
    {
        $this->assertTenor($tenor);

        $totalInterest = intdiv($principal * $interestRateBps * $tenor, 10000);
        $totalPayable = $principal + $totalInterest;
        $installment = intdiv($totalPayable, $tenor);

        $rows = [];
        $basePrincipal = intdiv($principal, $tenor);

        for ($index = 1; $index <= $tenor; $index++) {
            $isLast = $index === $tenor;
            $amount = $isLast ? $totalPayable - ($installment * ($tenor - 1)) : $installment;
            $principalDue = $isLast ? $principal - ($basePrincipal * ($tenor - 1)) : $basePrincipal;

            $rows[] = [
                'principal_due' => $principalDue,
                'interest_due' => $amount - $principalDue,
            ];
        }

        return [
            'total_interest' => $totalInterest,
            'total_payable' => $totalPayable,
            'installment_amount' => $installment,
            'schedule_rows' => $rows,
        ];
    }

    /**
     * Reducing-balance annuity: A = P × r × (1+r)^n / ((1+r)^n - 1).
     *
     * The annuity is derived using bcmath, while the amortization schedule is
     * computed with exact INTEGER arithmetic (rounding half-up per period) and
     * the final installment absorbs any rounding discrepancy.
     *
     * @return array{
     *     total_interest: int,
     *     total_payable: int,
     *     installment_amount: int,
     *     schedule_rows: array<int, array{principal_due: int, interest_due: int}>,
     * }
     */
    public function calculateReducingBalance(int $principal, int $interestRateBps, int $tenor): array
    {
        $this->assertTenor($tenor);

        if ($interestRateBps === 0) {
            return $this->calculateZeroRate($principal, $tenor);
        }

        $annuity = $this->annuityRoundedInt($principal, $interestRateBps, $tenor);

        $rows = [];
        $remaining = $principal;
        $totalInterest = 0;

        for ($index = 1; $index <= $tenor; $index++) {
            $isLast = $index === $tenor;

            // interest_k = remaining_principal_{k-1} × r  (integer half-up rounding)
            $interest = $this->roundHalfUp($remaining * $interestRateBps, 10000);

            if ($isLast) {
                $principalDue = $remaining;
                $remaining = 0;
            } else {
                $principalDue = $annuity - $interest;

                if ($principalDue < 0) {
                    $principalDue = 0;
                    $interest = $annuity;
                }

                $remaining -= $principalDue;
            }

            $totalInterest += $interest;
            $rows[] = [
                'principal_due' => $principalDue,
                'interest_due' => $interest,
            ];
        }

        return [
            'total_interest' => $totalInterest,
            'total_payable' => $principal + $totalInterest,
            'installment_amount' => $annuity,
            'schedule_rows' => $rows,
        ];
    }

    /**
     * Compute the maturity date based on the first due date, frequency and tenor.
     */
    public function maturityDate(mixed $firstDueDate, string $installmentFrequency, int $tenor): CarbonImmutable
    {
        $this->assertTenor($tenor);

        $date = CarbonImmutable::parse($firstDueDate);
        $offset = $tenor - 1;

        return $installmentFrequency === Loan::FREQUENCY_WEEKLY
            ? $date->addWeeks($offset)
            : $date->addMonths($offset);
    }

    /**
     * Integer half-up division, e.g. 250001 / 10000 -> 26.
     */
    private function roundHalfUp(int $dividend, int $divisor): int
    {
        return intdiv($dividend + intdiv($divisor, 2), $divisor);
    }

    /**
     * Annuity rounded to an integer using bcmath (no binary floating point).
     */
    private function annuityRoundedInt(int $principal, int $interestRateBps, int $tenor): int
    {
        $scale = 8;
        $rate = bcdiv((string) $interestRateBps, '10000', $scale);
        $discountFactor = bcadd('1', $rate, $scale);
        $power = bcpow($discountFactor, (string) $tenor, $scale);
        $numerator = bcmul(bcmul($rate, (string) $principal, $scale), $power, $scale);
        $annuity = bcdiv($numerator, bcsub($power, '1', $scale), $scale);

        [$whole, $fraction] = array_pad(explode('.', (string) $annuity, 2), 2, '0');
        $roundUp = ($fraction[0] ?? '0') >= '5';

        return (int) $whole + ($roundUp ? 1 : 0);
    }

    /**
     * @return array{
     *     total_interest: int,
     *     total_payable: int,
     *     installment_amount: int,
     *     schedule_rows: array<int, array{principal_due: int, interest_due: int}>,
     * }
     */
    private function calculateZeroRate(int $principal, int $tenor): array
    {
        $base = intdiv($principal, $tenor);
        $rows = [];

        for ($index = 1; $index <= $tenor; $index++) {
            $isLast = $index === $tenor;
            $rows[] = [
                'principal_due' => $isLast ? $principal - ($base * ($tenor - 1)) : $base,
                'interest_due' => 0,
            ];
        }

        return [
            'total_interest' => 0,
            'total_payable' => $principal,
            'installment_amount' => $base,
            'schedule_rows' => $rows,
        ];
    }

    private function assertTenor(int $tenor): void
    {
        if ($tenor < 1) {
            throw new InvalidArgumentException('Tenor minimal 1 periode.');
        }
    }
}
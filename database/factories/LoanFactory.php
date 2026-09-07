<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Loan;
use App\Models\User;
use App\Services\LoanCalculationService;
use App\Services\SequentialNumberService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Loan>
 */
class LoanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $principal = 10_000_000;
        $rateBps = 200;
        $tenor = 10;
        $frequency = Loan::FREQUENCY_MONTHLY;
        $firstDueDate = now()->addMonth();

        $calculation = app(LoanCalculationService::class)->calculate(
            $principal,
            $rateBps,
            Loan::METHOD_FLAT,
            $tenor,
        );

        return [
            'loan_number' => app(SequentialNumberService::class)->generateLoanNumber(),
            'customer_id' => Customer::factory(),
            'principal_amount' => $principal,
            'interest_rate' => $rateBps,
            'interest_method' => Loan::METHOD_FLAT,
            'tenor' => $tenor,
            'installment_frequency' => $frequency,
            'disbursement_date' => null,
            'first_due_date' => $firstDueDate,
            'maturity_date' => app(LoanCalculationService::class)
                ->maturityDate($firstDueDate, $frequency, $tenor),
            'total_interest' => $calculation['total_interest'],
            'total_payable' => $calculation['total_payable'],
            'installment_amount' => $calculation['installment_amount'],
            'outstanding_principal' => $principal,
            'outstanding_interest' => $calculation['total_interest'],
            'outstanding_penalty' => 0,
            'outstanding_total' => $calculation['total_payable'],
            'status' => Loan::STATUS_DRAFT,
            'created_by' => User::factory(),
        ];
    }
}

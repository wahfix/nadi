<?php

namespace Database\Seeders;

use App\Models\Installment;
use App\Models\Loan;
use App\Models\User;
use App\Services\LoanService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LoanSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed realistic synthetic loan applications across the state machine
     * (no real borrower data).
     */
    public function run(LoanService $loanService): void
    {
        $lo = User::where('email', 'lo@example.test')->firstOrFail();
        $admin = User::where('email', 'admin@example.test')->firstOrFail();
        $userId = $lo->id;

        $customers = \App\Models\Customer::where('status', \App\Models\Customer::STATUS_ACTIVE)
            ->orderBy('id')
            ->limit(20)
            ->get();

        if ($customers->isEmpty()) {
            return;
        }

        $defaults = fn (int $customerId, int $principal, int $tenor, string $firstDue, ?string $disbursement = null): array => [
            'customer_id' => $customerId,
            'principal_amount' => $principal,
            'interest_rate_bps' => 200,
            'interest_method' => Loan::METHOD_FLAT,
            'tenor' => $tenor,
            'installment_frequency' => Loan::FREQUENCY_MONTHLY,
            'disbursement_date' => $disbursement,
            'first_due_date' => $firstDue,
        ];

        $pipeline = [
            ['status' => 'DRAFT', 'principal' => 8_000_000, 'tenor' => 12, 'first_due' => now()->addMonth()->toDateString()],
            ['status' => 'DRAFT', 'principal' => 5_000_000, 'tenor' => 6, 'first_due' => now()->addMonth()->toDateString()],
            ['status' => 'SUBMITTED', 'principal' => 15_000_000, 'tenor' => 12, 'first_due' => now()->addMonth()->toDateString()],
            ['status' => 'UNDER_REVIEW', 'principal' => 20_000_000, 'tenor' => 24, 'first_due' => now()->addMonth()->toDateString()],
            ['status' => 'APPROVED', 'principal' => 10_000_000, 'tenor' => 12, 'first_due' => now()->addMonth()->toDateString()],
            ['status' => 'READY_FOR_DISBURSEMENT', 'principal' => 12_000_000, 'tenor' => 18, 'first_due' => now()->addMonth()->toDateString()],
        ];

        foreach ($pipeline as $index => $row) {
            $loan = $loanService->createDraft($defaults(
                $customers[$index % $customers->count()]->id,
                $row['principal'],
                $row['tenor'],
                $row['first_due'],
            ), $userId);

            if ($row['status'] === 'SUBMITTED') {
                $loan = $loanService->submitLoan($loan, $userId);
            } elseif ($row['status'] === 'UNDER_REVIEW') {
                $loan = $loanService->submitLoan($loan, $userId);
                $loan = $loanService->startReview($loan, $userId);
            } elseif ($row['status'] === 'APPROVED') {
                $loan = $loanService->submitLoan($loan, $userId);
                $loan = $loanService->startReview($loan, $userId);
                $loan = $loanService->approveLoan($loan, $admin->id);
            } elseif ($row['status'] === 'READY_FOR_DISBURSEMENT') {
                $loan = $loanService->submitLoan($loan, $userId);
                $loan = $loanService->startReview($loan, $userId);
                $loan = $loanService->approveLoan($loan, $admin->id);
                $loan = $loanService->prepareDisbursement($loan, $admin->id);
            }
        }

        // Aktif — first due masih di masa depan, belum ada angsuran jatuh tempo.
        $activeSpecs = [
            ['principal' => 25_000_000, 'tenor' => 24, 'first_due' => now()->addMonth()->toDateString()],
            ['principal' => 7_500_000, 'tenor' => 6, 'first_due' => now()->addMonth()->toDateString()],
        ];

        foreach ($activeSpecs as $index => $row) {
            $loan = $loanService->createDraft($defaults(
                $customers[(20 + $index) % $customers->count()]->id,
                $row['principal'],
                $row['tenor'],
                $row['first_due'],
                now()->toDateString(),
            ), $userId);

            $loan = $loanService->submitLoan($loan, $userId);
            $loan = $loanService->startReview($loan, $userId);
            $loan = $loanService->approveLoan($loan, $admin->id);
            $loan = $loanService->prepareDisbursement($loan, $admin->id);
            $loanService->disburseLoan($loan, $admin->id);
        }

        // Menunggak — first due sudah lewat dan belum dibayar.
        $overdue = $loanService->createDraft($defaults(
            $customers[3]->id,
            9_000_000,
            12,
            now()->subMonths(2)->toDateString(),
            now()->subMonths(3)->toDateString(),
        ), $userId);

        $overdue = $loanService->submitLoan($overdue, $userId);
        $overdue = $loanService->startReview($overdue, $userId);
        $overdue = $loanService->approveLoan($overdue, $admin->id);
        $overdue = $loanService->prepareDisbursement($overdue, $admin->id);
        $overdue = $loanService->disburseLoan($overdue, $admin->id);
        $loanService->syncOverdue($overdue, $userId);

        // Lunas — seluruh kewajiban diselesaikan pada masa lampau.
        $completed = $loanService->createDraft($defaults(
            $customers[5]->id,
            6_000_000,
            6,
            now()->subMonths(6)->toDateString(),
            now()->subMonths(6)->toDateString(),
        ), $userId);

        $completed = $loanService->submitLoan($completed, $userId);
        $completed = $loanService->startReview($completed, $userId);
        $completed = $loanService->approveLoan($completed, $admin->id);
        $completed = $loanService->prepareDisbursement($completed, $admin->id);
        $completed = $loanService->disburseLoan($completed, $admin->id);

        DB::transaction(function () use ($completed) {
            $completed->installments()->update([
                'principal_paid' => DB::raw('principal_due'),
                'interest_paid' => DB::raw('interest_due'),
                'penalty_paid' => DB::raw('penalty_due'),
                'total_paid' => DB::raw('total_due'),
                'remaining_amount' => 0,
                'status' => Installment::STATUS_PAID,
                'paid_at' => now()->subMonths(1),
            ]);

            $completed->update([
                'outstanding_principal' => 0,
                'outstanding_interest' => 0,
                'outstanding_penalty' => 0,
                'outstanding_total' => 0,
            ]);
        });

        $loanService->completeLoan($completed, $admin->id);
    }
}
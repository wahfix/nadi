<?php

namespace App\Services;

use App\Models\CollectionActivity;
use App\Models\Installment;
use App\Models\Loan;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CollectionService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * Loans that are currently being collected (ACTIVE or OVERDUE with a
     * remaining balance). DRAFT/UNDER_REVIEW/APPROVED/COMPLETED etc. are not
     * collectable.
     *
     * @return Builder<Loan>
     */
    public function collectableLoansQuery(): Builder
    {
        return Loan::query()
            ->with(['customer'])
            ->whereIn('status', [Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE])
            ->where('outstanding_total', '>', 0)
            ->orderByDesc('outstanding_total');
    }

    /**
     * Record a collection interaction with a debtor.
     *
     * @param  string  $contactMethod  One of CollectionActivity::METHOD_*
     * @param  string  $result  One of CollectionActivity::RESULT_*
     */
    public function recordActivity(
        Loan $loan,
        string $contactMethod,
        string $result,
        string $contactDate,
        ?string $promiseToPayDate = null,
        ?int $promiseToPayAmount = null,
        ?string $notes = null,
        ?int $collectorId = null,
    ): CollectionActivity {
        if (! in_array($loan->status, [Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE], true)) {
            throw new InvalidArgumentException('Pinjaman tidak dalam status penagihan (harus aktif atau menunggak).');
        }

        if ($loan->outstanding_total < 1) {
            throw new InvalidArgumentException('Pinjaman ini tidak memiliki sisa tagihan untuk ditagih.');
        }

        if ($promiseToPayDate !== null || $promiseToPayAmount !== null) {
            if ($result !== CollectionActivity::RESULT_PROMISE_TO_PAY) {
                throw new InvalidArgumentException('Data janji bayar hanya boleh diisi saat hasil penagihan adalah janji bayar.');
            }
        }

        if ($result === CollectionActivity::RESULT_PROMISE_TO_PAY) {
            if ($promiseToPayDate === null || $promiseToPayAmount === null) {
                throw new InvalidArgumentException('Hasil janji bayar wajib menyertakan tanggal dan nominal janji bayar.');
            }

            if ($promiseToPayAmount < 1) {
                throw new InvalidArgumentException('Nominal janji bayar minimal Rp 1.');
            }
        }

        $actorId = $collectorId ?? (int) Auth::id();
        $customerId = (int) $loan->customer_id;

        return DB::transaction(function () use ($loan, $customerId, $actorId, $contactMethod, $result, $contactDate, $promiseToPayDate, $promiseToPayAmount, $notes) {
            $activity = CollectionActivity::create([
                'loan_id' => $loan->id,
                'customer_id' => $customerId,
                'collector_id' => $actorId,
                'contact_date' => $contactDate,
                'contact_method' => $contactMethod,
                'result' => $result,
                'promise_to_pay_date' => $promiseToPayDate,
                'promise_to_pay_amount' => $promiseToPayAmount,
                'notes' => $notes,
            ]);

            $this->auditLogService->log(
                AuditLogService::COLLECTION_CREATED,
                $activity,
                null,
                null,
                $activity->only([
                    'loan_id',
                    'customer_id',
                    'collector_id',
                    'contact_date',
                    'contact_method',
                    'result',
                    'promise_to_pay_date',
                    'promise_to_pay_amount',
                    'notes',
                ]),
                $actorId,
            );

            return $activity;
        });
    }

    /**
     * Complete set of LC dashboard metrics.
     *
     * @return array<string, int|string>
     */
    public function dashboardStats(?User $collector = null): array
    {
        $now = CarbonImmutable::now();
        $today = $now->startOfDay();
        $weekEnd = $today->addDays(7);

        $activeLoanIds = Loan::query()
            ->whereIn('status', [Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE])
            ->where('outstanding_total', '>', 0);

        if ($collector !== null) {
            $activeLoanIds->whereIn('id', $this->collectorLoanIds($collector));
        }

        $loanIds = $activeLoanIds->pluck('id');

        $paidOrWaived = Installment::query()
            ->whereIn('installments.loan_id', $loanIds)
            ->where('installments.remaining_amount', '>', 0)
            ->whereNotIn('installments.status', [Installment::STATUS_PAID, Installment::STATUS_WAIVED]);

        $dueToday = (clone $paidOrWaived)
            ->whereDate('installments.due_date', $today->toDateString())
            ->count();

        $dueThisWeek = (clone $paidOrWaived)
            ->whereBetween('installments.due_date', [$today->toDateString(), $weekEnd->toDateString()])
            ->count();

        $overdue = (clone $paidOrWaived)
            ->where('installments.due_date', '<', $today->toDateString())
            ->count();

        $totalOutstanding = (int) Loan::query()
            ->whereIn('id', $loanIds)
            ->sum('outstanding_total');

        return [
            'total_assigned_customers' => Loan::query()
                ->whereIn('id', $loanIds)
                ->distinct('customer_id')
                ->count('customer_id'),
            'due_today' => $dueToday,
            'overdue' => $overdue,
            'due_this_week' => $dueThisWeek,
            'total_outstanding' => $totalOutstanding,
            'active_promises' => CollectionActivity::query()
                ->where('result', CollectionActivity::RESULT_PROMISE_TO_PAY)
                ->where('promise_to_pay_date', '>=', $today->toDateString())
                ->with(['loan.customer', 'collector'])
                ->orderBy('promise_to_pay_date')
                ->orderBy('promise_to_pay_amount', 'desc')
                ->limit(20)
                ->get(),
        ];
    }

    /**
     * IDs of the loans a collector handles (loans they have interacted with).
     *
     * @return array<int, int>
     */
    private function collectorLoanIds(User $collector): array
    {
        return $collector->collectionActivities()
            ->pluck('loan_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }
}

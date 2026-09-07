<?php

namespace App\Http\Controllers;

use App\Models\Installment;
use App\Models\Loan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class InstallmentController extends Controller
{
    /**
     * Display a paginated, searchable, filterable list of installments.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Installment::class);

        $installments = Installment::query()
            ->with(['loan.customer'])
            ->whereHas('loan', fn ($loan) => $loan->whereIn('status', [Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE]))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($inner) use ($search) {
                    $inner->where('installment_number', 'like', "%{$search}%")
                        ->orWhereRelation('loan', 'loan_number', 'like', "%{$search}%")
                        ->orWhereRelation('loan.customer', 'full_name', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->orderByRaw("CASE status WHEN 'OVERDUE' THEN 0 WHEN 'PARTIALLY_PAID' THEN 1 ELSE 2 END")
            ->orderBy('due_date')
            ->paginate(15)
            ->withQueryString();

        return view('modules.installments.index', [
            'installments' => $installments,
            'summary' => $this->summary(),
            'statusOptions' => [
                Installment::STATUS_OVERDUE,
                Installment::STATUS_PENDING,
                Installment::STATUS_PARTIALLY_PAID,
                Installment::STATUS_PAID,
                Installment::STATUS_WAIVED,
            ],
            'statusLabels' => [
                Installment::STATUS_PENDING => 'Belum Jatuh Tempo',
                Installment::STATUS_PARTIALLY_PAID => 'Dibayar Sebagian',
                Installment::STATUS_PAID => 'Lunas',
                Installment::STATUS_OVERDUE => 'Menunggak',
                Installment::STATUS_WAIVED => 'Dihapuskan',
            ],
        ]);
    }

    /**
     * Display a single installment with its loan and payment history.
     */
    public function show(Installment $installment): View
    {
        Gate::authorize('view', $installment);

        $installment->load(['loan.customer', 'payments.receivedBy', 'payments.reversal']);

        return view('modules.installments.show', [
            'installment' => $installment,
            'statusLabels' => [
                Installment::STATUS_PENDING => 'Belum Jatuh Tempo',
                Installment::STATUS_PARTIALLY_PAID => 'Dibayar Sebagian',
                Installment::STATUS_PAID => 'Lunas',
                Installment::STATUS_OVERDUE => 'Menunggak',
                Installment::STATUS_WAIVED => 'Dihapuskan',
            ],
        ]);
    }

    /**
     * Aggregate dashboard cards for the installment listing.
     *
     * @return array{overdue_count: int, overdue_total: int, due_this_month_count: int, due_this_month_total: int, active_remaining: int}
     */
    private function summary(): array
    {
        $activeLoanScope = fn ($loan) => $loan->whereIn('status', [Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE]);

        $overdueQuery = fn () => Installment::query()
            ->where('status', Installment::STATUS_OVERDUE)
            ->where('remaining_amount', '>', 0)
            ->whereHas('loan', $activeLoanScope);

        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        $dueThisMonthQuery = fn () => Installment::query()
            ->whereIn('status', [Installment::STATUS_PENDING, Installment::STATUS_PARTIALLY_PAID])
            ->whereBetween('due_date', [$monthStart, $monthEnd])
            ->whereHas('loan', $activeLoanScope);

        return [
            'overdue_count' => $overdueQuery()->count(),
            'overdue_total' => (int) $overdueQuery()->sum('remaining_amount'),
            'due_this_month_count' => $dueThisMonthQuery()->count(),
            'due_this_month_total' => (int) $dueThisMonthQuery()->sum('remaining_amount'),
            'active_remaining' => (int) Installment::query()
                ->whereIn('status', [Installment::STATUS_PENDING, Installment::STATUS_PARTIALLY_PAID, Installment::STATUS_OVERDUE])
                ->where('remaining_amount', '>', 0)
                ->whereHas('loan', $activeLoanScope)
                ->sum('remaining_amount'),
        ];
    }
}

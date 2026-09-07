<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Collateral;
use App\Models\CollateralRelease;
use App\Models\CollectionActivity;
use App\Models\Customer;
use App\Models\IdentityVerification;
use App\Models\Installment;
use App\Models\Loan;
use App\Models\Payment;
use App\Models\Role;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private const array ACTIVE_LOAN_STATUSES = [Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE];

    private const array OPEN_INSTALLMENT_STATUSES = [
        Installment::STATUS_PENDING,
        Installment::STATUS_PARTIALLY_PAID,
        Installment::STATUS_OVERDUE,
    ];

    /**
     * Render a role-specific dashboard widget set.
     */
    public function index(): View
    {
        $user = auth()->user();
        $roleName = $user->roles->first()?->name;

        [$partial, $dashboard] = match ($roleName) {
            Role::ADMIN => ['admin', $this->adminWidgets()],
            Role::LO => ['lo', $this->loWidgets($user)],
            Role::LC => ['lc', $this->lcWidgets()],
            Role::CASHIER => ['cashier', $this->cashierWidgets()],
            Role::COLLATERAL_OFFICER => ['collateral', $this->collateralWidgets()],
            Role::IDENTITY_VERIFIER => ['verifier', $this->verifierWidgets()],
            Role::AUDITOR => ['auditor', $this->auditorWidgets()],
            default => [null, []],
        };

        return view('dashboard', [
            'dashboard' => $dashboard,
            'dashboardPartial' => $partial,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function adminWidgets(): array
    {
        $dueToday = Installment::query()
            ->whereIn('status', self::OPEN_INSTALLMENT_STATUSES)
            ->where('remaining_amount', '>', 0)
            ->whereDate('due_date', today())
            ->whereHas('loan', fn ($loan) => $loan->whereIn('status', self::ACTIVE_LOAN_STATUSES));

        $weekStart = CarbonImmutable::today();
        $weekEnd = CarbonImmutable::today()->addDays(7)->endOfDay();

        $dueThisWeek = Installment::query()
            ->whereIn('status', self::OPEN_INSTALLMENT_STATUSES)
            ->where('remaining_amount', '>', 0)
            ->whereBetween('due_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->whereHas('loan', fn ($loan) => $loan->whereIn('status', self::ACTIVE_LOAN_STATUSES));

        $portfolioLoanScope = fn ($loan) => $loan->whereIn('status', self::ACTIVE_LOAN_STATUSES);
        $portfolioQuery = fn () => Loan::query()->where($portfolioLoanScope);

        $promises = CollectionActivity::query()
            ->where('result', CollectionActivity::RESULT_PROMISE_TO_PAY)
            ->whereDate('promise_to_pay_date', '>=', today());

        return [
            'portfolio' => [
                'total_customers' => Customer::count(),
                'active_loans' => $portfolioQuery()->count(),
                'outstanding_principal' => (int) $portfolioQuery()->sum('outstanding_principal'),
                'outstanding_interest' => (int) $portfolioQuery()->sum('outstanding_interest'),
                'portfolio_total' => (int) $portfolioQuery()->sum('principal_amount'),
            ],
            'collection' => [
                'due_today_count' => $dueToday->count(),
                'due_today_amount' => (int) $dueToday->sum('remaining_amount'),
                'overdue_count' => Installment::query()
                    ->where('status', Installment::STATUS_OVERDUE)
                    ->where('remaining_amount', '>', 0)
                    ->whereHas('loan', $portfolioLoanScope)
                    ->count(),
                'overdue_amount' => (int) Installment::query()
                    ->where('status', Installment::STATUS_OVERDUE)
                    ->where('remaining_amount', '>', 0)
                    ->whereHas('loan', $portfolioLoanScope)
                    ->sum('remaining_amount'),
                'due_this_week_count' => $dueThisWeek->count(),
                'promise_count' => $promises->count(),
                'promise_amount' => (int) $promises->sum('promise_to_pay_amount'),
            ],
            'collateral' => [
                'total' => Collateral::count(),
                'in_custody' => Collateral::where('custody_status', Collateral::STATUS_IN_CUSTODY)->count(),
                'ready' => Collateral::where('custody_status', Collateral::STATUS_READY_FOR_RELEASE)->count(),
                'released' => Collateral::where('custody_status', Collateral::STATUS_RELEASED)->count(),
            ],
            'recent' => $this->recentActivity(),
        ];
    }

    /**
     * @return array{total_customers: int, total_loans: int, pipeline: array<string, int>}
     */
    private function loWidgets(User $user): array
    {
        $pipeline = fn () => Loan::query()->where('created_by', $user->id);

        return [
            'total_customers' => Customer::count(),
            'total_loans' => $pipeline()->count(),
            'pipeline' => [
                Loan::STATUS_DRAFT => $pipeline()->where('status', Loan::STATUS_DRAFT)->count(),
                Loan::STATUS_SUBMITTED => $pipeline()->where('status', Loan::STATUS_SUBMITTED)->count(),
                Loan::STATUS_UNDER_REVIEW => $pipeline()->where('status', Loan::STATUS_UNDER_REVIEW)->count(),
                Loan::STATUS_APPROVED => $pipeline()->where('status', Loan::STATUS_APPROVED)->count(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function lcWidgets(): array
    {
        $installmentBase = Installment::query()
            ->with(['loan.customer'])
            ->whereHas('loan', fn ($loan) => $loan->whereIn('status', self::ACTIVE_LOAN_STATUSES));

        $today = CarbonImmutable::today();
        $weekEnd = CarbonImmutable::today()->addDays(7)->endOfDay();

        $dueToday = (clone $installmentBase)
            ->whereIn('status', self::OPEN_INSTALLMENT_STATUSES)
            ->where('remaining_amount', '>', 0)
            ->whereDate('due_date', $today)
            ->get();

        $dueThisWeek = (clone $installmentBase)
            ->whereIn('status', self::OPEN_INSTALLMENT_STATUSES)
            ->where('remaining_amount', '>', 0)
            ->whereBetween('due_date', [$today->toDateString(), $weekEnd->toDateString()])
            ->get();

        $overdue = (clone $installmentBase)
            ->where('status', Installment::STATUS_OVERDUE)
            ->where('remaining_amount', '>', 0)
            ->orderBy('due_date')
            ->limit(10)
            ->get();

        $promises = CollectionActivity::query()
            ->with(['loan.customer'])
            ->where('result', CollectionActivity::RESULT_PROMISE_TO_PAY)
            ->whereDate('promise_to_pay_date', '>=', today())
            ->orderBy('promise_to_pay_date')
            ->limit(10)
            ->get();

        $loanScope = fn () => Loan::query()->whereIn('status', self::ACTIVE_LOAN_STATUSES);

        return [
            'assigned_borrowers' => Loan::query()
                ->whereIn('status', self::ACTIVE_LOAN_STATUSES)
                ->distinct()
                ->count('customer_id'),
            'total_outstanding' => (int) $loanScope()->sum('outstanding_total'),
            'due_today' => $dueToday,
            'due_this_week' => $dueThisWeek,
            'overdue' => $overdue,
            'overdue_total' => (int) (clone $installmentBase)
                ->where('status', Installment::STATUS_OVERDUE)
                ->sum('remaining_amount'),
            'promises' => $promises,
            'promise_total' => (int) $promises->sum('promise_to_pay_amount'),
        ];
    }

    /**
     * @return array{today_count: int, today_amount: int, recent: Collection<int, Payment>}
     */
    private function cashierWidgets(): array
    {
        $todayPayments = Payment::query()
            ->whereDate('payment_date', today())
            ->doesntHave('reversal');

        return [
            'today_count' => $todayPayments->count(),
            'today_amount' => (int) $todayPayments->sum('amount'),
            'recent' => Payment::query()
                ->with(['customer', 'loan'])
                ->latest('created_at')
                ->limit(8)
                ->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function collateralWidgets(): array
    {
        $inCustody = Collateral::where('custody_status', Collateral::STATUS_IN_CUSTODY);

        return [
            'in_custody_count' => $inCustody->count(),
            'in_custody_value' => (int) $inCustody->sum('estimated_value'),
            'ready_count' => Collateral::where('custody_status', Collateral::STATUS_READY_FOR_RELEASE)->count(),
            'ready_value' => (int) Collateral::where('custody_status', Collateral::STATUS_READY_FOR_RELEASE)->sum('estimated_value'),
            'recent' => CollateralRelease::query()
                ->with(['customer', 'collateral'])
                ->latest('created_at')
                ->limit(5)
                ->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function verifierWidgets(): array
    {
        return [
            'pending' => IdentityVerification::where('result', IdentityVerification::RESULT_REQUIRES_REVIEW)->count(),
            'verified' => IdentityVerification::where('result', IdentityVerification::RESULT_VERIFIED)->count(),
            'failed' => IdentityVerification::where('result', IdentityVerification::RESULT_FAILED)->count(),
            'recent' => IdentityVerification::query()
                ->with(['customer'])
                ->latest('created_at')
                ->limit(5)
                ->get(),
        ];
    }

    /**
     * @return array{today_events: int, trend: array<string, int>, recent: Collection<int, AuditLog>}
     */
    private function auditorWidgets(): array
    {
        $trend = [];
        for ($daysAgo = 6; $daysAgo >= 0; $daysAgo--) {
            $day = CarbonImmutable::today()->subDays($daysAgo);
            $trend[$day->format('d/m')] = AuditLog::whereDate('created_at', $day)->count();
        }

        return [
            'today_events' => AuditLog::whereDate('created_at', today())->count(),
            'trend' => $trend,
            'recent' => AuditLog::query()
                ->with('user')
                ->latest('created_at')
                ->limit(10)
                ->get(),
        ];
    }

    /**
     * Build the most recent cross-module activity feed.
     *
     * @return Collection<int, array{icon: string, title: string, description: string, url: string, time: CarbonImmutable}>
     */
    private function recentActivity(): Collection
    {
        $items = collect();

        Payment::query()
            ->with(['customer'])
            ->latest('created_at')
            ->limit(4)
            ->get()
            ->each(function (Payment $payment) use ($items) {
                $items->push([
                    'icon' => 'banknotes',
                    'title' => 'Pembayaran '.$payment->payment_number,
                    'description' => $payment->customer?->full_name.' • '.format_rupiah($payment->amount),
                    'url' => route('payments.show', $payment),
                    'time' => CarbonImmutable::parse($payment->created_at),
                ]);
            });

        Loan::query()
            ->with(['customer'])
            ->whereNotNull('approved_at')
            ->latest('approved_at')
            ->limit(3)
            ->get()
            ->each(function (Loan $loan) use ($items) {
                $items->push([
                    'icon' => 'check-badge',
                    'title' => 'Pinjaman disetujui '.$loan->loan_number,
                    'description' => $loan->customer?->full_name.' • '.format_rupiah($loan->principal_amount),
                    'url' => route('loans.show', $loan),
                    'time' => CarbonImmutable::parse($loan->approved_at),
                ]);
            });

        Collateral::query()
            ->with(['customer'])
            ->latest('created_at')
            ->limit(3)
            ->get()
            ->each(function (Collateral $collateral) use ($items) {
                $items->push([
                    'icon' => 'wallet',
                    'title' => 'Jaminan diterima '.$collateral->collateral_code,
                    'description' => $collateral->customer?->full_name,
                    'url' => route('collaterals.show', $collateral),
                    'time' => CarbonImmutable::parse($collateral->created_at),
                ]);
            });

        IdentityVerification::query()
            ->with(['customer'])
            ->latest('created_at')
            ->limit(3)
            ->get()
            ->each(function (IdentityVerification $verification) use ($items) {
                $items->push([
                    'icon' => 'shield-check',
                    'title' => 'Verifikasi identitas '.$verification->result,
                    'description' => $verification->customer?->full_name,
                    'url' => route('verifications.show', $verification),
                    'time' => CarbonImmutable::parse($verification->created_at),
                ]);
            });

        CollateralRelease::query()
            ->with(['customer'])
            ->latest('created_at')
            ->limit(3)
            ->get()
            ->each(function (CollateralRelease $release) use ($items) {
                $items->push([
                    'icon' => 'arrow-right',
                    'title' => 'Jaminan diserahkan '.$release->release_number,
                    'description' => $release->customer?->full_name.' • '.$release->released_to_name,
                    'url' => route('releases.show', $release),
                    'time' => CarbonImmutable::parse($release->created_at),
                ]);
            });

        return $items->sortByDesc('time')->take(8)->values();
    }
}

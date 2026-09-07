<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCollectionRequest;
use App\Models\CollectionActivity;
use App\Models\Installment;
use App\Models\Loan;
use App\Models\User;
use App\Services\CollectionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use InvalidArgumentException;

class CollectionController extends Controller
{
    public function __construct(
        private readonly CollectionService $collectionService,
    ) {}

    /**
     * LC operational dashboard: metrics, promise-to-pay, dunning list and the
     * latest recorded interactions.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', CollectionActivity::class);

        $user = $request->user();
        $isCollector = $user instanceof User && $user->hasPermission('collections.create');

        $stats = $this->collectionService->dashboardStats(
            $isCollector ? $user : null,
        );

        $unpaidInstallments = Installment::query()
            ->with(['loan.customer'])
            ->where('remaining_amount', '>', 0)
            ->whereNotIn('status', [Installment::STATUS_PAID, Installment::STATUS_WAIVED])
            ->whereIn('loan_id', $this->collectableLoanIds())
            ->orderBy('due_date')
            ->limit(50)
            ->get();

        $activities = CollectionActivity::query()
            ->with(['loan.customer', 'collector'])
            ->when(
                $isCollector,
                fn (Builder $query) => $query->where('collector_id', $user->getAuthIdentifier()),
            )
            ->orderByDesc('contact_date')
            ->paginate(15)
            ->withQueryString();

        return view('modules.collections.index', [
            'stats' => $stats,
            'unpaidInstallments' => $unpaidInstallments,
            'activities' => $activities,
            'methodOptions' => self::methodOptions(),
            'resultOptions' => self::resultOptions(),
            'canRecord' => $user->can('create', CollectionActivity::class),
        ]);
    }

    /**
     * Show the form to record a collection interaction for a payable loan.
     */
    public function create(Request $request): View
    {
        Gate::authorize('create', CollectionActivity::class);

        $loans = $this->collectionService->collectableLoansQuery()->get();

        $preselected = $request->integer('loan') > 0
            ? $loans->firstWhere('id', $request->integer('loan'))
            : null;

        return view('modules.collections.create', [
            'loans' => $loans,
            'preselectedLoan' => $preselected,
            'methodOptions' => self::methodOptions(),
            'resultOptions' => self::resultOptions(),
        ]);
    }

    /**
     * Persist a new collection interaction.
     */
    public function store(StoreCollectionRequest $request): RedirectResponse
    {
        $loan = Loan::findOrFail((int) $request->validated('loan_id'));

        try {
            $activity = $this->collectionService->recordActivity(
                $loan,
                (string) $request->validated('contact_method'),
                (string) $request->validated('result'),
                (string) $request->validated('contact_date'),
                $request->validated('promise_to_pay_date'),
                $request->has('promise_to_pay_amount')
                    ? (int) $request->validated('promise_to_pay_amount')
                    : null,
                $request->validated('notes'),
            );
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->route('collections.create', ['loan' => $loan->id])
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('loans.show', $loan)
            ->with('success', 'Aktivitas penagihan berhasil dicatat untuk '.$loan->customer->full_name.'.');
    }

    /**
     * IDs of loans that are currently being collected.
     *
     * @return array<int, int>
     */
    private function collectableLoanIds(): array
    {
        return $this->collectionService->collectableLoansQuery()
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }

    /**
     * Human-readable labels for contact methods.
     *
     * @return array<string, string>
     */
    private static function methodOptions(): array
    {
        return [
            CollectionActivity::METHOD_PHONE => 'Telepon',
            CollectionActivity::METHOD_WHATSAPP => 'WhatsApp',
            CollectionActivity::METHOD_IN_PERSON => 'Kunjungan Langsung',
            CollectionActivity::METHOD_OTHER => 'Lainnya',
        ];
    }

    /**
     * Human-readable labels for collection results.
     *
     * @return array<string, string>
     */
    private static function resultOptions(): array
    {
        return [
            CollectionActivity::RESULT_PAID => 'Sudah Membayar',
            CollectionActivity::RESULT_PROMISE_TO_PAY => 'Janji Bayar',
            CollectionActivity::RESULT_NO_RESPONSE => 'Tidak Ada Respons',
            CollectionActivity::RESULT_CONTACT_FAILED => 'Kontak Gagal',
            CollectionActivity::RESULT_DISPUTED => 'Sanggahan Nasabah',
            CollectionActivity::RESULT_OTHER => 'Lainnya',
        ];
    }
}

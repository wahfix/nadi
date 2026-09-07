<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReceiveCollateralRequest;
use App\Http\Requests\UpdateCustodyRequest;
use App\Models\Collateral;
use App\Models\Loan;
use App\Services\CollateralService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use InvalidArgumentException;

class CollateralController extends Controller
{
    public function __construct(
        private readonly CollateralService $collateralService,
    ) {}

    /**
     * Display a paginated, searchable list of collaterals.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Collateral::class);

        $collaterals = Collateral::query()
            ->with(['loan', 'customer', 'receivedBy'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($inner) use ($search) {
                    $inner->where('collateral_code', 'like', "%{$search}%")
                        ->orWhere('identification_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($q) => $q->where('full_name', 'like', "%{$search}%"))
                        ->orWhereHas('loan', fn ($q) => $q->where('loan_number', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('custody_status'), fn ($query) => $query->where('custody_status', $request->string('custody_status')->toString()))
            ->when($request->filled('collateral_type'), fn ($query) => $query->where('collateral_type', $request->string('collateral_type')->toString()))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('modules.collaterals.index', [
            'collaterals' => $collaterals,
            'statusOptions' => [
                Collateral::STATUS_PENDING,
                Collateral::STATUS_RECEIVED,
                Collateral::STATUS_IN_CUSTODY,
                Collateral::STATUS_READY_FOR_RELEASE,
                Collateral::STATUS_RELEASED,
                Collateral::STATUS_DISPUTED,
            ],
            'typeOptions' => [
                Collateral::TYPE_DOCUMENT => 'Dokumen',
                Collateral::TYPE_VEHICLE => 'Kendaraan',
                Collateral::TYPE_ELECTRONIC => 'Elektronik',
                Collateral::TYPE_OTHER => 'Lainnya',
            ],
        ]);
    }

    /**
     * Show the form to receive a new collateral.
     */
    public function create(Request $request): View
    {
        Gate::authorize('receive', Collateral::class);

        $loans = Loan::query()
            ->with(['customer'])
            ->whereIn('status', [
                Loan::STATUS_ACTIVE,
                Loan::STATUS_OVERDUE,
                Loan::STATUS_APPROVED,
                Loan::STATUS_READY_FOR_DISBURSEMENT,
            ])
            ->orderByDesc('created_at')
            ->get();

        $preselected = $request->integer('loan') > 0
            ? $loans->firstWhere('id', $request->integer('loan'))
            : null;

        return view('modules.collaterals.create', [
            'loans' => $loans,
            'preselectedLoan' => $preselected,
            'typeOptions' => [
                Collateral::TYPE_DOCUMENT => 'Dokumen',
                Collateral::TYPE_VEHICLE => 'Kendaraan',
                Collateral::TYPE_ELECTRONIC => 'Elektronik',
                Collateral::TYPE_OTHER => 'Lainnya',
            ],
        ]);
    }

    /**
     * Persist a newly received collateral with audit trail.
     */
    public function store(ReceiveCollateralRequest $request): RedirectResponse
    {
        $loan = Loan::findOrFail((int) $request->validated('loan_id'));

        try {
            $collateral = $this->collateralService->receiveCollateral($loan, [
                ...$request->validated(),
                'received_by' => $request->user()->id,
            ]);
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->route('collaterals.create')
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('collaterals.show', $collateral)
            ->with('success', 'Jaminan '.$collateral->collateral_code.' berhasil diterima dan disimpan.');
    }

    /**
     * Display the collateral detail page.
     */
    public function show(Collateral $collateral): View
    {
        Gate::authorize('view', $collateral);

        $collateral->load(['loan', 'customer', 'receivedBy', 'releasedBy', 'release']);

        return view('modules.collaterals.show', [
            'collateral' => $collateral,
            'typeLabels' => [
                Collateral::TYPE_DOCUMENT => 'Dokumen',
                Collateral::TYPE_VEHICLE => 'Kendaraan',
                Collateral::TYPE_ELECTRONIC => 'Elektronik',
                Collateral::TYPE_OTHER => 'Lainnya',
            ],
            'statusLabels' => [
                Collateral::STATUS_PENDING => 'Menunggu',
                Collateral::STATUS_RECEIVED => 'Diterima',
                Collateral::STATUS_IN_CUSTODY => 'Disimpan',
                Collateral::STATUS_READY_FOR_RELEASE => 'Siap Diserahkan',
                Collateral::STATUS_RELEASED => 'Diserahkan',
                Collateral::STATUS_DISPUTED => 'Sengketa',
            ],
        ]);
    }

    /**
     * Update the custody status of a collateral.
     */
    public function updateCustody(UpdateCustodyRequest $request, Collateral $collateral): RedirectResponse
    {
        try {
            $this->collateralService->updateCustodyStatus($collateral, $request->validated('custody_status'));
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->route('collaterals.show', $collateral)
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('collaterals.show', $collateral)
            ->with('success', 'Status penyimpanan jaminan berhasil diperbarui.');
    }
}

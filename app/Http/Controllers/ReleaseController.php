<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExecuteReleaseRequest;
use App\Models\Collateral;
use App\Models\CollateralRelease;
use App\Models\IdentityVerification;
use App\Services\CollateralReleaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use InvalidArgumentException;

class ReleaseController extends Controller
{
    public function __construct(
        private readonly CollateralReleaseService $collateralReleaseService,
    ) {}

    /**
     * Display a paginated list of collateral releases.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', CollateralRelease::class);

        $releases = CollateralRelease::query()
            ->with(['collateral', 'loan', 'customer', 'releasedBy', 'identityVerification'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($inner) use ($search) {
                    $inner->where('release_number', 'like', "%{$search}%")
                        ->orWhere('released_to_name', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($q) => $q->where('full_name', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('modules.releases.index', [
            'releases' => $releases,
        ]);
    }

    /**
     * Show the release form with 8-condition checklist for a collateral.
     */
    public function create(Request $request): View
    {
        Gate::authorize('execute', CollateralRelease::class);

        $collaterals = Collateral::query()
            ->with(['loan.customer'])
            ->where('custody_status', Collateral::STATUS_READY_FOR_RELEASE)
            ->whereDoesntHave('release')
            ->orderByDesc('created_at')
            ->get();

        $preselected = $request->integer('collateral') > 0
            ? $collaterals->firstWhere('id', $request->integer('collateral'))
            : null;

        $checklist = null;
        $verifications = collect();

        if ($preselected) {
            $checklist = $this->collateralReleaseService->getReleaseChecklist($preselected);
            $verifications = IdentityVerification::query()
                ->where('customer_id', $preselected->customer_id)
                ->where('loan_id', $preselected->loan_id)
                ->where('result', IdentityVerification::RESULT_VERIFIED)
                ->orderByDesc('verification_timestamp')
                ->get();
        }

        return view('modules.releases.create', [
            'collaterals' => $collaterals,
            'preselectedCollateral' => $preselected,
            'checklist' => $checklist,
            'verifications' => $verifications,
        ]);
    }

    /**
     * Execute the collateral release with 8-condition server-side enforcement.
     */
    public function store(ExecuteReleaseRequest $request): RedirectResponse
    {
        $collateral = Collateral::findOrFail((int) $request->validated('collateral_id'));
        $verification = IdentityVerification::findOrFail((int) $request->validated('identity_verification_id'));

        try {
            $release = $this->collateralReleaseService->executeRelease($collateral, $verification, [
                ...$request->validated(),
                'released_by' => $request->user()->id,
            ]);
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->route('releases.create', ['collateral' => $collateral->id])
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('releases.show', $release)
            ->with('success', 'Penyerahan jaminan '.$release->release_number.' berhasil dilakukan.');
    }

    /**
     * Display the release detail page.
     */
    public function show(CollateralRelease $release): View
    {
        Gate::authorize('view', $release);

        $release->load(['collateral', 'loan', 'customer', 'releasedBy', 'witness', 'identityVerification']);

        return view('modules.releases.show', [
            'release' => $release,
        ]);
    }

    /**
     * Printable collateral release receipt (Berita Acara Serah Terima).
     */
    public function receipt(CollateralRelease $release): View
    {
        Gate::authorize('view', $release);

        $release->load(['collateral', 'loan', 'customer', 'releasedBy', 'witness', 'identityVerification']);

        return view('modules.releases.receipt', [
            'release' => $release,
        ]);
    }
}

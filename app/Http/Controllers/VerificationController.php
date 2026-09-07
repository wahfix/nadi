<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVerificationRequest;
use App\Models\IdentityVerification;
use App\Models\Loan;
use App\Services\CollateralReleaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use InvalidArgumentException;

class VerificationController extends Controller
{
    public function __construct(
        private readonly CollateralReleaseService $collateralReleaseService,
    ) {}

    /**
     * Display a paginated list of identity verifications.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', IdentityVerification::class);

        $verifications = IdentityVerification::query()
            ->with(['customer', 'loan', 'verifier'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($inner) use ($search) {
                    $inner->where('verified_name', 'like', "%{$search}%")
                        ->orWhere('verified_id_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($q) => $q->where('full_name', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('result'), fn ($query) => $query->where('result', $request->string('result')->toString()))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('modules.verifications.index', [
            'verifications' => $verifications,
            'resultOptions' => [
                IdentityVerification::RESULT_VERIFIED => 'Terverifikasi',
                IdentityVerification::RESULT_FAILED => 'Gagal',
                IdentityVerification::RESULT_REQUIRES_REVIEW => 'Perlu Review',
            ],
        ]);
    }

    /**
     * Show the form to create a new identity verification.
     */
    public function create(Request $request): View
    {
        Gate::authorize('create', IdentityVerification::class);

        $loans = Loan::query()
            ->with(['customer'])
            ->whereIn('status', [
                Loan::STATUS_ACTIVE,
                Loan::STATUS_OVERDUE,
                Loan::STATUS_COMPLETED,
            ])
            ->orderByDesc('created_at')
            ->get();

        $preselected = $request->integer('loan') > 0
            ? $loans->firstWhere('id', $request->integer('loan'))
            : null;

        return view('modules.verifications.create', [
            'loans' => $loans,
            'preselectedLoan' => $preselected,
            'methodOptions' => [
                IdentityVerification::METHOD_GOVERNMENT_ID => 'KTP-el / Paspor / SIM',
                IdentityVerification::METHOD_ACCOUNT_MATCH => 'Pencocokan Rekening Bank',
                IdentityVerification::METHOD_MANUAL_CHECK => 'Pemeriksaan Manual',
                IdentityVerification::METHOD_OTHER => 'Lainnya',
            ],
            'resultOptions' => [
                IdentityVerification::RESULT_VERIFIED => 'Terverifikasi',
                IdentityVerification::RESULT_FAILED => 'Gagal',
                IdentityVerification::RESULT_REQUIRES_REVIEW => 'Perlu Review',
            ],
        ]);
    }

    /**
     * Persist a new identity verification record.
     */
    public function store(StoreVerificationRequest $request): RedirectResponse
    {
        $loan = Loan::findOrFail((int) $request->validated('loan_id'));

        try {
            $verification = $this->collateralReleaseService->verifyIdentity($loan, [
                ...$request->validated(),
                'verifier_id' => $request->user()->id,
            ]);
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->route('verifications.create')
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('verifications.show', $verification)
            ->with('success', 'Verifikasi identitas untuk '.$verification->verified_name.' berhasil dicatat.');
    }

    /**
     * Display the verification detail page.
     */
    public function show(IdentityVerification $verification): View
    {
        Gate::authorize('view', $verification);

        $verification->load(['customer', 'loan', 'verifier']);

        return view('modules.verifications.show', [
            'verification' => $verification,
            'methodLabels' => [
                IdentityVerification::METHOD_GOVERNMENT_ID => 'Identitas Pemerintah',
                IdentityVerification::METHOD_ACCOUNT_MATCH => 'Pencocokan Akun',
                IdentityVerification::METHOD_MANUAL_CHECK => 'Pemeriksaan Manual',
                IdentityVerification::METHOD_OTHER => 'Lainnya',
            ],
            'resultLabels' => [
                IdentityVerification::RESULT_VERIFIED => 'Terverifikasi',
                IdentityVerification::RESULT_FAILED => 'Gagal',
                IdentityVerification::RESULT_REQUIRES_REVIEW => 'Perlu Review',
            ],
        ]);
    }

    /**
     * Print the identity verification result document.
     */
    public function printResult(IdentityVerification $verification): View
    {
        Gate::authorize('view', $verification);

        $verification->load(['customer', 'loan', 'verifier']);

        return view('modules.verifications.print-result', [
            'verification' => $verification,
        ]);
    }
}

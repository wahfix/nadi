<?php

namespace App\Http\Controllers;

use App\Http\Requests\RejectLoanRequest;
use App\Http\Requests\StoreLoanRequest;
use App\Http\Requests\UpdateLoanRequest;
use App\Models\Customer;
use App\Models\Installment;
use App\Models\Loan;
use App\Services\LoanCalculationService;
use App\Services\LoanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use InvalidArgumentException;

class LoanController extends Controller
{
    public function __construct(
        private readonly LoanService $loanService,
        private readonly LoanCalculationService $loanCalculationService,
    ) {}

    /**
     * Display a paginated, searchable, filterable list of loans.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Loan::class);

        $loans = Loan::query()
            ->with(['customer', 'creator', 'approver'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($inner) use ($search) {
                    $inner->where('loan_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($customer) use ($search) {
                            $customer->where('full_name', 'like', "%{$search}%")
                                ->orWhere('customer_code', 'like', "%{$search}%");
                        });
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('customer_id'), fn ($query) => $query->where('customer_id', (int) $request->integer('customer_id')))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('modules.loans.index', [
            'loans' => $loans,
            'statusOptions' => [
                Loan::STATUS_DRAFT,
                Loan::STATUS_SUBMITTED,
                Loan::STATUS_UNDER_REVIEW,
                Loan::STATUS_APPROVED,
                Loan::STATUS_REJECTED,
                Loan::STATUS_READY_FOR_DISBURSEMENT,
                Loan::STATUS_ACTIVE,
                Loan::STATUS_OVERDUE,
                Loan::STATUS_COMPLETED,
                Loan::STATUS_DEFAULTED,
                Loan::STATUS_CANCELLED,
            ],
            'customers' => Customer::query()
                ->selectRaw('id, full_name, customer_code')
                ->orderBy('full_name')
                ->get(),
        ]);
    }

    /**
     * Show the loan application form with a live calculation preview.
     */
    public function create(): View
    {
        Gate::authorize('create', Loan::class);

        return view('modules.loans.create', [
            'customers' => Customer::query()
                ->where('status', Customer::STATUS_ACTIVE)
                ->orderBy('full_name')
                ->get(),
        ]);
    }

    /**
     * Persist a new DRAFT loan application.
     */
    public function store(StoreLoanRequest $request): RedirectResponse
    {
        $loan = $this->loanService->createDraft($request->validated());

        return redirect()
            ->route('loans.show', $loan)
            ->with('success', 'Pengajuan pinjaman '.$loan->loan_number.' berhasil dibuat.');
    }

    /**
     * Display the loan detail page with its financial records.
     */
    public function show(Loan $loan): View
    {
        Gate::authorize('view', $loan);

        $loan->load([
            'customer',
            'creator',
            'approver',
            'installments',
            'payments',
            'collaterals',
            'collectionActivities',
            'statusHistories.user',
        ]);

        return view('modules.loans.show', [
            'loan' => $loan,
        ]);
    }

    /**
     * Show the form to edit a DRAFT loan application.
     */
    public function edit(Loan $loan): View
    {
        Gate::authorize('update', $loan);

        return view('modules.loans.edit', [
            'loan' => $loan,
            'customers' => Customer::query()
                ->where('status', Customer::STATUS_ACTIVE)
                ->orderBy('full_name')
                ->get(),
        ]);
    }

    /**
     * Update a DRAFT loan application.
     */
    public function update(UpdateLoanRequest $request, Loan $loan): RedirectResponse
    {
        Gate::authorize('update', $loan);

        try {
            $this->loanService->updateDraft($loan, $request->validated());
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->route('loans.show', $loan)
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('loans.show', $loan)
            ->with('success', 'Pengajuan pinjaman berhasil diperbarui.');
    }

    /**
     * JSON preview endpoint for the loan application form (server-side math only).
     */
    public function calculatePreview(Request $request): JsonResponse
    {
        Gate::authorize('create', Loan::class);

        $validated = $request->validate([
            'principal_amount' => ['required', 'integer', 'min:1'],
            'interest_rate' => ['required', 'numeric', 'min:0.01', 'max:100'],
            'interest_method' => ['required', 'in:FLAT,REDUCING_BALANCE'],
            'tenor' => ['required', 'integer', 'min:1', 'max:120'],
            'installment_frequency' => ['required', 'in:MONTHLY,WEEKLY'],
            'first_due_date' => ['required', 'date'],
        ], [
            'principal_amount.required' => 'Pokok pinjaman wajib diisi.',
            'principal_amount.integer' => 'Pokok pinjaman harus berupa angka.',
            'principal_amount.min' => 'Pokok pinjaman minimal Rp 1.',
            'interest_rate.required' => 'Suku bunga wajib diisi.',
            'interest_rate.numeric' => 'Suku bunga harus berupa angka.',
            'interest_rate.min' => 'Suku bunga minimal 0,01%.',
            'interest_rate.max' => 'Suku bunga maksimal 100%.',
            'interest_method.required' => 'Metode bunga wajib dipilih.',
            'interest_method.in' => 'Metode bunga tidak valid.',
            'tenor.required' => 'Tenor wajib diisi.',
            'tenor.integer' => 'Tenor harus berupa angka bulat.',
            'tenor.min' => 'Tenor minimal 1 periode.',
            'tenor.max' => 'Tenor maksimal 120 periode.',
            'installment_frequency.required' => 'Frekuensi angsuran wajib dipilih.',
            'installment_frequency.in' => 'Frekuensi angsuran tidak valid.',
            'first_due_date.required' => 'Tanggal jatuh tempo pertama wajib diisi.',
            'first_due_date.date' => 'Format tanggal tidak valid.',
        ]);

        $rate = (float) $validated['interest_rate'];
        $rateBps = (int) round($rate * 100);

        $calculation = $this->loanCalculationService->calculate(
            (int) $validated['principal_amount'],
            $rateBps,
            (string) $validated['interest_method'],
            (int) $validated['tenor'],
        );

        $maturity = $this->loanCalculationService->maturityDate(
            $validated['first_due_date'],
            (string) $validated['installment_frequency'],
            (int) $validated['tenor'],
        );

        return response()->json(array_merge($calculation, [
            'interest_rate_bps' => $rateBps,
            'maturity_date' => $maturity->toDateString(),
        ]));
    }

    /**
     * Submit a DRAFT application into the review pipeline.
     */
    public function submit(Loan $loan): RedirectResponse
    {
        return $this->runAction(fn () => $this->loanService->submitLoan($loan), 'Pengajuan pinjaman berhasil disubmit.', $loan);
    }

    /**
     * Take a submitted loan into review.
     */
    public function review(Loan $loan): RedirectResponse
    {
        return $this->runAction(fn () => $this->loanService->startReview($loan), 'Pengajuan pinjaman masuk dalam proses review.', $loan);
    }

    /**
     * Approve a loan application.
     */
    public function approve(Loan $loan): RedirectResponse
    {
        return $this->runAction(fn () => $this->loanService->approveLoan($loan), 'Pengajuan pinjaman berhasil disetujui.', $loan);
    }

    /**
     * Reject a loan application with a mandatory reason.
     */
    public function reject(RejectLoanRequest $request, Loan $loan): RedirectResponse
    {
        return $this->runAction(
            fn () => $this->loanService->rejectLoan($loan, null, (string) $request->validated()['reason']),
            'Pengajuan pinjaman ditolak.',
            $loan,
        );
    }

    /**
     * Move an approved loan to READY_FOR_DISBURSEMENT.
     */
    public function ready(Loan $loan): RedirectResponse
    {
        return $this->runAction(fn () => $this->loanService->prepareDisbursement($loan), 'Pinjaman siap untuk dicairkan.', $loan);
    }

    /**
     * Disburse an approved loan and generate its installment schedule.
     */
    public function disburse(Loan $loan): RedirectResponse
    {
        return $this->runAction(fn () => $this->loanService->disburseLoan($loan), 'Pinjaman berhasil dicairkan. Jadwal angsuran telah dibuat.', $loan);
    }

    /**
     * Cancel a non-finalized loan.
     */
    public function cancel(Loan $loan): RedirectResponse
    {
        return $this->runAction(fn () => $this->loanService->cancelLoan($loan), 'Pengajuan pinjaman dibatalkan.', $loan);
    }

    /**
     * Run a loan mutation, converting validation errors into useful flash messages.
     */
    private function runAction(callable $operation, string $successMessage, Loan $loan): RedirectResponse
    {
        try {
            $operation();
        } catch (InvalidArgumentException $exception) {
            return redirect()->route('loans.show', $loan)->with('error', $exception->getMessage());
        }

        return redirect()->route('loans.show', $loan)->with('success', $successMessage);
    }
}
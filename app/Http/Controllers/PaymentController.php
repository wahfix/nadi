<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReversePaymentRequest;
use App\Http\Requests\StorePaymentRequest;
use App\Models\Loan;
use App\Models\Payment;
use App\Services\PaymentAllocationService;
use App\Services\PaymentReversalService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use InvalidArgumentException;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentAllocationService $paymentAllocationService,
        private readonly PaymentReversalService $paymentReversalService,
    ) {}

    /**
     * Bubble up the loan outstanding so views can render it cheaply.
     *
     * @return Builder<Loan>
     */
    private static function payableLoansQuery()
    {
        return Loan::query()
            ->with(['customer'])
            ->whereIn('status', [Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE])
            ->where('outstanding_total', '>', 0)
            ->orderByDesc('created_at');
    }

    /**
     * Display a paginated, searchable, filterable list of payments.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Payment::class);

        $payments = Payment::query()
            ->with(['loan', 'customer', 'receivedBy', 'reversal'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($inner) use ($search) {
                    $inner->where('payment_number', 'like', "%{$search}%")
                        ->orWhere('reference_number', 'like', "%{$search}%")
                        ->orWhereHas('loan', fn ($loan) => $loan->where('loan_number', 'like', "%{$search}%"))
                        ->orWhereHas('customer', fn ($customer) => $customer->where('full_name', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('payment_method'), fn ($query) => $query->where('payment_method', $request->string('payment_method')->toString()))
            ->when($request->filled('status'), function ($query) use ($request) {
                $request->string('status')->toString() === 'reversed'
                    ? $query->has('reversal')
                    : $query->doesntHave('reversal');
            })
            ->orderByDesc('payment_date')
            ->paginate(15)
            ->withQueryString();

        return view('modules.payments.index', [
            'payments' => $payments,
            'methodOptions' => [
                Payment::METHOD_CASH => 'Tunai',
                Payment::METHOD_BANK_TRANSFER => 'Transfer Bank',
                Payment::METHOD_QRIS => 'QRIS',
                Payment::METHOD_OTHER => 'Lainnya',
            ],
        ]);
    }

    /**
     * Show the cashier payment form for a payable loan.
     */
    public function create(Request $request): View
    {
        Gate::authorize('create', Payment::class);

        $loans = self::payableLoansQuery()->get();

        $preselected = $request->integer('loan') > 0
            ? $loans->firstWhere('id', $request->integer('loan'))
            : null;

        return view('modules.payments.create', [
            'loans' => $loans,
            'preselectedLoan' => $preselected,
            'methodOptions' => [
                Payment::METHOD_CASH => 'Tunai',
                Payment::METHOD_BANK_TRANSFER => 'Transfer Bank',
                Payment::METHOD_QRIS => 'QRIS',
                Payment::METHOD_OTHER => 'Lainnya',
            ],
        ]);
    }

    /**
     * Record a new cashier payment with strict allocation.
     */
    public function store(StorePaymentRequest $request): RedirectResponse
    {
        $loan = Loan::findOrFail((int) $request->validated('loan_id'));

        try {
            $payment = $this->paymentAllocationService->recordPayment(
                $loan,
                (int) $request->validated('amount'),
                (string) $request->validated('payment_method'),
                (string) $request->validated('payment_date'),
                $request->validated('reference_number'),
                $request->validated('notes'),
            );
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->route('payments.create', ['loan' => $loan->id])
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('payments.show', $payment)
            ->with('success', 'Pembayaran '.$payment->payment_number.' berhasil dicatat.');
    }

    /**
     * Display the immutable payment detail with its allocation breakdown.
     */
    public function show(Payment $payment): View
    {
        Gate::authorize('view', $payment);

        $payment->load([
            'loan.customer',
            'loan.installments',
            'customer',
            'receivedBy',
            'reversal.reversedBy',
            'installment',
        ]);

        return view('modules.payments.show', [
            'payment' => $payment,
        ]);
    }

    /**
     * Printable payment receipt (kuitansi tanda terima).
     */
    public function receipt(Payment $payment): View
    {
        Gate::authorize('receipt', $payment);

        $payment->load(['loan', 'customer', 'receivedBy', 'reversal']);

        return view('modules.payments.receipt', [
            'payment' => $payment,
        ]);
    }

    /**
     * Reverse an immutable payment (admin only), restoring all balances.
     */
    public function reverse(ReversePaymentRequest $request, Payment $payment): RedirectResponse
    {
        try {
            $reversal = $this->paymentReversalService->reverse(
                $payment,
                (string) $request->validated('reason'),
            );
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->route('payments.show', $payment)
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('payments.show', $payment)
            ->with('success', 'Pembayaran '.$payment->payment_number.' berhasil dibalikkan (reversal #'.$reversal->id.').');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Collateral;
use App\Models\CollateralRelease;
use App\Models\CollectionActivity;
use App\Models\Customer;
use App\Models\Installment;
use App\Models\Loan;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    private const array STATUS_LABELS = [
        'DRAFT' => 'Draft',
        'SUBMITTED' => 'Disubmit',
        'UNDER_REVIEW' => 'Dalam Review',
        'APPROVED' => 'Disetujui',
        'REJECTED' => 'Ditolak',
        'READY_FOR_DISBURSEMENT' => 'Siap Dicairkan',
        'ACTIVE' => 'Aktif',
        'OVERDUE' => 'Menunggak',
        'COMPLETED' => 'Lunas',
        'DEFAULTED' => 'Macet',
        'CANCELLED' => 'Dibatalkan',
    ];

    private const array STATUS_COLORS = [
        'DRAFT' => 'neutral',
        'SUBMITTED' => 'blue',
        'UNDER_REVIEW' => 'amber',
        'APPROVED' => 'blue',
        'REJECTED' => 'red',
        'READY_FOR_DISBURSEMENT' => 'blue',
        'ACTIVE' => 'green',
        'OVERDUE' => 'amber',
        'COMPLETED' => 'green',
        'DEFAULTED' => 'red',
        'CANCELLED' => 'neutral',
    ];

    private const array COLLATERAL_STATUS_LABELS = [
        'PENDING' => 'Menunggu',
        'RECEIVED' => 'Diterima',
        'IN_CUSTODY' => 'Dalam Penyimpanan',
        'READY_FOR_RELEASE' => 'Siap Dilepas',
        'RELEASED' => 'Dilepas',
        'DISPUTED' => 'Dalam Sengketa',
    ];

    private const array COLLATERAL_CONTEXT_LABELS = [
        'DOCUMENT' => 'Dokumen',
        'VEHICLE' => 'Kendaraan',
        'ELECTRONIC' => 'Elektronik',
        'OTHER' => 'Lainnya',
    ];

    private const array COLLECTION_METHOD_LABELS = [
        'PHONE' => 'Telepon',
        'WHATSAPP' => 'WhatsApp',
        'IN_PERSON' => 'Kunjungan Langsung',
        'OTHER' => 'Lainnya',
    ];

    private const array COLLECTION_RESULT_LABELS = [
        'PAID' => 'Pembayaran Diterima',
        'PROMISE_TO_PAY' => 'Janji Bayar',
        'NO_RESPONSE' => 'Tidak Ada Respon',
        'CONTACT_FAILED' => 'Kontak Gagal',
        'DISPUTED' => 'Sengketa',
        'OTHER' => 'Lainnya',
    ];

    private const array PAYMENT_METHOD_LABELS = [
        'CASH' => 'Tunai',
        'BANK_TRANSFER' => 'Transfer Bank',
        'QRIS' => 'QRIS',
        'OTHER' => 'Lainnya',
    ];

    /**
     * Menu daftar seluruh laporan.
     */
    public function index(): View
    {
        return view('modules.reports.index');
    }

    /**
     * Laporan daftar nasabah.
     */
    public function customers(Request $request): View
    {
        $customers = Customer::query()
            ->withCount('loans')
            ->when($request->filled('search'), function (Builder $q) use ($request) {
                $q->where(function (Builder $inner) use ($request) {
                    $inner->where('customer_code', 'like', '%'.$request->string('search').'%')
                        ->orWhere('full_name', 'like', '%'.$request->string('search').'%')
                        ->orWhere('id_number', 'like', '%'.$request->string('search').'%');
                });
            })
            ->when($request->filled('date_from'), fn (Builder $q) => $q->whereDate('created_at', '>=', $request->string('date_from')))
            ->when($request->filled('date_to'), fn (Builder $q) => $q->whereDate('created_at', '<=', $request->string('date_to')))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('modules.reports.customers', ['customers' => $customers]);
    }

    /**
     * Laporan daftar pinjaman.
     */
    public function loans(Request $request): View
    {
        $loans = Loan::query()
            ->with(['customer'])
            ->when($request->filled('search'), function (Builder $q) use ($request) {
                $q->where(function (Builder $inner) use ($request) {
                    $inner->where('loan_number', 'like', '%'.$request->string('search').'%')
                        ->orWhereHas('customer', fn (Builder $customer) => $customer->where('full_name', 'like', '%'.$request->string('search').'%'));
                });
            })
            ->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->string('status')))
            ->when($request->filled('date_from'), fn (Builder $q) => $q->whereDate('created_at', '>=', $request->string('date_from')))
            ->when($request->filled('date_to'), fn (Builder $q) => $q->whereDate('created_at', '<=', $request->string('date_to')))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('modules.reports.loans', [
            'loans' => $loans,
            'statusLabels' => self::STATUS_LABELS,
            'statusColors' => self::STATUS_COLORS,
        ]);
    }

    /**
     * Laporan portofolio pinjaman berjalan (outstanding).
     */
    public function outstanding(Request $request): View
    {
        $query = Loan::query()
            ->with(['customer'])
            ->whereIn('status', [Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE])
            ->when($request->filled('search'), fn (Builder $q) => $q->whereHas('customer', fn (Builder $customer) => $customer->where('full_name', 'like', '%'.$request->string('search').'%')))
            ->when($request->filled('date_from'), fn (Builder $q) => $q->whereDate('disbursement_date', '>=', $request->string('date_from')))
            ->when($request->filled('date_to'), fn (Builder $q) => $q->whereDate('disbursement_date', '<=', $request->string('date_to')));

        $totals = [
            'count' => (new Loan)->newQuery()
                ->whereIn('status', [Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE])
                ->when($request->filled('search'), fn (Builder $q) => $q->whereHas('customer', fn (Builder $customer) => $customer->where('full_name', 'like', '%'.$request->string('search').'%')))
                ->when($request->filled('date_from'), fn (Builder $q) => $q->whereDate('disbursement_date', '>=', $request->string('date_from')))
                ->when($request->filled('date_to'), fn (Builder $q) => $q->whereDate('disbursement_date', '<=', $request->string('date_to')))
                ->count(),
            'outstanding_total' => (int) (new Loan)->newQuery()
                ->whereIn('status', [Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE])
                ->when($request->filled('date_from'), fn (Builder $q) => $q->whereDate('disbursement_date', '>=', $request->string('date_from')))
                ->when($request->filled('date_to'), fn (Builder $q) => $q->whereDate('disbursement_date', '<=', $request->string('date_to')))
                ->sum('outstanding_total'),
            'principal_amount' => (int) (new Loan)->newQuery()
                ->whereIn('status', [Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE])
                ->when($request->filled('date_from'), fn (Builder $q) => $q->whereDate('disbursement_date', '>=', $request->string('date_from')))
                ->when($request->filled('date_to'), fn (Builder $q) => $q->whereDate('disbursement_date', '<=', $request->string('date_to')))
                ->sum('principal_amount'),
        ];

        $loans = $query->orderByDesc('outstanding_total')->paginate(15)->withQueryString();

        return view('modules.reports.outstanding', [
            'loans' => $loans,
            'totals' => $totals,
            'statusLabels' => self::STATUS_LABELS,
            'statusColors' => self::STATUS_COLORS,
        ]);
    }

    /**
     * Laporan jadwal jatuh tempo angsuran dalam rentang tanggal.
     */
    public function dueDates(Request $request): View
    {
        $installments = Installment::query()
            ->with(['loan.customer'])
            ->where('status', '!=', 'PAID')
            ->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->string('status')))
            ->when($request->filled('date_from'), fn (Builder $q) => $q->whereDate('due_date', '>=', $request->string('date_from')))
            ->when($request->filled('date_to'), fn (Builder $q) => $q->whereDate('due_date', '<=', $request->string('date_to')))
            ->orderBy('due_date')
            ->paginate(15)
            ->withQueryString();

        return view('modules.reports.due-dates', ['installments' => $installments]);
    }

    /**
     * Laporan tunggakan angsuran dengan DPD (days past due).
     */
    public function overdue(Request $request): View
    {
        $installments = Installment::query()
            ->with(['loan.customer'])
            ->where('status', 'OVERDUE')
            ->whereHas('loan', fn (Builder $loan) => $loan->whereIn('status', [Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE]))
            ->when($request->filled('search'), fn (Builder $q) => $q->whereHas('loan.customer', fn (Builder $customer) => $customer->where('full_name', 'like', '%'.$request->string('search').'%')))
            ->when($request->filled('date_from'), fn (Builder $q) => $q->whereDate('due_date', '>=', $request->string('date_from')))
            ->when($request->filled('date_to'), fn (Builder $q) => $q->whereDate('due_date', '<=', $request->string('date_to')))
            ->orderBy('due_date')
            ->paginate(15)
            ->withQueryString();

        return view('modules.reports.overdue', ['installments' => $installments]);
    }

    /**
     * Laporan pembayaran / penerimaan uang.
     */
    public function payments(Request $request): View
    {
        $payments = Payment::query()
            ->with(['customer', 'loan'])
            ->when($request->filled('search'), function (Builder $q) use ($request) {
                $q->where(function (Builder $inner) use ($request) {
                    $inner->where('payment_number', 'like', '%'.$request->string('search').'%')
                        ->orWhereHas('customer', fn (Builder $customer) => $customer->where('full_name', 'like', '%'.$request->string('search').'%'))
                        ->orWhereHas('loan', fn (Builder $loan) => $loan->where('loan_number', 'like', '%'.$request->string('search').'%'));
                });
            })
            ->when($request->filled('method'), fn (Builder $q) => $q->where('payment_method', $request->string('method')))
            ->when($request->filled('date_from'), fn (Builder $q) => $q->whereDate('payment_date', '>=', $request->string('date_from')))
            ->when($request->filled('date_to'), fn (Builder $q) => $q->whereDate('payment_date', '<=', $request->string('date_to')))
            ->latest('payment_date')
            ->paginate(15)
            ->withQueryString();

        return view('modules.reports.payments', [
            'payments' => $payments,
            'methodLabels' => self::PAYMENT_METHOD_LABELS,
        ]);
    }

    /**
     * Laporan jaminan yang diterima dan disimpan.
     */
    public function collaterals(Request $request): View
    {
        $collaterals = Collateral::query()
            ->with(['customer', 'loan'])
            ->when($request->filled('search'), function (Builder $q) use ($request) {
                $q->where(function (Builder $inner) use ($request) {
                    $inner->where('collateral_code', 'like', '%'.$request->string('search').'%')
                        ->orWhereHas('customer', fn (Builder $customer) => $customer->where('full_name', 'like', '%'.$request->string('search').'%'));
                });
            })
            ->when($request->filled('status'), fn (Builder $q) => $q->where('custody_status', $request->string('status')))
            ->when($request->filled('date_from'), fn (Builder $q) => $q->whereDate('received_date', '>=', $request->string('date_from')))
            ->when($request->filled('date_to'), fn (Builder $q) => $q->whereDate('received_date', '<=', $request->string('date_to')))
            ->orderByDesc('received_date')
            ->paginate(15)
            ->withQueryString();

        return view('modules.reports.collaterals', [
            'collaterals' => $collaterals,
            'statusLabels' => self::COLLATERAL_STATUS_LABELS,
            'typeLabels' => self::COLLATERAL_CONTEXT_LABELS,
        ]);
    }

    /**
     * Laporan pengambilan / pelepasan jaminan.
     */
    public function releases(Request $request): View
    {
        $releases = CollateralRelease::query()
            ->with(['customer', 'loan', 'collateral'])
            ->when($request->filled('search'), function (Builder $q) use ($request) {
                $q->where(function (Builder $inner) use ($request) {
                    $inner->where('release_number', 'like', '%'.$request->string('search').'%')
                        ->orWhere('released_to_name', 'like', '%'.$request->string('search').'%')
                        ->orWhereHas('customer', fn (Builder $customer) => $customer->where('full_name', 'like', '%'.$request->string('search').'%'));
                });
            })
            ->when($request->filled('date_from'), fn (Builder $q) => $q->whereDate('release_date', '>=', $request->string('date_from')))
            ->when($request->filled('date_to'), fn (Builder $q) => $q->whereDate('release_date', '<=', $request->string('date_to')))
            ->orderByDesc('release_date')
            ->paginate(15)
            ->withQueryString();

        return view('modules.reports.releases', ['releases' => $releases]);
    }

    /**
     * Laporan aktivitas penagihan (Collection / LC).
     */
    public function collectionActivities(Request $request): View
    {
        $activities = CollectionActivity::query()
            ->with(['loan.customer', 'collector'])
            ->when($request->filled('search'), fn (Builder $q) => $q->whereHas('loan.customer', fn (Builder $customer) => $customer->where('full_name', 'like', '%'.$request->string('search').'%')))
            ->when($request->filled('method'), fn (Builder $q) => $q->where('contact_method', $request->string('method')))
            ->when($request->filled('result'), fn (Builder $q) => $q->where('result', $request->string('result')))
            ->when($request->filled('date_from'), fn (Builder $q) => $q->whereDate('contact_date', '>=', $request->string('date_from')))
            ->when($request->filled('date_to'), fn (Builder $q) => $q->whereDate('contact_date', '<=', $request->string('date_to')))
            ->orderByDesc('contact_date')
            ->paginate(15)
            ->withQueryString();

        return view('modules.reports.collection-activities', [
            'activities' => $activities,
            'methodLabels' => self::COLLECTION_METHOD_LABELS,
            'resultLabels' => self::COLLECTION_RESULT_LABELS,
        ]);
    }

    /**
     * Laporan audit log dalam rentang tanggal.
     */
    public function auditLogs(Request $request): View
    {
        $logs = AuditLog::query()
            ->with('user')
            ->when($request->filled('search'), fn (Builder $q) => $q->where('action', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('action'), fn (Builder $q) => $q->where('action', $request->string('action')))
            ->when($request->filled('date_from'), fn (Builder $q) => $q->whereDate('created_at', '>=', $request->string('date_from')))
            ->when($request->filled('date_to'), fn (Builder $q) => $q->whereDate('created_at', '<=', $request->string('date_to')))
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        $actions = AuditLog::query()->distinct()->orderBy('action')->pluck('action', 'action')->all();

        return view('modules.reports.audit-logs', ['logs' => $logs, 'actions' => $actions]);
    }
}

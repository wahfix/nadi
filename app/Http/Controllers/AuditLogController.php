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
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    private const array ENTITY_LABELS = [
        Loan::class => 'Pinjaman',
        Customer::class => 'Nasabah',
        Payment::class => 'Pembayaran',
        Installment::class => 'Angsuran',
        Collateral::class => 'Jaminan',
        CollateralRelease::class => 'Pengambilan Jaminan',
        IdentityVerification::class => 'Verifikasi Identitas',
        CollectionActivity::class => 'Penagihan',
        User::class => 'Pengguna',
    ];

    private const array ACTION_COLORS = [
        'CUSTOMER_CREATED' => 'neutral',
        'CUSTOMER_UPDATED' => 'neutral',
        'LOAN_CREATED' => 'blue',
        'LOAN_SUBMITTED' => 'blue',
        'LOAN_APPROVED' => 'green',
        'LOAN_REJECTED' => 'red',
        'LOAN_DISBURSED' => 'green',
        'LOAN_STATUS_CHANGED' => 'amber',
        'PAYMENT_CREATED' => 'green',
        'PAYMENT_REVERSED' => 'red',
        'COLLECTION_CREATED' => 'blue',
        'COLLATERAL_RECEIVED' => 'amber',
        'IDENTITY_VERIFIED' => 'green',
        'IDENTITY_FAILED' => 'red',
        'RELEASE_CREATED' => 'green',
        'COLLATERAL_RELEASED' => 'green',
        'USER_CREATED' => 'neutral',
        'PERMISSION_CHANGED' => 'amber',
    ];

    /**
     * Display a searchable, filterable, paginated list of audit logs.
     */
    public function index(Request $request): View
    {
        $logs = AuditLog::query()
            ->with('user')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($inner) use ($search) {
                    $inner->where('action', 'like', "%{$search}%")
                        ->orWhere('entity_type', 'like', "%{$search}%")
                        ->orWhere('entity_id', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('action'), fn ($query) => $query->where('action', $request->string('action')->toString()))
            ->when($request->filled('entity'), fn ($query) => $query->where('entity_type', $request->string('entity')->toString()))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('created_at', '>=', $request->string('date_from')->toString()))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('created_at', '<=', $request->string('date_to')->toString()))
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('modules.audit-log.index', [
            'logs' => $logs,
            'actionOptions' => $this->actionOptions(),
            'entityOptions' => self::ENTITY_LABELS,
            'entityLabel' => fn (string $entityType) => self::ENTITY_LABELS[$entityType] ?? class_basename($entityType),
            'actionColor' => fn (string $action) => self::ACTION_COLORS[$action] ?? 'neutral',
            'recordRef' => fn (string $entityType, int $entityId) => $this->recordRef($entityType, $entityId),
        ]);
    }

    /**
     * Resolve the business reference number for a mutated record.
     */
    private function recordRef(string $entityType, int $entityId): string
    {
        $record = match ($entityType) {
            Loan::class => Loan::query()->find($entityId),
            Customer::class => Customer::query()->find($entityId),
            Payment::class => Payment::query()->find($entityId),
            Collateral::class => Collateral::query()->find($entityId),
            CollateralRelease::class => CollateralRelease::query()->find($entityId),
            default => null,
        };

        if ($record instanceof Loan) {
            return $record->loan_number;
        }

        if ($record instanceof Customer) {
            return $record->customer_code;
        }

        if ($record instanceof Payment) {
            return $record->payment_number;
        }

        if ($record instanceof Collateral) {
            return $record->collateral_code;
        }

        if ($record instanceof CollateralRelease) {
            return $record->release_number;
        }

        return (string) $entityId;
    }

    /**
     * @return array<string, string>
     */
    private function actionOptions(): array
    {
        return AuditLog::query()
            ->distinct()
            ->orderBy('action')
            ->pluck('action', 'action')
            ->all();
    }
}

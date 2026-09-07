<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogService
{
    // The 18 standard event types
    public const CUSTOMER_CREATED = 'CUSTOMER_CREATED';

    public const CUSTOMER_UPDATED = 'CUSTOMER_UPDATED';

    public const LOAN_CREATED = 'LOAN_CREATED';

    public const LOAN_SUBMITTED = 'LOAN_SUBMITTED';

    public const LOAN_APPROVED = 'LOAN_APPROVED';

    public const LOAN_REJECTED = 'LOAN_REJECTED';

    public const LOAN_DISBURSED = 'LOAN_DISBURSED';

    public const LOAN_STATUS_CHANGED = 'LOAN_STATUS_CHANGED';

    public const PAYMENT_CREATED = 'PAYMENT_CREATED';

    public const PAYMENT_REVERSED = 'PAYMENT_REVERSED';

    public const COLLECTION_CREATED = 'COLLECTION_CREATED';

    public const COLLATERAL_RECEIVED = 'COLLATERAL_RECEIVED';

    public const IDENTITY_VERIFIED = 'IDENTITY_VERIFIED';

    public const IDENTITY_FAILED = 'IDENTITY_FAILED';

    public const RELEASE_CREATED = 'RELEASE_CREATED';

    public const COLLATERAL_RELEASED = 'COLLATERAL_RELEASED';

    public const USER_CREATED = 'USER_CREATED';

    public const PERMISSION_CHANGED = 'PERMISSION_CHANGED';

    /**
     * Record an audit log entry.
     *
     * @param  array<string, int|string|null>|null  $oldValues
     * @param  array<string, int|string|null>|null  $newValues
     */
    public function log(
        string $action,
        string|Model $entity,
        ?int $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $userId = null
    ): AuditLog {
        $entityType = $entity instanceof Model ? get_class($entity) : (string) $entity;
        $id = $entity instanceof Model ? $entity->getKey() : $entityId;

        return AuditLog::create([
            'user_id' => $userId ?? Auth::id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => (int) $id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip() ?? '127.0.0.1',
            'user_agent' => Request::userAgent() ?? 'CLI',
            'created_at' => now(),
        ]);
    }
}

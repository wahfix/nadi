<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\LoanStatusHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LoanStatusService
{
    /**
     * The 11 official statuses and their legal outgoing transitions.
     */
    private const TRANSITIONS = [
        Loan::STATUS_DRAFT => [Loan::STATUS_SUBMITTED, Loan::STATUS_CANCELLED],
        Loan::STATUS_SUBMITTED => [Loan::STATUS_UNDER_REVIEW, Loan::STATUS_CANCELLED],
        Loan::STATUS_UNDER_REVIEW => [Loan::STATUS_APPROVED, Loan::STATUS_REJECTED],
        Loan::STATUS_APPROVED => [Loan::STATUS_READY_FOR_DISBURSEMENT],
        Loan::STATUS_REJECTED => [],
        Loan::STATUS_READY_FOR_DISBURSEMENT => [Loan::STATUS_ACTIVE, Loan::STATUS_CANCELLED],
        Loan::STATUS_ACTIVE => [Loan::STATUS_OVERDUE, Loan::STATUS_COMPLETED, Loan::STATUS_DEFAULTED],
        Loan::STATUS_OVERDUE => [Loan::STATUS_ACTIVE, Loan::STATUS_COMPLETED, Loan::STATUS_DEFAULTED],
        Loan::STATUS_COMPLETED => [],
        Loan::STATUS_DEFAULTED => [],
        Loan::STATUS_CANCELLED => [],
    ];

    /**
     * All legal transitions across the whole state machine.
     *
     * @return array<string, array<int, string>>
     */
    public function allTransitions(): array
    {
        return self::TRANSITIONS;
    }

    /**
     * Whether the given status transition is legal.
     */
    public function canTransition(Loan $loan, string $toStatus): bool
    {
        return in_array($toStatus, self::TRANSITIONS[$loan->status] ?? [], true);
    }

    /**
     * Apply a validated status transition within a database transaction.
     *
     * Records a row in loan_status_histories and an audit event. No arbitrary
     * status changes are allowed.
     *
     * @param  array<string, int|string|null>|null  $oldSnapshot
     * @param  array<string, int|string|null>|null  $newSnapshot
     */
    public function transition(
        Loan $loan,
        string $toStatus,
        ?int $userId = null,
        ?string $reason = null,
        ?string $auditAction = null,
        ?array $oldSnapshot = null,
        ?array $newSnapshot = null,
    ): Loan {
        return DB::transaction(function () use ($loan, $toStatus, $userId, $reason, $auditAction, $oldSnapshot, $newSnapshot) {
            if (! $this->canTransition($loan, $toStatus)) {
                throw new InvalidArgumentException(
                    "Transisi status pinjaman tidak diizinkan: {$loan->status} → {$toStatus}."
                );
            }

            $fromStatus = $loan->status;
            $actorId = $userId ?? (int) Auth::id();

            $loan->update(['status' => $toStatus]);

            LoanStatusHistory::create([
                'loan_id' => $loan->id,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'changed_by' => $actorId,
                'reason' => $reason,
                'created_at' => now(),
            ]);

            $oldSnapshot ??= ['status' => $fromStatus];
            $newSnapshot ??= ['status' => $toStatus];

            app(AuditLogService::class)->log(
                $auditAction ?? AuditLogService::LOAN_STATUS_CHANGED,
                $loan,
                null,
                $oldSnapshot,
                $newSnapshot,
                $actorId,
            );

            return $loan->refresh();
        });
    }
}

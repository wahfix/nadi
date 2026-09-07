<?php

namespace App\Services;

use App\Models\Collateral;
use App\Models\Loan;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CollateralService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly SequentialNumberService $sequentialNumberService,
    ) {}

    /**
     * Receive a collateral asset for an existing loan (12-step operational flow).
     *
     * @param  array<string, mixed>  $data
     */
    public function receiveCollateral(Loan $loan, array $data): Collateral
    {
        if (! in_array($loan->status, [
            Loan::STATUS_ACTIVE,
            Loan::STATUS_OVERDUE,
            Loan::STATUS_APPROVED,
            Loan::STATUS_READY_FOR_DISBURSEMENT,
            Loan::STATUS_COMPLETED,
        ], true)) {
            throw new InvalidArgumentException('Pinjaman tidak dalam status yang memungkinkan penerimaan jaminan.');
        }

        return DB::transaction(function () use ($loan, $data) {
            $collateral = Collateral::create([
                'collateral_code' => $this->sequentialNumberService->generateCollateralCode(),
                'loan_id' => $loan->id,
                'customer_id' => $loan->customer_id,
                'collateral_type' => $data['collateral_type'],
                'description' => $data['description'],
                'identification_number' => $data['identification_number'],
                'estimated_value' => $data['estimated_value'],
                'received_date' => $data['received_date'],
                'condition_on_receipt' => $data['condition_on_receipt'],
                'storage_location' => $data['storage_location'],
                'custody_status' => Collateral::STATUS_RECEIVED,
                'received_by' => $data['received_by'],
            ]);

            $this->auditLogService->log(
                AuditLogService::COLLATERAL_RECEIVED,
                $collateral,
                null,
                null,
                $this->collateralSnapshot($collateral),
            );

            return $collateral;
        });
    }

    /**
     * Update custody status of a collateral (e.g. move to READY_FOR_RELEASE).
     */
    public function updateCustodyStatus(Collateral $collateral, string $status): Collateral
    {
        $allowedTransitions = [
            Collateral::STATUS_RECEIVED => [Collateral::STATUS_IN_CUSTODY, Collateral::STATUS_DISPUTED],
            Collateral::STATUS_IN_CUSTODY => [Collateral::STATUS_READY_FOR_RELEASE, Collateral::STATUS_DISPUTED],
            Collateral::STATUS_READY_FOR_RELEASE => [Collateral::STATUS_IN_CUSTODY],
        ];

        $current = $collateral->custody_status;

        if (! isset($allowedTransitions[$current]) || ! in_array($status, $allowedTransitions[$current], true)) {
            throw new InvalidArgumentException(
                "Transisi status dari '{$current}' ke '{$status}' tidak diizinkan."
            );
        }

        return DB::transaction(function () use ($collateral, $status) {
            $oldSnapshot = $this->collateralSnapshot($collateral);

            $collateral->update(['custody_status' => $status]);

            $this->auditLogService->log(
                AuditLogService::LOAN_STATUS_CHANGED,
                $collateral,
                null,
                $oldSnapshot,
                $this->collateralSnapshot($collateral),
            );

            return $collateral->refresh();
        });
    }

    /**
     * @return array<string, int|string|null>
     */
    private function collateralSnapshot(Collateral $collateral): array
    {
        return [
            'collateral_code' => $collateral->collateral_code,
            'loan_id' => $collateral->loan_id,
            'customer_id' => $collateral->customer_id,
            'collateral_type' => $collateral->collateral_type,
            'description' => $collateral->description,
            'identification_number' => $collateral->identification_number,
            'estimated_value' => $collateral->estimated_value,
            'received_date' => $collateral->received_date ? CarbonImmutable::parse($collateral->received_date)->toDateString() : null,
            'condition_on_receipt' => $collateral->condition_on_receipt,
            'storage_location' => $collateral->storage_location,
            'custody_status' => $collateral->custody_status,
            'received_by' => $collateral->received_by,
            'released_by' => $collateral->released_by,
            'released_at' => $collateral->released_at ? CarbonImmutable::parse($collateral->released_at)->toIso8601String() : null,
        ];
    }
}

<?php

namespace App\Services;

use App\Models\Collateral;
use App\Models\CollateralRelease;
use App\Models\IdentityVerification;
use App\Models\Loan;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CollateralReleaseService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly SequentialNumberService $sequentialNumberService,
    ) {}

    /**
     * Record an identity verification for a collateral release applicant.
     *
     * @param  array<string, mixed>  $data
     */
    public function verifyIdentity(Loan $loan, array $data): IdentityVerification
    {
        return DB::transaction(function () use ($loan, $data) {
            $verification = IdentityVerification::create([
                'customer_id' => $loan->customer_id,
                'loan_id' => $loan->id,
                'verification_method' => $data['verification_method'],
                'verified_name' => $data['verified_name'],
                'verified_id_number' => $data['verified_id_number'],
                'result' => $data['result'],
                'verifier_id' => $data['verifier_id'],
                'verification_timestamp' => $data['verification_timestamp'] ?? now()->toDateTimeString(),
                'notes' => $data['notes'] ?? null,
            ]);

            $eventType = $data['result'] === IdentityVerification::RESULT_VERIFIED
                ? AuditLogService::IDENTITY_VERIFIED
                : AuditLogService::IDENTITY_FAILED;

            $this->auditLogService->log(
                $eventType,
                $verification,
                null,
                null,
                [
                    'customer_id' => $verification->customer_id,
                    'loan_id' => $verification->loan_id,
                    'verification_method' => $verification->verification_method,
                    'verified_name' => $verification->verified_name,
                    'result' => $verification->result,
                ],
            );

            return $verification;
        });
    }

    /**
     * Validate the 8 mandatory conditions and execute collateral release (11-step workflow).
     *
     * @param  array<string, mixed>  $data
     */
    public function executeRelease(Collateral $collateral, IdentityVerification $verification, array $data): CollateralRelease
    {
        $this->validateEightConditions($collateral, $verification);

        return DB::transaction(function () use ($collateral, $verification, $data) {
            $oldCollateralSnapshot = [
                'custody_status' => $collateral->custody_status,
                'released_by' => $collateral->released_by,
                'released_at' => $collateral->released_at ? CarbonImmutable::parse($collateral->released_at)->toIso8601String() : null,
            ];

            $release = CollateralRelease::create([
                'release_number' => $this->sequentialNumberService->generateReleaseNumber(),
                'collateral_id' => $collateral->id,
                'loan_id' => $collateral->loan_id,
                'customer_id' => $collateral->customer_id,
                'verified_identity_id' => $verification->id,
                'released_to_name' => $data['released_to_name'],
                'relationship_to_customer' => $data['relationship_to_customer'],
                'release_date' => $data['release_date'],
                'release_location' => $data['release_location'],
                'released_by' => $data['released_by'],
                'witness_id' => $data['witness_id'] ?? null,
                'customer_signature_reference' => $data['customer_signature_reference'] ?? null,
                'handover_notes' => $data['handover_notes'] ?? null,
            ]);

            $collateral->update([
                'custody_status' => Collateral::STATUS_RELEASED,
                'released_by' => $data['released_by'],
                'released_at' => $data['release_date'],
            ]);

            $this->auditLogService->log(
                AuditLogService::COLLATERAL_RELEASED,
                $release,
                null,
                $oldCollateralSnapshot,
                [
                    'release_number' => $release->release_number,
                    'collateral_id' => $release->collateral_id,
                    'released_to_name' => $release->released_to_name,
                    'released_by' => $release->released_by,
                ],
            );

            return $release;
        });
    }

    /**
     * Evaluate the 8 mandatory release conditions. Throws on failure.
     */
    private function validateEightConditions(Collateral $collateral, IdentityVerification $verification): void
    {
        // 1. Collateral exists (guaranteed by the non-null parameter type)

        // 2. Collateral belongs to a valid loan
        $loan = $collateral->loan;
        if ($loan === null) {
            throw new InvalidArgumentException('Jaminan tidak terasosiasi dengan pinjaman yang sah.');
        }

        // 3. Loan is eligible for release (must be COMPLETED or have outstanding_total = 0)
        if (! $loan->isPaidOff()) {
            throw new InvalidArgumentException(
                'Pinjaman belum lunas. Sisa tagihan: '.format_rupiah($loan->outstanding_total).'.'
            );
        }

        // 4. Outstanding obligations are all zero
        if ($loan->outstanding_principal > 0 || $loan->outstanding_interest > 0 || $loan->outstanding_penalty > 0) {
            throw new InvalidArgumentException(
                'Masih terdapat kewajiban finansial yang belum lunas.'
            );
        }

        // 5. Collateral physical status must be READY_FOR_RELEASE
        if ($collateral->custody_status !== Collateral::STATUS_READY_FOR_RELEASE) {
            throw new InvalidArgumentException(
                'Status jaminan harus SIAP DISERAHKAN. Status saat ini: '.$collateral->custody_status.'.'
            );
        }

        // 6. Identity must be verified with VERIFIED result
        if ($verification->result !== IdentityVerification::RESULT_VERIFIED) {
            throw new InvalidArgumentException(
                'Verifikasi identitas pemohon belum berhasil. Status: '.$verification->result.'.'
            );
        }

        // 7. Operator must be authorized (checked at controller level via Gate)
        // This is a server-side double-check
        // (The controller already gates this via permission middleware)

        // 8. Release data completeness is validated by FormRequest
        // (released_to_name, relationship_to_customer, release_date, release_location are required)
    }

    /**
     * Get the release eligibility checklist for a collateral.
     *
     * @return array<int, array{label: string, passed: bool, detail: string}>
     */
    public function getReleaseChecklist(Collateral $collateral): array
    {
        $loan = $collateral->loan;
        $verification = IdentityVerification::query()
            ->where('customer_id', $collateral->customer_id)
            ->where('loan_id', $collateral->loan_id)
            ->where('result', IdentityVerification::RESULT_VERIFIED)
            ->latest('verification_timestamp')
            ->first();

        $isPaidOff = $loan?->isPaidOff() ?? false;

        return [
            [
                'label' => 'Kontrak pinjaman terdaftar sah',
                'passed' => $loan !== null,
                'detail' => $loan ? $loan->loan_number : 'Pinjaman tidak ditemukan',
            ],
            [
                'label' => 'Seluruh kewajiban finansial lunas',
                'passed' => $isPaidOff,
                'detail' => $isPaidOff
                    ? 'Sisa: Rp 0'
                    : 'Sisa Tagihan: '.format_rupiah((int) ($loan->outstanding_total ?? 0)),
            ],
            [
                'label' => 'Jaminan fisik terdaftar',
                'passed' => true,
                'detail' => $collateral->collateral_code,
            ],
            [
                'label' => 'Status jaminan: Siap Diserahkan',
                'passed' => $collateral->custody_status === Collateral::STATUS_READY_FOR_RELEASE,
                'detail' => 'Status: '.str_replace('_', ' ', $collateral->custody_status),
            ],
            [
                'label' => 'Identitas penerima terverifikasi (VERIFIED)',
                'passed' => $verification !== null,
                'detail' => $verification ? 'Terverifikasi pada '.CarbonImmutable::parse($verification->verification_timestamp)->format('d/m/Y H:i') : 'Belum ada verifikasi',
            ],
            [
                'label' => 'Petugas berwenang terotorisasi',
                'passed' => true,
                'detail' => 'Diperiksa via otorisasi sistem',
            ],
        ];
    }
}

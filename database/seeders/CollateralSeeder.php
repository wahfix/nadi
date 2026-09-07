<?php

namespace Database\Seeders;

use App\Models\Collateral;
use App\Models\IdentityVerification;
use App\Models\Loan;
use App\Models\User;
use App\Services\CollateralReleaseService;
use App\Services\CollateralService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CollateralSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed collateral data: receive jaminan for active/completed loans, verify
     * identities for completed loans, and mark some ready for release.
     */
    public function run(
        CollateralService $collateralService,
        CollateralReleaseService $collateralReleaseService,
    ): void {
        $collateralOfficer = User::where('email', 'collateral@example.test')->firstOrFail();
        $verifier = User::where('email', 'verifier@example.test')->firstOrFail();
        $admin = User::where('email', 'admin@example.test')->firstOrFail();

        $activeLoans = Loan::query()
            ->with('customer')
            ->whereIn('status', [Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE])
            ->get();

        $completedLoan = Loan::query()
            ->with('customer')
            ->where('status', Loan::STATUS_COMPLETED)
            ->first();

        // --- Penerimaan jaminan untuk pinjaman aktif ---
        $activeCollaterals = [
            [
                'type' => Collateral::TYPE_VEHICLE,
                'desc' => 'Honda Beat Sporty 2023, warna merah, plat B 1234 ABC',
                'ident' => 'B 1234 ABC',
                'value' => 15_000_000,
                'condition' => 'Kondisi baik, tidak ada lecet, surat-surat lengkap',
                'location' => 'Brankas A-01',
            ],
            [
                'type' => Collateral::TYPE_DOCUMENT,
                'desc' => 'Sertifikat Hak Milik Tanah, Luas 120m², Kelurahan Sukamaju',
                'ident' => 'SHM-2020-123456',
                'value' => 250_000_000,
                'condition' => 'Dokumen asli dalam amplop tertutup, kondisi prima',
                'location' => 'Brankas B-03',
            ],
        ];

        foreach ($activeLoans as $index => $loan) {
            $spec = $activeCollaterals[$index % count($activeCollaterals)];

            $collateralService->receiveCollateral($loan, [
                'collateral_type' => $spec['type'],
                'description' => $spec['desc'],
                'identification_number' => $spec['ident'],
                'estimated_value' => $spec['value'],
                'received_date' => now()->subDays(5)->toDateString(),
                'condition_on_receipt' => $spec['condition'],
                'storage_location' => $spec['location'],
                'received_by' => $collateralOfficer->id,
            ]);
        }

        // Tandai satu jaminan dari pinjaman aktif sebagai SIAP DISERAHKAN agar
        // alur pengambilan dapat didemonstrasikan (release akan diblokir karena
        // pinjaman belum lunas).
        DB::table('collaterals')
            ->where('id', $activeLoans->first()->collaterals()->first()->id)
            ->update(['custody_status' => Collateral::STATUS_IN_CUSTODY]);

        DB::table('collaterals')
            ->where('id', $activeLoans->first()->collaterals()->first()->id)
            ->update(['custody_status' => Collateral::STATUS_READY_FOR_RELEASE]);

        // Verifikasi identitas untuk pinjaman aktif (untuk demonstrasi blokade release)
        if ($activeLoan = $activeLoans->first()) {
            $collateralReleaseService->verifyIdentity($activeLoan, [
                'verification_method' => IdentityVerification::METHOD_GOVERNMENT_ID,
                'verified_name' => $activeLoan->customer->full_name,
                'verified_id_number' => $activeLoan->customer->national_id_number,
                'result' => IdentityVerification::RESULT_VERIFIED,
                'verifier_id' => $verifier->id,
                'notes' => 'Verifikasi identitas untuk demonstrasi blokade pengambilan (data demo).',
            ]);
        }

        // --- Pinjaman lunas: terima jaminan, verifikasi identitas, dan tandai siap release ---
        if ($completedLoan) {
            $collateral = $collateralService->receiveCollateral($completedLoan, [
                'collateral_type' => Collateral::TYPE_ELECTRONIC,
                'description' => 'MacBook Air M2 2023, RAM 8GB, 256GB SSD, Space Gray',
                'identification_number' => 'C02Z1234HKDD',
                'estimated_value' => 14_000_000,
                'received_date' => now()->subMonths(3)->toDateString(),
                'condition_on_receipt' => 'Kondisi sangat baik, ada dus dan charger original',
                'storage_location' => 'Brankas C-02',
                'received_by' => $collateralOfficer->id,
            ]);

            // Pindahkan ke IN_CUSTODY lalu READY_FOR_RELEASE
            DB::table('collaterals')
                ->where('id', $collateral->id)
                ->update(['custody_status' => Collateral::STATUS_IN_CUSTODY]);

            DB::table('collaterals')
                ->where('id', $collateral->id)
                ->update(['custody_status' => Collateral::STATUS_READY_FOR_RELEASE]);

            // Verifikasi identitas untuk pinjaman lunas
            $verification = $collateralReleaseService->verifyIdentity($completedLoan, [
                'verification_method' => IdentityVerification::METHOD_GOVERNMENT_ID,
                'verified_name' => $completedLoan->customer->full_name,
                'verified_id_number' => $completedLoan->customer->national_id_number,
                'result' => IdentityVerification::RESULT_VERIFIED,
                'verifier_id' => $verifier->id,
            ]);

            // Eksekusi pelepasan jaminan
            $collateralReleaseService->executeRelease(
                $collateral->fresh(),
                $verification->fresh(),
                [
                    'released_to_name' => $completedLoan->customer->full_name,
                    'relationship_to_customer' => 'Pemilik (Nasabah)',
                    'release_date' => now()->subDays(2)->toDateString(),
                    'release_location' => 'Kantor NADI, Lantai 1',
                    'released_by' => $collateralOfficer->id,
                    'witness_id' => $admin->id,
                    'handover_notes' => 'Jaminan diserahkan langsung kepada nasabah (data demo).',
                ],
            );
        }
    }
}

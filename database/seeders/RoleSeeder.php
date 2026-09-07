<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => Role::ADMIN,
                'display_name' => 'Administrator',
                'description' => 'Akses penuh ke seluruh modul sistem dan persetujuan pinjaman.',
            ],
            [
                'name' => Role::LO,
                'display_name' => 'Loan Officer',
                'description' => 'Input data nasabah dan draft pengajuan pinjaman.',
            ],
            [
                'name' => Role::LC,
                'display_name' => 'Loan Collector',
                'description' => 'Pencatatan penagihan, janji bayar, dan monitoring tunggakan.',
            ],
            [
                'name' => Role::CASHIER,
                'display_name' => 'Kasir',
                'description' => 'Penerimaan dan pencatatan pembayaran angsuran serta cetak kuitansi.',
            ],
            [
                'name' => Role::COLLATERAL_OFFICER,
                'display_name' => 'Petugas Agunan',
                'description' => 'Penerimaan, penyimpanan, dan serah terima fisik jaminan.',
            ],
            [
                'name' => Role::IDENTITY_VERIFIER,
                'display_name' => 'Petugas Verifikasi Identitas',
                'description' => 'Verifikasi keabsahan identitas pemohon pengambilan jaminan.',
            ],
            [
                'name' => Role::AUDITOR,
                'display_name' => 'Auditor',
                'description' => 'Akses baca seluruh modul, jejak audit, dan laporan sistem.',
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['name' => $role['name']],
                $role
            );
        }
    }
}

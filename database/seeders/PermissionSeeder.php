<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Customers
            ['name' => 'customers.view', 'display_name' => 'Lihat Nasabah', 'description' => 'Melihat daftar dan profil nasabah'],
            ['name' => 'customers.create', 'display_name' => 'Tambah Nasabah', 'description' => 'Mendaftarkan nasabah baru'],
            ['name' => 'customers.edit', 'display_name' => 'Ubah Nasabah', 'description' => 'Mengubah data profil nasabah'],
            ['name' => 'customers.delete', 'display_name' => 'Hapus Nasabah', 'description' => 'Menghapus nasabah'],

            // Loans
            ['name' => 'loans.view', 'display_name' => 'Lihat Pinjaman', 'description' => 'Melihat daftar dan detail pinjaman'],
            ['name' => 'loans.create', 'display_name' => 'Pengajuan Pinjaman', 'description' => 'Membuat draft pengajuan pinjaman'],
            ['name' => 'loans.edit', 'display_name' => 'Ubah Pengajuan', 'description' => 'Mengubah draft pengajuan pinjaman'],
            ['name' => 'loans.review', 'display_name' => 'Review Pinjaman', 'description' => 'Melakukan review pengajuan pinjaman'],
            ['name' => 'loans.approve', 'display_name' => 'Persetujuan Pinjaman', 'description' => 'Menyetujui atau menolak pinjaman'],
            ['name' => 'loans.disburse', 'display_name' => 'Pencairan Pinjaman', 'description' => 'Mencairkan pinjaman yang disetujui'],

            // Installments
            ['name' => 'installments.view', 'display_name' => 'Lihat Jadwal Angsuran', 'description' => 'Melihat jadwal dan status angsuran'],

            // Payments
            ['name' => 'payments.view', 'display_name' => 'Lihat Pembayaran', 'description' => 'Melihat riwayat pembayaran'],
            ['name' => 'payments.create', 'display_name' => 'Terima Pembayaran', 'description' => 'Mencatat transaksi pembayaran kasir'],
            ['name' => 'payments.reverse', 'display_name' => 'Pembalikan Pembayaran', 'description' => 'Melakukan reversal atas kekeliruan pembayaran'],
            ['name' => 'payments.receipt', 'display_name' => 'Cetak Kuitansi', 'description' => 'Mencetak kuitansi tanda terima pembayaran'],

            // Collections
            ['name' => 'collections.view', 'display_name' => 'Lihat Penagihan', 'description' => 'Melihat aktivitas penagihan dan tunggakan'],
            ['name' => 'collections.create', 'display_name' => 'Catat Penagihan', 'description' => 'Mencatat hasil kontak dan janji bayar debitur'],

            // Collaterals
            ['name' => 'collaterals.view', 'display_name' => 'Lihat Agunan', 'description' => 'Melihat daftar dan status agunan'],
            ['name' => 'collaterals.receive', 'display_name' => 'Terima Agunan', 'description' => 'Menerima agunan fisik baru'],
            ['name' => 'collaterals.update_custody', 'display_name' => 'Update Penyimpanan', 'description' => 'Memperbarui lokasi dan status fisik agunan'],
            ['name' => 'collaterals.release', 'display_name' => 'Pelepasan Agunan', 'description' => 'Menyerahkan kembali agunan yang lunas'],

            // Identity Verification
            ['name' => 'verifications.view', 'display_name' => 'Lihat Verifikasi', 'description' => 'Melihat riwayat verifikasi identitas'],
            ['name' => 'verifications.create', 'display_name' => 'Lakukan Verifikasi', 'description' => 'Melakukan verifikasi identitas pemohon'],
            ['name' => 'verifications.review', 'display_name' => 'Review Verifikasi', 'description' => 'Menolak atau menyetujui hasil verifikasi'],

            // Collateral Release Handover
            ['name' => 'releases.view', 'display_name' => 'Lihat Pengambilan Jaminan', 'description' => 'Melihat data berita acara serah terima'],
            ['name' => 'releases.execute', 'display_name' => 'Eksekusi Pengambilan', 'description' => 'Melakukan serah terima fisik jaminan'],

            // Audit Logs
            ['name' => 'audit_logs.view', 'display_name' => 'Lihat Audit Log', 'description' => 'Melihat jejak audit dan JSON diff'],

            // Reports
            ['name' => 'reports.view', 'display_name' => 'Lihat Laporan', 'description' => 'Mengakses 10 modul laporan sistem'],

            // Users & RBAC
            ['name' => 'users.view', 'display_name' => 'Lihat Pengguna', 'description' => 'Melihat daftar akun staf'],
            ['name' => 'users.manage', 'display_name' => 'Kelola Pengguna', 'description' => 'Menambah dan mengatur akun serta peran pengguna'],

            // System Settings
            ['name' => 'settings.view', 'display_name' => 'Lihat Pengaturan', 'description' => 'Melihat konfigurasi sistem'],
            ['name' => 'settings.manage', 'display_name' => 'Ubah Pengaturan', 'description' => 'Mengubah konfigurasi sistem'],
        ];

        foreach ($permissions as $permData) {
            Permission::updateOrCreate(['name' => $permData['name']], $permData);
        }

        // Role-Permission mapping
        $rolePermissions = [
            Role::LO => [
                'customers.view',
                'customers.create',
                'customers.edit',
                'loans.view',
                'loans.create',
                'loans.edit',
                'installments.view',
            ],
            Role::LC => [
                'customers.view',
                'loans.view',
                'installments.view',
                'payments.view',
                'collections.view',
                'collections.create',
            ],
            Role::CASHIER => [
                'installments.view',
                'payments.view',
                'payments.create',
                'payments.receipt',
            ],
            Role::COLLATERAL_OFFICER => [
                'collaterals.view',
                'collaterals.receive',
                'collaterals.update_custody',
                'collaterals.release',
                'releases.view',
                'releases.execute',
            ],
            Role::IDENTITY_VERIFIER => [
                'verifications.view',
                'verifications.create',
                'verifications.review',
            ],
            Role::AUDITOR => [
                'customers.view',
                'loans.view',
                'installments.view',
                'payments.view',
                'collections.view',
                'collaterals.view',
                'verifications.view',
                'releases.view',
                'audit_logs.view',
                'reports.view',
            ],
        ];

        // Attach permissions to non-admin roles
        foreach ($rolePermissions as $roleName => $permNames) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $permIds = Permission::whereIn('name', $permNames)->pluck('id');
                $role->permissions()->sync($permIds);
            }
        }

        // Admin gets all permissions attached as well
        $adminRole = Role::where('name', Role::ADMIN)->first();
        if ($adminRole) {
            $allPermIds = Permission::pluck('id');
            $adminRole->permissions()->sync($allPermIds);
        }
    }
}

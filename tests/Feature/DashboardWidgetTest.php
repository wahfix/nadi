<?php

use App\Models\User;
use Database\Seeders\CollateralSeeder;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\LoanSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;

beforeEach(function () {
    $this->seed([
        RoleSeeder::class,
        PermissionSeeder::class,
        UserSeeder::class,
        DemoDataSeeder::class,
        LoanSeeder::class,
        CollateralSeeder::class,
    ]);
});

test('admin dashboard shows portfolio, collection, and collateral widgets', function () {
    $admin = demoUser('admin@example.test');

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Total Nasabah')
        ->assertSee('Pinjaman Aktif')
        ->assertSee('Outstanding Pokok')
        ->assertSee('Jatuh Tempo Hari Ini')
        ->assertSee('Menunggak')
        ->assertSee('Agunan Siap Diambil')
        ->assertSee('Aktivitas Terbaru');
});

test('loan officer dashboard shows pipeline and quick actions', function () {
    $lo = demoUser('lo@example.test');

    $this->actingAs($lo)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Pinjaman Anda')
        ->assertSee('Sedang Direview')
        ->assertSee('Daftarkan Nasabah Baru')
        ->assertSee('Buat Pengajuan Pinjaman');
});

test('loan collector dashboard shows due and overdue installments', function () {
    $lc = demoUser('lc@example.test');

    $this->actingAs($lc)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Nasabah dalam Penanganan')
        ->assertSee('Sisa Tagihan Berjalan')
        ->assertSee('Jatuh Tempo Hari Ini')
        ->assertSee('Janji Bayar Mendatang')
        ->assertSee('Tunggakan (Menunggak)');
});

test('cashier dashboard shows today collection summary', function () {
    $cashier = demoUser('cashier@example.test');

    $this->actingAs($cashier)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Penerimaan Kas Hari Ini')
        ->assertSee('Pembayaran Terbaru')
        ->assertSee('Catat Pembayaran');
});

test('collateral officer dashboard shows custody and release widgets', function () {
    $collateral = demoUser('collateral@example.test');

    $this->actingAs($collateral)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Dalam Penyimpanan')
        ->assertSee('Siap Diserahkan')
        ->assertSee('Serah Terima Jaminan Terakhir');
});

test('identity verifier dashboard shows verification queue', function () {
    $verifier = demoUser('verifier@example.test');

    $this->actingAs($verifier)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Antrean Perlu Review')
        ->assertSee('Verifikasi Berhasil')
        ->assertSee('Verifikasi Gagal')
        ->assertSee('Riwayat Verifikasi Terbaru');
});

test('auditor dashboard shows audit trend and recent mutations', function () {
    $auditor = demoUser('auditor@example.test');

    $this->actingAs($auditor)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Event Audit Hari Ini')
        ->assertSee('Tren Aktivitas 7 Hari Terakhir')
        ->assertSee('Mutasi Audit Terbaru');
});

test('authenticated user without recognized role still sees dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});

<?php

use App\Models\Collateral;
use App\Models\CollateralRelease;
use App\Models\IdentityVerification;
use App\Models\Loan;
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

test('collateral index page loads for authorized users', function () {
    $collateral = demoUser('collateral@example.test');
    $this->actingAs($collateral)
        ->get(route('collaterals.index'))
        ->assertOk()
        ->assertSee('Jaminan');
});

test('collateral index page is forbidden for unauthorized users', function () {
    $lo = demoUser('lo@example.test');
    $this->actingAs($lo)
        ->get(route('collaterals.index'))
        ->assertForbidden();
});

test('receive collateral form loads for authorized users', function () {
    $admin = demoUser('admin@example.test');
    $this->actingAs($admin)
        ->get(route('collaterals.create'))
        ->assertOk()
        ->assertSee('Terima Jaminan');
});

test('receive collateral form is forbidden for unauthorized users', function () {
    $lo = demoUser('lo@example.test');
    $this->actingAs($lo)
        ->get(route('collaterals.create'))
        ->assertForbidden();
});

test('collateral receive is validated server-side', function () {
    $admin = demoUser('admin@example.test');

    $this->actingAs($admin)
        ->from(route('collaterals.create'))
        ->post(route('collaterals.store'), [])
        ->assertRedirect(route('collaterals.create'))
        ->assertSessionHasErrors([
            'loan_id',
            'collateral_type',
            'description',
            'identification_number',
            'estimated_value',
            'received_date',
            'condition_on_receipt',
            'storage_location',
        ]);
});

test('collateral receive creates a new collateral with audit trail', function () {
    $admin = demoUser('admin@example.test');
    $loan = Loan::query()->where('status', Loan::STATUS_ACTIVE)->firstOrFail();

    $this->actingAs($admin)
        ->post(route('collaterals.store'), [
            'loan_id' => $loan->id,
            'collateral_type' => Collateral::TYPE_DOCUMENT,
            'description' => 'Dokumen jaminan uji coba (data demo)',
            'identification_number' => 'TEST-COL-001',
            'estimated_value' => 5_000_000,
            'received_date' => now()->toDateString(),
            'condition_on_receipt' => 'Kondisi baik',
            'storage_location' => 'Rak Uji Coba',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('collaterals', [
        'loan_id' => $loan->id,
        'collateral_type' => Collateral::TYPE_DOCUMENT,
        'identification_number' => 'TEST-COL-001',
        'estimated_value' => 5_000_000,
        'custody_status' => Collateral::STATUS_RECEIVED,
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'COLLATERAL_RECEIVED',
        'entity_type' => Collateral::class,
    ]);
});

test('collateral show page loads correctly', function () {
    $collateral = Collateral::firstOrFail();
    $officer = demoUser('collateral@example.test');

    $this->actingAs($officer)
        ->get(route('collaterals.show', $collateral))
        ->assertOk()
        ->assertSee($collateral->collateral_code);
});

test('custody status update works for valid transitions', function () {
    $admin = demoUser('admin@example.test');
    $collateral = Collateral::where('custody_status', Collateral::STATUS_RECEIVED)->firstOrFail();

    $this->actingAs($admin)
        ->post(route('collaterals.update-custody', $collateral), [
            'custody_status' => Collateral::STATUS_IN_CUSTODY,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('collaterals', [
        'id' => $collateral->id,
        'custody_status' => Collateral::STATUS_IN_CUSTODY,
    ]);
});

test('custody status update rejects invalid transitions', function () {
    $admin = demoUser('admin@example.test');
    $collateral = Collateral::where('custody_status', Collateral::STATUS_RECEIVED)->firstOrFail();

    $this->actingAs($admin)
        ->from(route('collaterals.show', $collateral))
        ->post(route('collaterals.update-custody', $collateral), [
            'custody_status' => Collateral::STATUS_RELEASED,
        ])
        ->assertRedirect(route('collaterals.show', $collateral))
        ->assertSessionHasErrors('custody_status');

    $this->assertDatabaseHas('collaterals', [
        'id' => $collateral->id,
        'custody_status' => Collateral::STATUS_RECEIVED,
    ]);
});

test('verification create form loads for authorized users', function () {
    $verifier = demoUser('verifier@example.test');
    $this->actingAs($verifier)
        ->get(route('verifications.create'))
        ->assertOk()
        ->assertSee('Buat Verifikasi');
});

test('verification is validated server-side', function () {
    $verifier = demoUser('verifier@example.test');

    $this->actingAs($verifier)
        ->from(route('verifications.create'))
        ->post(route('verifications.store'), [])
        ->assertRedirect(route('verifications.create'))
        ->assertSessionHasErrors([
            'loan_id',
            'verification_method',
            'verified_name',
            'verified_id_number',
            'result',
        ]);
});

test('verification store creates a new verification record', function () {
    $verifier = demoUser('verifier@example.test');
    $loan = Loan::query()->with('customer')->firstOrFail();

    $this->actingAs($verifier)
        ->post(route('verifications.store'), [
            'loan_id' => $loan->id,
            'verification_method' => IdentityVerification::METHOD_GOVERNMENT_ID,
            'verified_name' => $loan->customer->full_name,
            'verified_id_number' => $loan->customer->national_id_number,
            'result' => IdentityVerification::RESULT_VERIFIED,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('identity_verifications', [
        'customer_id' => $loan->customer_id,
        'loan_id' => $loan->id,
        'verification_method' => IdentityVerification::METHOD_GOVERNMENT_ID,
        'result' => IdentityVerification::RESULT_VERIFIED,
    ]);
});

test('verification index page loads correctly', function () {
    $verifier = demoUser('verifier@example.test');
    $this->actingAs($verifier)
        ->get(route('verifications.index'))
        ->assertOk()
        ->assertSee('Verifikasi Identitas');
});

test('release create form loads with checklist', function () {
    $admin = demoUser('admin@example.test');
    $readyCollateral = Collateral::where('custody_status', Collateral::STATUS_READY_FOR_RELEASE)
        ->whereDoesntHave('release')
        ->first();

    $this->actingAs($admin)
        ->get(route('releases.create'))
        ->assertOk()
        ->assertSee('Pilih jaminan terlebih dahulu');

    if ($readyCollateral !== null) {
        $this->actingAs($admin)
            ->get(route('releases.create', ['collateral' => $readyCollateral->id]))
            ->assertOk()
            ->assertSee('Daftar Periksa')
            ->assertSee($readyCollateral->collateral_code);
    } else {
        $this->markTestSkipped('No collateral in READY_FOR_RELEASE status available.');
    }
});

test('release is validated server-side', function () {
    $admin = demoUser('admin@example.test');

    $this->actingAs($admin)
        ->from(route('releases.create'))
        ->post(route('releases.store'), [])
        ->assertRedirect(route('releases.create'))
        ->assertSessionHasErrors([
            'collateral_id',
            'identity_verification_id',
            'released_to_name',
            'relationship_to_customer',
            'release_date',
            'release_location',
        ]);
});

test('release index page loads correctly', function () {
    $admin = demoUser('admin@example.test');
    $this->actingAs($admin)
        ->get(route('releases.index'))
        ->assertOk()
        ->assertSee('Pengambilan Jaminan');
});

test('release receipt page loads for a completed release', function () {
    $admin = demoUser('admin@example.test');
    $release = CollateralRelease::firstOrFail();

    $this->actingAs($admin)
        ->get(route('releases.receipt', $release))
        ->assertOk()
        ->assertSee('Berita Acara Serah Terima Jaminan')
        ->assertSee($release->release_number)
        ->assertSee($release->released_to_name);
});

test('release blocks when loan is not paid off', function () {
    $admin = demoUser('admin@example.test');
    $activeCollateral = Collateral::where('custody_status', Collateral::STATUS_READY_FOR_RELEASE)
        ->whereHas('loan', fn ($q) => $q->where('status', Loan::STATUS_ACTIVE))
        ->first();

    if ($activeCollateral === null) {
        $this->markTestSkipped('No active collateral in READY_FOR_RELEASE status available.');
    }

    $verification = IdentityVerification::where('customer_id', $activeCollateral->customer_id)
        ->where('result', IdentityVerification::RESULT_VERIFIED)
        ->first();

    if ($verification === null) {
        $this->markTestSkipped('No verified identity available for this collateral.');
    }

    $this->actingAs($admin)
        ->post(route('releases.store'), [
            'collateral_id' => $activeCollateral->id,
            'identity_verification_id' => $verification->id,
            'released_to_name' => 'Penguji Coba',
            'relationship_to_customer' => 'Lainnya',
            'release_date' => now()->toDateString(),
            'release_location' => 'Kantor Uji Coba',
        ])
        ->assertRedirect()
        ->assertSessionHas('error');
});

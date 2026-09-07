<?php

use App\Models\IdentityVerification;
use App\Models\Loan;
use App\Models\User;
use App\Services\CollateralReleaseService;
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
    ]);
});

function verificationDemoUser(string $email): User
{
    return User::where('email', $email)->firstOrFail();
}

function verificationDemoLoan(): Loan
{
    return Loan::query()->whereIn('status', [
        Loan::STATUS_ACTIVE,
        Loan::STATUS_OVERDUE,
        Loan::STATUS_COMPLETED,
    ])->firstOrFail();
}

function verificationPayload(array $overrides = []): array
{
    $loan = verificationDemoLoan();

    return array_merge([
        'loan_id' => $loan->id,
        'verification_method' => IdentityVerification::METHOD_GOVERNMENT_ID,
        'verified_name' => $loan->customer->full_name,
        'verified_id_number' => $loan->customer->national_id_number,
        'result' => IdentityVerification::RESULT_VERIFIED,
        'notes' => 'Pencocokan data fisik nasabah berhasil (data sintetis).',
    ], $overrides);
}

test('guests are redirected to login on verification module pages', function () {
    $this->get(route('verifications.index'))->assertRedirect(route('login'));
    $this->get(route('verifications.create'))->assertRedirect(route('login'));
});

test('only roles with the verification view permission can access the list', function () {
    $this->actingAs(verificationDemoUser('verifier@example.test'))->get(route('verifications.index'))->assertOk();
    $this->actingAs(verificationDemoUser('auditor@example.test'))->get(route('verifications.index'))->assertOk();

    $this->actingAs(verificationDemoUser('cashier@example.test'))->get(route('verifications.index'))->assertForbidden();
});

test('only the verifier role can open the verification create form', function () {
    $this->actingAs(verificationDemoUser('verifier@example.test'))->get(route('verifications.create'))->assertOk()->assertSee('Buat Verifikasi');

    $this->actingAs(verificationDemoUser('lo@example.test'))->get(route('verifications.create'))->assertForbidden();
});

test('a successful verification is recorded with VERIFIED status and audit trail', function () {
    $verifier = verificationDemoUser('verifier@example.test');
    $loan = verificationDemoLoan();

    $this->actingAs($verifier)
        ->post(route('verifications.store'), verificationPayload())
        ->assertRedirect();

    $this->assertDatabaseHas('identity_verifications', [
        'customer_id' => $loan->customer_id,
        'loan_id' => $loan->id,
        'verification_method' => IdentityVerification::METHOD_GOVERNMENT_ID,
        'result' => IdentityVerification::RESULT_VERIFIED,
        'verifier_id' => $verifier->id,
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'IDENTITY_VERIFIED',
        'entity_type' => IdentityVerification::class,
        'user_id' => $verifier->id,
    ]);
});

test('a failed verification is recorded with FAILED status and audit trail', function () {
    $verifier = verificationDemoUser('verifier@example.test');
    $loan = verificationDemoLoan();

    $this->actingAs($verifier)
        ->post(route('verifications.store'), verificationPayload([
            'result' => IdentityVerification::RESULT_FAILED,
            'notes' => 'Dokumen identitas tidak sesuai dengan profil (data sintetis).',
        ]))
        ->assertRedirect();

    $this->assertDatabaseHas('identity_verifications', [
        'customer_id' => $loan->customer_id,
        'loan_id' => $loan->id,
        'result' => IdentityVerification::RESULT_FAILED,
        'verifier_id' => $verifier->id,
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'IDENTITY_FAILED',
        'entity_type' => IdentityVerification::class,
        'user_id' => $verifier->id,
    ]);
});

test('a requires-review verification is recorded for manual follow-up', function () {
    $verifier = verificationDemoUser('verifier@example.test');
    $loan = verificationDemoLoan();

    $this->actingAs($verifier)
        ->post(route('verifications.store'), verificationPayload([
            'result' => IdentityVerification::RESULT_REQUIRES_REVIEW,
            'notes' => 'Nama yang diucapkan berbeda ejaan, perlu pengecekan manual (data sintetis).',
        ]))
        ->assertRedirect();

    $this->assertDatabaseHas('identity_verifications', [
        'customer_id' => $loan->customer_id,
        'loan_id' => $loan->id,
        'result' => IdentityVerification::RESULT_REQUIRES_REVIEW,
        'verifier_id' => $verifier->id,
    ]);
});

test('a non-verified result cannot be used as release eligibility proof', function () {
    $loan = verificationDemoLoan();
    $verifier = verificationDemoUser('verifier@example.test');

    $failed = app(CollateralReleaseService::class)->verifyIdentity($loan, [
        'verification_method' => IdentityVerification::METHOD_MANUAL_CHECK,
        'verified_name' => $loan->customer->full_name,
        'verified_id_number' => $loan->customer->national_id_number,
        'result' => IdentityVerification::RESULT_REQUIRES_REVIEW,
        'verifier_id' => $verifier->id,
    ]);

    expect($failed->result)->toBe(IdentityVerification::RESULT_REQUIRES_REVIEW);
    expect(IdentityVerification::query()
        ->where('loan_id', $loan->id)
        ->where('result', IdentityVerification::RESULT_VERIFIED)
        ->count())->toBe(0);
});

test('verification store rejects invalid result values server-side', function () {
    $verifier = verificationDemoUser('verifier@example.test');

    $this->actingAs($verifier)
        ->from(route('verifications.create'))
        ->post(route('verifications.store'), verificationPayload(['result' => 'APPROVED']))
        ->assertRedirect(route('verifications.create'))
        ->assertSessionHasErrors(['result']);

    $this->assertDatabaseCount('identity_verifications', 0);
});

test('verification store is forbidden for unauthorized roles', function () {
    $cashier = verificationDemoUser('cashier@example.test');

    $this->actingAs($cashier)
        ->post(route('verifications.store'), verificationPayload())
        ->assertForbidden();

    $this->assertDatabaseCount('identity_verifications', 0);
});

test('verification detail and print pages load for view permission holders', function () {
    $verifier = verificationDemoUser('verifier@example.test');
    $this->actingAs($verifier)->post(route('verifications.store'), verificationPayload())->assertRedirect();

    $verification = IdentityVerification::latest('id')->firstOrFail();

    $this->actingAs($verifier)
        ->get(route('verifications.show', $verification))
        ->assertOk()
        ->assertSee($verification->verified_name);

    $this->actingAs($verifier)
        ->get(route('verifications.print', $verification))
        ->assertOk()
        ->assertSee('Hasil Verifikasi Identitas');
});

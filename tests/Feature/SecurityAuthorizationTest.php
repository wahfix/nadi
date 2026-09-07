<?php

use App\Models\Collateral;
use App\Models\CollateralRelease;
use App\Models\Customer;
use App\Models\Loan;
use App\Models\Payment;
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

function securityUser(string $email): User
{
    return User::where('email', $email)->firstOrFail();
}

function submittedLoan(): Loan
{
    return Loan::query()
        ->where('status', Loan::STATUS_SUBMITTED)
        ->orWhere('status', Loan::STATUS_UNDER_REVIEW)
        ->firstOrFail();
}

test('LC cannot approve a loan — HTTP 403 with status unchanged', function () {
    $lc = securityUser('lc@example.test');
    $loan = submittedLoan();

    $this->actingAs($lc)
        ->post(route('loans.approve', $loan))
        ->assertForbidden();

    expect($loan->fresh()->status)->toBe($loan->status);
    expect($loan->fresh()->approved_by)->toBeNull();
});

test('Cashier cannot execute a collateral release — HTTP 403', function () {
    $cashier = securityUser('cashier@example.test');
    $releasesBefore = CollateralRelease::count();

    $this->actingAs($cashier)
        ->get(route('releases.create'))
        ->assertForbidden();

    $this->actingAs($cashier)
        ->post(route('releases.store'), [])
        ->assertForbidden();

    expect(CollateralRelease::count())->toBe($releasesBefore);
});

test('Auditor cannot modify or add data in any module — HTTP 403', function () {
    $auditor = securityUser('auditor@example.test');
    $loan = submittedLoan();
    $payment = Payment::query()->first();
    $paymentsBefore = Payment::count();
    $customersBefore = Customer::count();

    $this->actingAs($auditor);

    $this->post(route('customers.store'), [])->assertForbidden();
    $this->post(route('loans.store'), [])->assertForbidden();
    $this->post(route('payments.store'), [])->assertForbidden();
    $this->post(route('collaterals.store'), [])->assertForbidden();
    $this->post(route('verifications.store'), [])->assertForbidden();
    $this->post(route('users.store'), [])->assertForbidden();

    // Auditor tidak dapat menjalankan mutasi status pinjaman sekalipun memegang izin lihat.
    $this->post(route('loans.submit', $loan))->assertForbidden();
    $this->post(route('loans.review', $loan))->assertForbidden();
    $this->post(route('loans.disburse', $loan))->assertForbidden();

    if ($payment) {
        $this->post(route('payments.reverse', $payment), ['reason' => 'Uji auditor (data sintetis)'])->assertForbidden();
    }

    // Tidak ada data yang berubah oleh percobaan auditor.
    expect(Payment::count())->toBe($paymentsBefore);
    expect(Customer::count())->toBe($customersBefore);
});

test('auditor still has read-only access across all reportable modules', function () {
    $auditor = securityUser('auditor@example.test');
    $this->actingAs($auditor);

    $this->get(route('dashboard'))->assertOk();
    $this->get(route('customers.index'))->assertOk();
    $this->get(route('loans.index'))->assertOk();
    $this->get(route('installments.index'))->assertOk();
    $this->get(route('payments.index'))->assertOk();
    $this->get(route('collaterals.index'))->assertOk();
    $this->get(route('verifications.index'))->assertOk();
    $this->get(route('releases.index'))->assertOk();
    $this->get(route('reports.index'))->assertOk();
    $this->get(route('audit-log.index'))->assertOk();
});

test('unauthenticated users cannot execute or even open the release flow', function () {
    $releasesBefore = CollateralRelease::count();

    $this->get(route('releases.create'))->assertRedirect(route('login'));
    $this->get(route('releases.index'))->assertRedirect(route('login'));

    $this->post(route('releases.store'), [])->assertRedirect(route('login'));

    expect(CollateralRelease::count())->toBe($releasesBefore);
});

test('Cashier cannot mark collateral ready for release', function () {
    $cashier = securityUser('cashier@example.test');
    $collateral = Collateral::query()
        ->where('custody_status', Collateral::STATUS_RECEIVED)
        ->first();

    if ($collateral === null) {
        $this->markTestSkipped('No collateral in RECEIVED status available.');
    }

    $this->actingAs($cashier)
        ->post(route('collaterals.update-custody', $collateral), [
            'custody_status' => Collateral::STATUS_IN_CUSTODY,
        ])
        ->assertForbidden();

    expect($collateral->fresh()->custody_status)->toBe(Collateral::STATUS_RECEIVED);
});

test('Loan rejection is restricted to the admin approve role', function () {
    $lc = securityUser('lc@example.test');
    $loan = submittedLoan();

    $this->actingAs($lc)
        ->post(route('loans.reject', $loan), ['reason' => 'Percobaan dari LC (data sintetis).'])
        ->assertForbidden();

    expect($loan->fresh()->status)->toBe($loan->status);
});

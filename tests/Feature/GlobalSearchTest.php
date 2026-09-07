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

function globalSearchUser(string $email): User
{
    return User::where('email', $email)->firstOrFail();
}

test('guests are redirected to login on global search pages', function () {
    $this->get(route('search.index'))->assertRedirect(route('login'));
    $this->get(route('search.results'))->assertRedirect(route('login'));
});

test('search page loads for authenticated users with the number dropdown', function () {
    $this->actingAs(globalSearchUser('admin@example.test'))
        ->get(route('search.index'))
        ->assertOk()
        ->assertSee('Pencarian Global')
        ->assertSee('Nasabah (No. CUS / Nama / Telepon / NIK)')
        ->assertSee('Pinjaman (NADI-LOAN-...)')
        ->assertSee('Pembayaran (PAY-...)')
        ->assertSee('Jaminan (COL-...)')
        ->assertSee('Pelepasan (REL-...)')
        ->assertSee('CUS-YYYY-XXXXXX')
        ->assertSee('NADI-LOAN-YYYY-XXXXXX')
        ->assertSee('REL-YYYY-XXXXXX');
});

test('search by customer code returns the matching nasabah', function () {
    $customer = Customer::firstOrFail();

    $this->actingAs(globalSearchUser('admin@example.test'))
        ->get(route('search.results', ['type' => 'customer', 'q' => $customer->customer_code]))
        ->assertOk()
        ->assertSee($customer->customer_code)
        ->assertSee($customer->full_name);
});

test('search by customer name, phone, and NIK works', function () {
    $customer = Customer::firstOrFail();
    $admin = globalSearchUser('admin@example.test');

    $this->actingAs($admin)
        ->get(route('search.results', ['type' => 'customer', 'q' => substr($customer->full_name, 0, 5)]))
        ->assertOk()
        ->assertSee($customer->full_name);

    $this->actingAs($admin)
        ->get(route('search.results', ['type' => 'customer', 'q' => $customer->phone]))
        ->assertOk()
        ->assertSee($customer->full_name);

    $this->actingAs($admin)
        ->get(route('search.results', ['type' => 'customer', 'q' => substr($customer->national_id_number, -6)]))
        ->assertOk()
        ->assertSee($customer->full_name);
});

test('search by loan number returns the matching pinjaman', function () {
    $loan = Loan::where('status', Loan::STATUS_ACTIVE)->firstOrFail();

    $this->actingAs(globalSearchUser('admin@example.test'))
        ->get(route('search.results', ['type' => 'loan', 'q' => $loan->loan_number]))
        ->assertOk()
        ->assertSee($loan->loan_number)
        ->assertSee($loan->customer->full_name);
});

test('search by payment number returns the matching pembayaran', function () {
    $payment = Payment::firstOrFail();

    $this->actingAs(globalSearchUser('admin@example.test'))
        ->get(route('search.results', ['type' => 'payment', 'q' => $payment->payment_number]))
        ->assertOk()
        ->assertSee($payment->payment_number)
        ->assertSee($payment->loan->customer->full_name);
});

test('search by collateral code returns the matching jaminan', function () {
    $collateral = Collateral::firstOrFail();

    $this->actingAs(globalSearchUser('admin@example.test'))
        ->get(route('search.results', ['type' => 'collateral', 'q' => $collateral->collateral_code]))
        ->assertOk()
        ->assertSee($collateral->collateral_code);
});

test('search by release number returns the matching pelepasan', function () {
    $release = CollateralRelease::firstOrFail();

    $this->actingAs(globalSearchUser('admin@example.test'))
        ->get(route('search.results', ['type' => 'release', 'q' => $release->release_number]))
        ->assertOk()
        ->assertSee($release->release_number)
        ->assertSee($release->collateral->collateral_code);
});

test('search shows an informative empty state when nothing matches', function () {
    $this->actingAs(globalSearchUser('admin@example.test'))
        ->get(route('search.results', ['type' => 'loan', 'q' => 'NADI-LOAN-9999-999999']))
        ->assertOk()
        ->assertSee('Tidak ada hasil ditemukan');
});

test('search results are gated by the signed-in role permissions', function () {
    // Kasir tidak memiliki loans.view → pencarian pinjaman ditolak 403.
    $cashier = globalSearchUser('cashier@example.test');

    $this->actingAs($cashier)
        ->get(route('search.results', ['type' => 'loan', 'q' => 'NADI-LOAN']))
        ->assertForbidden();

    // LO tidak memiliki payments.view → pencarian pembayaran ditolak 403.
    $lo = globalSearchUser('lo@example.test');

    $this->actingAs($lo)
        ->get(route('search.results', ['type' => 'payment', 'q' => 'PAY-']))
        ->assertForbidden();
});

test('search respects audit access by allowing permitted modules only', function () {
    // Auditor dapat mencari nasabah (customers.view) namun bukan pengguna aksi mutasi.
    $auditor = globalSearchUser('auditor@example.test');
    $customer = Customer::firstOrFail();

    $this->actingAs($auditor)
        ->get(route('search.results', ['type' => 'customer', 'q' => $customer->customer_code]))
        ->assertOk()
        ->assertSee($customer->full_name);
});

test('search input is validated server-side', function () {
    $admin = globalSearchUser('admin@example.test');

    $this->actingAs($admin)
        ->from(route('search.index'))
        ->get(route('search.results', ['type' => 'mesin', 'q' => '']))
        ->assertRedirect(route('search.index'))
        ->assertSessionHasErrors(['type', 'q']);
});

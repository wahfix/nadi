<?php

use App\Models\Installment;
use App\Models\Loan;
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

test('installment index page loads for authorized users', function () {
    $cashier = demoUser('cashier@example.test');

    $this->actingAs($cashier)
        ->get(route('installments.index'))
        ->assertOk()
        ->assertSee('Angsuran')
        ->assertSee('Jatuh Tempo');
});

test('installment index page is forbidden for unauthorized users', function () {
    $collateralOfficer = demoUser('collateral@example.test');

    $this->actingAs($collateralOfficer)
        ->get(route('installments.index'))
        ->assertForbidden();
});

test('installment index lists only installments of active and overdue loans', function () {
    $cashier = demoUser('cashier@example.test');

    $response = $this->actingAs($cashier)->get(route('installments.index'));

    $response->assertOk();

    $loanStatuses = collect($response->viewData('installments')->items())
        ->pluck('loan.status')
        ->unique()
        ->all();

    expect($loanStatuses)
        ->not->toBeEmpty()
        ->each(fn ($status) => $status->toBeIn([Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE]));
});

test('installment index filters by status', function () {
    $cashier = demoUser('cashier@example.test');

    $response = $this->actingAs($cashier)
        ->get(route('installments.index', ['status' => Installment::STATUS_OVERDUE]))
        ->assertOk();

    $items = collect($response->viewData('installments')->items());
    expect($items->count())->toBeGreaterThan(0);
    expect($items->pluck('status')->unique()->all())->toBe([Installment::STATUS_OVERDUE]);
});

test('installment index searches by customer name', function () {
    $cashier = demoUser('cashier@example.test');
    $installment = Installment::query()
        ->whereHas('loan', fn ($loan) => $loan->where('status', Loan::STATUS_ACTIVE))
        ->firstOrFail();
    $customerName = $installment->loan->customer->full_name;
    $fullNamePart = mb_substr($customerName, 0, 5);

    $response = $this->actingAs($cashier)
        ->get(route('installments.index', ['search' => $fullNamePart]))
        ->assertOk()
        ->assertSee($customerName);
});

test('installment index searches by loan number', function () {
    $cashier = demoUser('cashier@example.test');
    $installment = Installment::query()
        ->whereHas('loan', fn ($loan) => $loan->where('status', Loan::STATUS_ACTIVE))
        ->firstOrFail();
    $loanNumber = $installment->loan->loan_number;

    $response = $this->actingAs($cashier)
        ->get(route('installments.index', ['search' => $loanNumber]))
        ->assertOk()
        ->assertSee($loanNumber);
});

test('installment index shows summary cards', function () {
    $cashier = demoUser('cashier@example.test');

    $this->actingAs($cashier)
        ->get(route('installments.index'))
        ->assertOk()
        ->assertSee('Menunggak')
        ->assertSee('Jatuh Tempo Bulan Ini')
        ->assertSee('Sisa Tagihan Aktif');
});

test('installment index shows empty state when no installments match', function () {
    $cashier = demoUser('cashier@example.test');

    $this->actingAs($cashier)
        ->get(route('installments.index', ['search' => 'nomor-tidak-ada-xyz']))
        ->assertOk()
        ->assertSee('Tidak ada angsuran yang ditemukan');
});

test('installment show page loads with payment history', function () {
    $cashier = demoUser('cashier@example.test');
    $installment = Installment::withCount('payments')
        ->orderByDesc('payments_count')
        ->firstOrFail();

    $this->actingAs($cashier)
        ->get(route('installments.show', $installment))
        ->assertOk()
        ->assertSee('Rincian Tagihan')
        ->assertSee('Pembayaran Terkait');
});

test('installment show page is forbidden for unauthorized users', function () {
    $collateralOfficer = demoUser('collateral@example.test');
    $installment = Installment::query()->firstOrFail();

    $this->actingAs($collateralOfficer)
        ->get(route('installments.show', $installment))
        ->assertForbidden();
});

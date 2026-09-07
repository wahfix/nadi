<?php

use App\Models\CollectionActivity;
use App\Models\Customer;
use App\Models\Loan;
use App\Models\User;
use App\Services\CollectionService;
use App\Services\LoanService;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\LoanSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use InvalidArgumentException;

beforeEach(function () {
    $this->seed([
        RoleSeeder::class,
        PermissionSeeder::class,
        UserSeeder::class,
        DemoDataSeeder::class,
        LoanSeeder::class,
    ]);
});

function collectionDemoUser(string $email): User
{
    return User::where('email', $email)->firstOrFail();
}

/**
 * Disburse a fresh ACTIVE loan so it can be collected.
 */
function collectionActiveLoan(int $principal = 10000000): Loan
{
    $loanService = app(LoanService::class);
    $lo = collectionDemoUser('lo@example.test');
    $admin = collectionDemoUser('admin@example.test');
    $customer = Customer::where('status', Customer::STATUS_ACTIVE)->firstOrFail();

    $loan = $loanService->createDraft([
        'customer_id' => $customer->id,
        'principal_amount' => $principal,
        'interest_rate_bps' => 200,
        'interest_method' => Loan::METHOD_FLAT,
        'tenor' => 10,
        'installment_frequency' => Loan::FREQUENCY_MONTHLY,
        'first_due_date' => now()->addMonth()->toDateString(),
    ], $lo->id);

    $loan = $loanService->submitLoan($loan, $lo->id);
    $loan = $loanService->startReview($loan, $lo->id);
    $loan = $loanService->approveLoan($loan, $admin->id);
    $loan = $loanService->prepareDisbursement($loan, $admin->id);

    return $loanService->disburseLoan($loan, $admin->id);
}

test('guests are redirected to login on collection pages', function () {
    $this->get(route('collections.index'))->assertRedirect(route('login'));
    $this->get(route('collections.create'))->assertRedirect(route('login'));
});

test('only authorized roles can open the collection dashboard', function () {
    $this->actingAs(collectionDemoUser('lo@example.test'))->get(route('collections.index'))->assertForbidden();
    $this->actingAs(collectionDemoUser('cashier@example.test'))->get(route('collections.index'))->assertForbidden();
    $this->actingAs(collectionDemoUser('lc@example.test'))->get(route('collections.index'))->assertOk();
    $this->actingAs(collectionDemoUser('auditor@example.test'))->get(route('collections.index'))->assertOk();
    $this->actingAs(collectionDemoUser('admin@example.test'))->get(route('collections.index'))->assertOk();
});

test('only the collector role can open the activity form and persist activities', function () {
    $lc = collectionDemoUser('lc@example.test');
    $this->actingAs($lc)->get(route('collections.create'))->assertOk();

    $this->actingAs(collectionDemoUser('auditor@example.test'))->get(route('collections.create'))->assertForbidden();
    $this->actingAs(collectionDemoUser('lo@example.test'))->get(route('collections.create'))->assertForbidden();
});

test('recording a promise-to-pay activity locks payload, audit trail and collector', function () {
    $lc = collectionDemoUser('lc@example.test');
    $loan = collectionActiveLoan();

    $this->actingAs($lc)
        ->post(route('collections.store'), [
            'loan_id' => $loan->id,
            'contact_date' => now()->format('Y-m-d H:i'),
            'contact_method' => 'PHONE',
            'result' => 'PROMISE_TO_PAY',
            'promise_to_pay_date' => now()->addWeek()->toDateString(),
            'promise_to_pay_amount' => 500000,
            'notes' => 'Akan menyetor ke kasir pekan depan.',
        ])
        ->assertRedirect(route('loans.show', $loan));

    $activity = CollectionActivity::where('loan_id', $loan->id)->firstOrFail();

    expect($activity->customer_id)->toBe($loan->customer_id);
    expect($activity->collector_id)->toBe($lc->id);
    expect($activity->contact_method)->toBe('PHONE');
    expect($activity->result)->toBe(CollectionActivity::RESULT_PROMISE_TO_PAY);
    expect($activity->promise_to_pay_date->toDateString())->toBe(now()->addWeek()->toDateString());
    expect($activity->promise_to_pay_amount)->toBe(500000);

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'COLLECTION_CREATED',
        'entity_type' => CollectionActivity::class,
        'entity_id' => $activity->id,
        'user_id' => $lc->id,
    ]);
});

test('promise-to-pay fields are required when the result is a promise', function () {
    $loan = collectionActiveLoan();

    $this->actingAs(collectionDemoUser('lc@example.test'))
        ->from(route('collections.create'))
        ->post(route('collections.store'), [
            'loan_id' => $loan->id,
            'contact_date' => now()->format('Y-m-d H:i'),
            'contact_method' => 'WHATSAPP',
            'result' => 'PROMISE_TO_PAY',
        ])
        ->assertRedirect(route('collections.create'))
        ->assertSessionHasErrors(['promise_to_pay_date', 'promise_to_pay_amount']);
});

test('promise data is rejected for non-promise results server-side', function () {
    $lc = collectionDemoUser('lc@example.test');
    $loan = collectionActiveLoan();

    $this->actingAs($lc)
        ->from(route('collections.create'))
        ->post(route('collections.store'), [
            'loan_id' => $loan->id,
            'contact_date' => now()->format('Y-m-d H:i'),
            'contact_method' => 'IN_PERSON',
            'result' => 'NO_RESPONSE',
            'promise_to_pay_date' => now()->addWeek()->toDateString(),
            'promise_to_pay_amount' => 300000,
        ])
        ->assertRedirect(route('collections.create', ['loan' => $loan->id]))
        ->assertSessionHas('error');

    expect(CollectionActivity::where('loan_id', $loan->id)->exists())->toBeFalse();
});

test('collection activity is rejected for loans outside the collectable state', function () {
    $loanService = app(LoanService::class);
    $lo = collectionDemoUser('lo@example.test');
    $customer = Customer::where('status', Customer::STATUS_ACTIVE)->firstOrFail();

    $draft = $loanService->createDraft([
        'customer_id' => $customer->id,
        'principal_amount' => 5000000,
        'interest_rate_bps' => 200,
        'interest_method' => Loan::METHOD_FLAT,
        'tenor' => 6,
        'installment_frequency' => Loan::FREQUENCY_MONTHLY,
        'first_due_date' => now()->addMonth()->toDateString(),
    ], $lo->id);

    $this->actingAs(collectionDemoUser('lc@example.test'))
        ->from(route('collections.create'))
        ->post(route('collections.store'), [
            'loan_id' => $draft->id,
            'contact_date' => now()->format('Y-m-d H:i'),
            'contact_method' => 'PHONE',
            'result' => 'NO_RESPONSE',
        ])
        ->assertRedirect(route('collections.create', ['loan' => $draft->id]))
        ->assertSessionHas('error');

    expect(CollectionActivity::where('loan_id', $draft->id)->exists())->toBeFalse();
});

test('dashboard fences render LC metrics and the active promise list', function () {
    $service = app(CollectionService::class);
    $stats = $service->dashboardStats();

    expect($stats['total_assigned_customers'])->toBeInt();
    expect($stats['due_today'])->toBeInt();
    expect($stats['overdue'])->toBeInt();
    expect($stats['due_this_week'])->toBeInt();
    expect($stats['total_outstanding'])->toBeInt();
    expect($stats['total_outstanding'])->toBeGreaterThan(0);

    $promises = $stats['active_promises'];
    expect($promises)->toHaveCount(1);
    expect($promises->first()->promise_to_pay_amount)->toBe(1000000);

    $this->actingAs(collectionDemoUser('auditor@example.test'))
        ->get(route('collections.index'))
        ->assertOk()
        ->assertSee('Dasbor Penagihan')
        ->assertSee('Janji Bayar Aktif')
        ->assertSee('Daftar Tunggakan');
});

test('loan collector sees a list of loans overdue and unpaid installments', function () {
    $this->actingAs(collectionDemoUser('lc@example.test'))
        ->get(route('collections.index'))
        ->assertOk()
        ->assertSee('Menunggak')
        ->assertSee('Hubungi');
});

test('collection activities are immutable, only recording is possible', function () {
    expect(Route::has('collections.edit'))->toBeFalse();
    expect(Route::has('collections.update'))->toBeFalse();
    expect(Route::has('collections.destroy'))->toBeFalse();
});

test('service guards reject zero promise amounts and non-collectable loans', function () {
    $service = app(CollectionService::class);
    $loan = collectionActiveLoan();

    expect(fn () => $service->recordActivity(
        $loan,
        'PHONE',
        'PROMISE_TO_PAY',
        now()->format('Y-m-d H:i'),
        now()->addWeek()->toDateString(),
        0,
    ))->toThrow(InvalidArgumentException::class);

    expect(fn () => $service->recordActivity(
        $loan,
        'PHONE',
        'NO_RESPONSE',
        now()->format('Y-m-d H:i'),
        now()->addDay()->toDateString(),
        1_000_000,
    ))->toThrow(InvalidArgumentException::class);

    // Tidak ada aktivitas yang tertulis untuk pinjaman ini (aksi dibatalkan sebelum tersimpan).
    expect(CollectionActivity::where('loan_id', $loan->id)->exists())->toBeFalse();
});

<?php

use App\Models\Customer;
use App\Models\Loan;
use App\Models\User;
use App\Services\LoanCalculationService;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;

beforeEach(function () {
    $this->seed([
        RoleSeeder::class,
        PermissionSeeder::class,
        UserSeeder::class,
        DemoDataSeeder::class,
    ]);
});

function loanDemoUser(string $email): User
{
    return User::where('email', $email)->firstOrFail();
}

function loanPayload(array $overrides = []): array
{
    return array_merge([
        'customer_id' => Customer::where('status', Customer::STATUS_ACTIVE)->firstOrFail()->id,
        'principal_amount' => 10000000,
        'interest_rate' => '2',
        'interest_method' => 'FLAT',
        'tenor' => 10,
        'installment_frequency' => 'MONTHLY',
        'disbursement_date' => null,
        'first_due_date' => now()->addMonth()->toDateString(),
    ], $overrides);
}

test('guests are redirected to login on loan module pages', function () {
    $this->get(route('loans.index'))->assertRedirect(route('login'));
    $this->get(route('loans.create'))->assertRedirect(route('login'));
});

test('only authorized roles can access the loan list', function () {
    $lo = loanDemoUser('lo@example.test');
    $this->actingAs($lo)->get(route('loans.index'))->assertOk();

    $cashier = loanDemoUser('cashier@example.test');
    $this->actingAs($cashier)->get(route('loans.index'))->assertForbidden();
});

test('only roles with create permission can open the loan create form', function () {
    $lo = loanDemoUser('lo@example.test');
    $this->actingAs($lo)->get(route('loans.create'))->assertOk();

    $auditor = loanDemoUser('auditor@example.test');
    $this->actingAs($auditor)->get(route('loans.create'))->assertForbidden();
});

test('loan creation generates the correct sequential number and audit trail', function () {
    $lo = loanDemoUser('lo@example.test');

    $this->actingAs($lo)->post(route('loans.store'), loanPayload())->assertRedirect();

    $first = Loan::where('loan_number', 'like', 'NADI-LOAN-'.date('Y').'-%')->orderBy('id')->firstOrFail();
    expect($first->loan_number)->toMatch('/^NADI-LOAN-'.date('Y').'-000001$/');
    expect($first->status)->toBe(Loan::STATUS_DRAFT);

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'LOAN_CREATED',
        'entity_type' => Loan::class,
        'entity_id' => $first->id,
    ]);

    $this->actingAs($lo)->post(route('loans.store'), loanPayload([
        'customer_id' => Customer::where('status', Customer::STATUS_ACTIVE)->skip(1)->firstOrFail()->id,
    ]))->assertRedirect();

    $second = Loan::where('loan_number', 'like', 'NADI-LOAN-'.date('Y').'-%')->orderBy('id')->skip(1)->firstOrFail();
    expect($second->loan_number)->toMatch('/^NADI-LOAN-'.date('Y').'-000002$/');
});

test('loan creation stores flat financial values as locked integer money', function () {
    $lo = loanDemoUser('lo@example.test');

    $this->actingAs($lo)->post(route('loans.store'), loanPayload())->assertRedirect();

    $loan = Loan::latest('id')->firstOrFail();
    expect($loan->principal_amount)->toBe(10000000);
    expect($loan->interest_rate)->toBe(200);
    expect($loan->total_interest)->toBe(2000000);
    expect($loan->total_payable)->toBe(12000000);
    expect($loan->installment_amount)->toBe(1200000);
    expect($loan->outstanding_principal)->toBe(10000000);
    expect($loan->outstanding_total)->toBe(12000000);
});

test('flat interest calculation follows the official formula with integer money', function () {
    $result = app(LoanCalculationService::class)->calculate(10000000, 200, Loan::METHOD_FLAT, 10);

    expect($result['total_interest'])->toBe(2000000);
    expect($result['total_payable'])->toBe(12000000);
    expect($result['installment_amount'])->toBe(1200000);
    expect(array_sum(array_column($result['schedule_rows'], 'principal_due')))->toBe(10000000);
    expect(array_sum(array_column($result['schedule_rows'], 'interest_due')))->toBe(2000000);
});

test('reducing balance amortization uses integer arithmetic and sums perfectly', function () {
    $result = app(LoanCalculationService::class)->calculate(10000000, 100, Loan::METHOD_REDUCING_BALANCE, 12);

    $sumPrincipal = array_sum(array_column($result['schedule_rows'], 'principal_due'));
    $sumInterest = array_sum(array_column($result['schedule_rows'], 'interest_due'));

    expect($sumPrincipal)->toBe(10000000);
    expect($sumInterest)->toBe($result['total_interest']);
    expect($result['total_payable'])->toBe(10000000 + $result['total_interest']);
    expect($result['total_interest'])->toBeInt();
    expect($result['installment_amount'])->toBeInt();
});

test('reducing balance values are stored consistently via the store endpoint', function () {
    $lo = loanDemoUser('lo@example.test');

    $expected = app(LoanCalculationService::class)->calculate(10000000, 100, Loan::METHOD_REDUCING_BALANCE, 12);

    $this->actingAs($lo)->post(route('loans.store'), loanPayload([
        'interest_rate' => '1',
        'interest_method' => 'REDUCING_BALANCE',
        'tenor' => 12,
    ]))->assertRedirect();

    $loan = Loan::latest('id')->firstOrFail();
    expect($loan->interest_rate)->toBe(100);
    expect($loan->total_interest)->toBe($expected['total_interest']);
    expect($loan->installment_amount)->toBe($expected['installment_amount']);
    expect($loan->total_payable)->toBe(10000000 + $expected['total_interest']);
});

test('loan can navigate the full pipeline to disbursement and generates installments', function () {
    $lo = loanDemoUser('lo@example.test');
    $admin = loanDemoUser('admin@example.test');

    $this->actingAs($lo)->post(route('loans.store'), loanPayload())->assertRedirect();
    $loan = Loan::latest('id')->firstOrFail();

    $this->actingAs($lo)->post(route('loans.submit', $loan))->assertRedirect(route('loans.show', $loan));
    expect($loan->fresh()->status)->toBe(Loan::STATUS_SUBMITTED);

    $this->actingAs($admin)->post(route('loans.review', $loan))->assertRedirect();
    expect($loan->fresh()->status)->toBe(Loan::STATUS_UNDER_REVIEW);

    $this->actingAs($admin)->post(route('loans.approve', $loan))->assertRedirect();
    $loan->refresh();
    expect($loan->status)->toBe(Loan::STATUS_APPROVED);
    expect($loan->approved_by)->toBe($admin->id);

    $this->actingAs($admin)->post(route('loans.ready', $loan))->assertRedirect();
    expect($loan->fresh()->status)->toBe(Loan::STATUS_READY_FOR_DISBURSEMENT);

    $this->actingAs($admin)->post(route('loans.disburse', $loan))->assertRedirect();
    $loan->refresh();
    expect($loan->status)->toBe(Loan::STATUS_ACTIVE);
    expect($loan->installments()->count())->toBe(10);
    expect($loan->disbursed_at)->not->toBeNull();
    expect($loan->outstanding_total)->toBe(12000000);
});

test('status transitions are validated against the state machine', function () {
    $lo = loanDemoUser('lo@example.test');
    $admin = loanDemoUser('admin@example.test');

    $this->actingAs($lo)->post(route('loans.store'), loanPayload())->assertRedirect();
    $loan = Loan::latest('id')->firstOrFail();

    // Cannot disburse a DRAFT loan.
    $this->actingAs($admin)
        ->post(route('loans.disburse', $loan))
        ->assertRedirect(route('loans.show', $loan))
        ->assertSessionHas('error');
    expect($loan->fresh()->status)->toBe(Loan::STATUS_DRAFT);

    // Cannot approve before the loan enters UNDER_REVIEW.
    $this->actingAs($lo)->post(route('loans.submit', $loan));
    $this->actingAs($admin)
        ->post(route('loans.approve', $loan))
        ->assertRedirect(route('loans.show', $loan))
        ->assertSessionHas('error');
    expect($loan->fresh()->status)->toBe(Loan::STATUS_SUBMITTED);
});

test('loan rejection requires a mandatory reason', function () {
    $lo = loanDemoUser('lo@example.test');
    $admin = loanDemoUser('admin@example.test');

    $this->actingAs($lo)->post(route('loans.store'), loanPayload())->assertRedirect();
    $loan = Loan::latest('id')->firstOrFail();

    $this->actingAs($lo)->post(route('loans.submit', $loan));
    $this->actingAs($admin)->post(route('loans.review', $loan));

    $this->actingAs($admin)
        ->from(route('loans.show', $loan))
        ->post(route('loans.reject', $loan))
        ->assertRedirect(route('loans.show', $loan))
        ->assertSessionHasErrors(['reason']);

    expect($loan->fresh()->status)->toBe(Loan::STATUS_UNDER_REVIEW);

    $this->actingAs($admin)
        ->post(route('loans.reject', $loan), ['reason' => 'Penghasilan tidak mencukupi rasio angsuran.'])
        ->assertRedirect(route('loans.show', $loan));

    $loan->refresh();
    expect($loan->status)->toBe(Loan::STATUS_REJECTED);

    $this->assertDatabaseHas('loan_status_histories', [
        'loan_id' => $loan->id,
        'from_status' => Loan::STATUS_UNDER_REVIEW,
        'to_status' => Loan::STATUS_REJECTED,
    ]);
});

test('unauthorized roles cannot create loans through the store endpoint', function () {
    $cashier = loanDemoUser('cashier@example.test');

    $this->actingAs($cashier)
        ->post(route('loans.store'), loanPayload())
        ->assertForbidden();

    $this->assertDatabaseCount('loans', 0);
});

test('only loans.edit holders can submit a draft application', function () {
    $lo = loanDemoUser('lo@example.test');
    $lc = loanDemoUser('lc@example.test');

    $this->actingAs($lo)->post(route('loans.store'), loanPayload())->assertRedirect();
    $loan = Loan::latest('id')->firstOrFail();

    $this->actingAs($lc)
        ->post(route('loans.submit', $loan))
        ->assertForbidden();
    expect($loan->fresh()->status)->toBe(Loan::STATUS_DRAFT);

    $this->actingAs($lo)->post(route('loans.submit', $loan))->assertRedirect();
    expect($loan->fresh()->status)->toBe(Loan::STATUS_SUBMITTED);
});

test('loan creation is validated server-side', function () {
    $lo = loanDemoUser('lo@example.test');

    $this->actingAs($lo)
        ->from(route('loans.create'))
        ->post(route('loans.store'), [])
        ->assertRedirect(route('loans.create'))
        ->assertSessionHasErrors([
            'customer_id',
            'principal_amount',
            'interest_rate',
            'interest_method',
            'tenor',
            'installment_frequency',
            'first_due_date',
        ]);

    $this->assertDatabaseCount('loans', 0);
});

test('loan detail page is accessible by view permission holders', function () {
    $lo = loanDemoUser('lo@example.test');
    $this->actingAs($lo)->post(route('loans.store'), loanPayload())->assertRedirect();
    $loan = Loan::latest('id')->firstOrFail();

    $this->actingAs($lo)
        ->get(route('loans.show', $loan))
        ->assertOk()
        ->assertSee($loan->loan_number);

    $auditor = loanDemoUser('auditor@example.test');
    $this->actingAs($auditor)->get(route('loans.show', $loan))->assertOk();

    $cashier = loanDemoUser('cashier@example.test');
    $this->actingAs($cashier)->get(route('loans.show', $loan))->assertForbidden();
});

test('updating a draft recalculates financial values and records before/after audit', function () {
    $lo = loanDemoUser('lo@example.test');

    $this->actingAs($lo)->post(route('loans.store'), loanPayload())->assertRedirect();
    $loan = Loan::latest('id')->firstOrFail();

    $this->actingAs($lo)
        ->put(route('loans.update', $loan), loanPayload([
            'principal_amount' => 20000000,
            'interest_rate' => '3',
            'tenor' => 12,
        ]))
        ->assertRedirect(route('loans.show', $loan));

    $loan->refresh();
    expect($loan->status)->toBe(Loan::STATUS_DRAFT);
    expect($loan->principal_amount)->toBe(20000000);
    expect($loan->interest_rate)->toBe(300);
    expect($loan->total_interest)->toBe(7200000);
    expect($loan->total_payable)->toBe(27200000);
    expect($loan->installment_amount)->toBe(2266666);

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'LOAN_UPDATED',
        'entity_type' => Loan::class,
        'entity_id' => $loan->id,
    ]);
});

test('a submitted loan cannot be updated through the edit endpoint', function () {
    $lo = loanDemoUser('lo@example.test');

    $this->actingAs($lo)->post(route('loans.store'), loanPayload())->assertRedirect();
    $loan = Loan::latest('id')->firstOrFail();
    $this->actingAs($lo)->post(route('loans.submit', $loan));

    $this->actingAs($lo)
        ->put(route('loans.update', $loan), loanPayload(['principal_amount' => 5000000, 'tenor' => 6]))
        ->assertForbidden();

    expect($loan->fresh()->principal_amount)->toBe(10000000);
});

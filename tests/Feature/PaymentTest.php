<?php

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Installment;
use App\Models\Loan;
use App\Models\Payment;
use App\Models\User;
use App\Services\LoanService;
use App\Services\PaymentAllocationService;
use App\Services\PaymentReversalService;
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

function paymentDemoUser(string $email): User
{
    return User::where('email', $email)->firstOrFail();
}

/**
 * Create a freshly disbursed ACTIVE loan with deterministic flat numbers:
 * principal 10.000.000 @ 2%/month for 10 months.
 */
function paymentActiveLoan(int $principal = 10000000, int $rateBps = 200, string $method = Loan::METHOD_FLAT, int $tenor = 10): Loan
{
    $loanService = app(LoanService::class);
    $lo = paymentDemoUser('lo@example.test');
    $admin = paymentDemoUser('admin@example.test');
    $customer = Customer::where('status', Customer::STATUS_ACTIVE)->firstOrFail();

    $loan = $loanService->createDraft([
        'customer_id' => $customer->id,
        'principal_amount' => $principal,
        'interest_rate_bps' => $rateBps,
        'interest_method' => $method,
        'tenor' => $tenor,
        'installment_frequency' => Loan::FREQUENCY_MONTHLY,
        'first_due_date' => now()->addMonth()->toDateString(),
    ], $lo->id);

    $loan = $loanService->submitLoan($loan, $lo->id);
    $loan = $loanService->startReview($loan, $lo->id);
    $loan = $loanService->approveLoan($loan, $admin->id);
    $loan = $loanService->prepareDisbursement($loan, $admin->id);

    return $loanService->disburseLoan($loan, $admin->id);
}

test('guests are redirected to login on payment module pages', function () {
    $this->get(route('payments.index'))->assertRedirect(route('login'));
    $this->get(route('payments.create'))->assertRedirect(route('login'));
});

test('only authorized roles can access the payment list', function () {
    $this->actingAs(paymentDemoUser('lo@example.test'))->get(route('payments.index'))->assertForbidden();
    $this->actingAs(paymentDemoUser('cashier@example.test'))->get(route('payments.index'))->assertOk();
    $this->actingAs(paymentDemoUser('lc@example.test'))->get(route('payments.index'))->assertOk();
    $this->actingAs(paymentDemoUser('auditor@example.test'))->get(route('payments.index'))->assertOk();
    $this->actingAs(paymentDemoUser('admin@example.test'))->get(route('payments.index'))->assertOk();
});

test('only roles with create permission can open the payment form', function () {
    $this->actingAs(paymentDemoUser('cashier@example.test'))->get(route('payments.create'))->assertOk();
    $this->actingAs(paymentDemoUser('auditor@example.test'))->get(route('payments.create'))->assertForbidden();
});

test('recording a payment locks the correct allocation, number format and audit trail', function () {
    $cashier = paymentDemoUser('cashier@example.test');
    $loan = paymentActiveLoan();

    $this->actingAs($cashier)
        ->post(route('payments.store'), [
            'loan_id' => $loan->id,
            'amount' => 1200000,
            'payment_method' => 'CASH',
            'payment_date' => now()->toDateString(),
        ])
        ->assertRedirect();

    $payment = Payment::where('loan_id', $loan->id)->latest('id')->firstOrFail();

    expect($payment->payment_number)->toMatch('/^PAY-'.date('Y').'-\d{6}$/');
    expect($payment->amount)->toBe(1200000);
    // Strict hierarchy: penalty 0 → interest 200.000 (satu periode) → principal 1.000.000.
    expect($payment->penalty_component)->toBe(0);
    expect($payment->interest_component)->toBe(200000);
    expect($payment->principal_component)->toBe(1000000);
    expect($payment->received_by)->toBe($cashier->id);

    // Satu angsuran pertama lunas, pinjaman masih aktif.
    $first = $loan->installments()->orderBy('installment_number')->first();
    expect($first->fresh()->status)->toBe(Installment::STATUS_PAID);
    expect($loan->fresh()->status)->toBe(Loan::STATUS_ACTIVE);

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'PAYMENT_CREATED',
        'entity_type' => Payment::class,
        'entity_id' => $payment->id,
        'user_id' => $cashier->id,
    ]);

    $audit = AuditLog::where('action', 'PAYMENT_CREATED')->where('entity_id', $payment->id)->firstOrFail();
    expect($audit->new_values['principal_component'])->toBe(1000000);
    expect($audit->new_values['interest_component'])->toBe(200000);
});

test('allocation obeys the mandatory penalty → interest → principal hierarchy', function () {
    $cashier = paymentDemoUser('cashier@example.test');
    $loan = paymentActiveLoan();

    // Simulasikan denda berjalan sebesar 50.000 pada pinjaman.
    $loan->update(['outstanding_penalty' => 50000, 'outstanding_total' => $loan->outstanding_total + 50000]);

    $this->actingAs($cashier)
        ->post(route('payments.store'), [
            'loan_id' => $loan->id,
            'amount' => 1150000,
            'payment_method' => 'BANK_TRANSFER',
            'reference_number' => 'TRX-0001',
            'payment_date' => now()->toDateString(),
        ])
        ->assertRedirect();

    $payment = Payment::where('loan_id', $loan->id)->latest('id')->firstOrFail();

    expect($payment->penalty_component)->toBe(50000);
    expect($payment->interest_component)->toBe(200000);
    expect($payment->principal_component)->toBe(900000);

    $loan->refresh();
    expect($loan->outstanding_penalty)->toBe(0);
    expect($loan->outstanding_interest)->toBe(1800000);
    expect($loan->outstanding_principal)->toBe(9100000);
    expect($loan->outstanding_total)->toBe(10900000);

    // Angsuran pertama terbayar sebagian.
    $first = $loan->installments()->orderBy('installment_number')->firstOrFail();
    expect($first->status)->toBe(Installment::STATUS_PARTIALLY_PAID);
    expect($first->total_paid)->toBe(1100000);
    expect($first->remaining_amount)->toBe(100000);
});

test('overpayment exceeding outstanding balance is rejected server-side', function () {
    $cashier = paymentDemoUser('cashier@example.test');
    $loan = paymentActiveLoan();

    $this->actingAs($cashier)
        ->post(route('payments.store'), [
            'loan_id' => $loan->id,
            'amount' => $loan->outstanding_total + 1,
            'payment_method' => 'CASH',
            'payment_date' => now()->toDateString(),
        ])
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(Payment::where('loan_id', $loan->id)->count())->toBe(0);
    expect($loan->fresh()->outstanding_total)->toBe(12000000);
});

test('paying off the full obligation auto-completes the loan', function () {
    $cashier = paymentDemoUser('cashier@example.test');
    $loan = paymentActiveLoan();

    $this->actingAs($cashier)
        ->post(route('payments.store'), [
            'loan_id' => $loan->id,
            'amount' => $loan->outstanding_total,
            'payment_method' => 'QRIS',
            'payment_date' => now()->toDateString(),
        ])
        ->assertRedirect();

    $loan->refresh();
    expect($loan->status)->toBe(Loan::STATUS_COMPLETED);
    expect($loan->completed_at)->not->toBeNull();
    expect($loan->outstanding_total)->toBe(0);
    expect($loan->installments()->where('status', Installment::STATUS_PAID)->count())->toBe(10);
});

test('a payment cannot be edited or deleted once recorded', function () {
    $cashier = paymentDemoUser('cashier@example.test');
    $loan = paymentActiveLoan();

    app(PaymentAllocationService::class)->recordPayment(
        $loan, 1200000, Payment::METHOD_CASH, now()->toDateString(), null, null, $cashier->id,
    );
    $payment = Payment::where('loan_id', $loan->id)->firstOrFail();

    $this->actingAs($cashier)->get('/payments/'.$payment->id.'/edit')->assertNotFound();
    $this->actingAs($cashier)->get('/payments/'.$payment->id.'/delete')->assertNotFound();
});

test('only admins can reverse a payment and the reversal restores all balances', function () {
    // Setoran parsial 1.150.000 dengan denda 50.000.
    $cashier = paymentDemoUser('cashier@example.test');
    $admin = paymentDemoUser('admin@example.test');
    $loan = paymentActiveLoan();
    $loan->update(['outstanding_penalty' => 50000, 'outstanding_total' => $loan->outstanding_total + 50000]);

    $payment = app(PaymentAllocationService::class)->recordPayment(
        $loan, 1150000, Payment::METHOD_CASH, now()->toDateString(), null, null, $cashier->id,
    );
    $payment->refresh()->load('loan', 'reversal');

    // Kasir tidak berwenang membalikkan.
    $this->actingAs($cashier)
        ->post(route('payments.reverse', $payment), ['reason' => 'Kesalahan input nominal yang tidak disengaja'])
        ->assertForbidden();

    // Admin balikkan — audit & saldo dipulihkan.
    $this->actingAs($admin)
        ->post(route('payments.reverse', $payment), ['reason' => 'Setoran ganda diakui nasabah'])
        ->assertRedirect();

    $this->assertDatabaseHas('payment_reversals', [
        'payment_id' => $payment->id,
        'reversed_by' => $admin->id,
    ]);

    $payment->refresh();
    expect($payment->isReversed())->toBeTrue();

    $loan->refresh();
    expect($loan->outstanding_penalty)->toBe(50000);
    expect($loan->outstanding_interest)->toBe(2000000);
    expect($loan->outstanding_principal)->toBe(10000000);
    expect($loan->outstanding_total)->toBe(12050000);

    $first = $loan->installments()->orderBy('installment_number')->firstOrFail();
    expect($first->status)->toBe(Installment::STATUS_PENDING);
    expect($first->total_paid)->toBe(0);

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'PAYMENT_REVERSED',
        'entity_type' => Payment::class,
        'entity_id' => $payment->id,
        'user_id' => $admin->id,
    ]);
});

test('reversing the payoff payment reopens a completed loan back to active', function () {
    $cashier = paymentDemoUser('cashier@example.test');
    $admin = paymentDemoUser('admin@example.test');
    $loan = paymentActiveLoan();

    $payment = app(PaymentAllocationService::class)->recordPayment(
        $loan, $loan->outstanding_total, Payment::METHOD_BANK_TRANSFER, now()->toDateString(), null, null, $cashier->id,
    );

    expect($loan->fresh()->status)->toBe(Loan::STATUS_COMPLETED);

    $this->actingAs($admin)
        ->post(route('payments.reverse', $payment), ['reason' => 'Transfer tiba berlebih dan diminta dikembalikan'])
        ->assertRedirect();

    $loan->refresh();
    expect($loan->status)->toBe(Loan::STATUS_ACTIVE);
    expect($loan->completed_at)->toBeNull();
    expect($loan->outstanding_total)->toBe(12000000);
    expect($loan->installments()->where('status', Installment::STATUS_PENDING)->count())->toBe(10);
});

test('an already reversed payment cannot be reversed again', function () {
    $cashier = paymentDemoUser('cashier@example.test');
    $admin = paymentDemoUser('admin@example.test');
    $loan = paymentActiveLoan();

    $payment = app(PaymentAllocationService::class)->recordPayment(
        $loan, 1200000, Payment::METHOD_CASH, now()->toDateString(), null, null, $cashier->id,
    );

    $this->actingAs($admin)->post(route('payments.reverse', $payment), ['reason' => 'Alasan pertama yang valid'])->assertRedirect();
    $this->assertDatabaseCount('payment_reversals', 1);

    $this->actingAs($admin)
        ->post(route('payments.reverse', $payment), ['reason' => 'Percobaan balik dua kali yang keliru'])
        ->assertRedirect()
        ->assertSessionHas('error');

    $this->assertDatabaseCount('payment_reversals', 1);
});

test('payment and reversal forms enforce required server-side validation', function () {
    $cashier = paymentDemoUser('cashier@example.test');
    $admin = paymentDemoUser('admin@example.test');
    $loan = paymentActiveLoan();

    $this->actingAs($cashier)
        ->from(route('payments.create'))
        ->post(route('payments.store'), [
            'loan_id' => $loan->id,
            'amount' => 0,
            'payment_method' => '',
            'payment_date' => '',
        ])
        ->assertSessionHasErrors(['amount', 'payment_method', 'payment_date']);

    $payment = app(PaymentAllocationService::class)->recordPayment(
        $loan, 1200000, Payment::METHOD_CASH, now()->toDateString(), null, null, $cashier->id,
    );

    $this->actingAs($admin)
        ->post(route('payments.reverse', $payment), ['reason' => ''])
        ->assertSessionHasErrors(['reason']);
});

test('payment numbers are generated sequentially in the PAY format', function () {
    $cashier = paymentDemoUser('cashier@example.test');
    $allocationService = app(PaymentAllocationService::class);

    $loanA = paymentActiveLoan();
    $loanB = paymentActiveLoan(15000000, 200, Loan::METHOD_FLAT, 5);

    $paymentA = $allocationService->recordPayment($loanA, 1200000, Payment::METHOD_CASH, now()->toDateString(), null, null, $cashier->id);
    $paymentB = $allocationService->recordPayment($loanB, 3300000, Payment::METHOD_CASH, now()->toDateString(), null, null, $cashier->id);

    expect($paymentA->payment_number)->toMatch('/^PAY-'.date('Y').'-\d{6}$/');
    expect($paymentB->payment_number)->not->toBe($paymentA->payment_number);
});

test('allocating more than the remaining payable amount yields an exhausted unallocated remainder', function () {
    $loan = paymentActiveLoan();
    $service = app(PaymentAllocationService::class);

    $allocation = $service->allocate($loan, $loan->outstanding_total);
    expect($allocation['unallocated'])->toBe(0);
    expect($allocation['interest'])->toBe(2000000);
    expect($allocation['principal'])->toBe(10000000);

    $over = $service->allocate($loan, $loan->outstanding_total + 1);
    expect($over['unallocated'])->toBe(1);
});

test('reverse service rejects a payment that has already been reversed', function () {
    $cashier = paymentDemoUser('cashier@example.test');

    $loan = paymentActiveLoan();
    $payment = app(PaymentAllocationService::class)->recordPayment(
        $loan, 1200000, Payment::METHOD_CASH, now()->toDateString(), null, null, $cashier->id,
    );

    app(PaymentReversalService::class)->reverse($payment, 'Alasan yang valid', $cashier->id);

    expect(fn () => app(PaymentReversalService::class)->reverse($payment, 'Balik dua kali', $cashier->id))
        ->toThrow(InvalidArgumentException::class);
});

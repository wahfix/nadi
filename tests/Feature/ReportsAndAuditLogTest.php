<?php

use App\Models\Collateral;
use App\Models\Customer;
use App\Models\IdentityVerification;
use App\Models\Loan;
use App\Models\Payment;
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

test('admin can open every report page', function () {
    $admin = demoUser('admin@example.test');
    $this->actingAs($admin);

    foreach ([
        'reports.customers',
        'reports.loans',
        'reports.outstanding',
        'reports.due-dates',
        'reports.overdue',
        'reports.payments',
        'reports.collaterals',
        'reports.releases',
        'reports.collection-activities',
        'reports.audit-logs',
    ] as $route) {
        $this->get(route($route))->assertOk();
    }
});

test('report menu lists all ten report modules', function () {
    $admin = demoUser('admin@example.test');
    $this->actingAs($admin)
        ->get(route('reports.index'))
        ->assertOk()
        ->assertSee('Daftar Nasabah')
        ->assertSee('Daftar Pinjaman')
        ->assertSee('Outstanding Pinjaman')
        ->assertSee('Jadwal Jatuh Tempo')
        ->assertSee('Tunggakan')
        ->assertSee('Pembayaran')
        ->assertSee('Jaminan')
        ->assertSee('Pengambilan Jaminan')
        ->assertSee('Aktivitas Penagihan')
        ->assertSee('Audit Log');
});

test('date range filter excludes records outside the range', function () {
    $admin = demoUser('admin@example.test');
    $customer = Customer::firstOrFail();

    $this->actingAs($admin)
        ->get(route('reports.customers', ['date_from' => '2099-01-01', 'date_to' => '2099-12-31']))
        ->assertOk()
        ->assertSee('Tidak ada data nasabah.');

    $this->actingAs($admin)
        ->get(route('reports.customers', ['search' => $customer->customer_code]))
        ->assertOk()
        ->assertSee($customer->customer_code)
        ->assertSee($customer->full_name);
});

test('outstanding report only contains active and overdue loans', function () {
    $admin = demoUser('admin@example.test');
    $this->actingAs($admin);

    $activeLoan = Loan::where('status', Loan::STATUS_ACTIVE)->firstOrFail();
    $record = $this->get(route('reports.outstanding'))
        ->assertOk();
    $html = $record->getContent();

    expect($html)->toContain(format_rupiah($activeLoan->outstanding_total));

    $releasedLoan = Loan::whereIn('status', [Loan::STATUS_COMPLETED])->first();
    if ($releasedLoan) {
        expect(str_contains($html, $releasedLoan->loan_number))->toBeFalse();
    }
});

test('audit log page shows recorded mutations and supports search', function () {
    $admin = demoUser('admin@example.test');
    $this->actingAs($admin);

    $this->post(route('customers.store'), [
        'full_name' => 'Nadia Audit Test',
        'national_id_number' => '3399999999900001',
        'date_of_birth' => '1990-01-01',
        'gender' => 'FEMALE',
        'phone' => '081299990001',
        'address' => 'Jl. Audit No. 1',
        'city' => 'Jakarta',
        'emergency_contact_name' => 'Keluarga Nadia',
        'emergency_contact_phone' => '081299990099',
        'employment.company_name' => 'PT Audit Sejahtera',
        'employment.department' => 'Keuangan',
        'employment.position' => 'Staf',
        'employment.employment_type' => 'PERMANENT',
        'employment.estimated_monthly_income' => 5000000,
        'employment.employment_status' => 'ACTIVE',
    ])->assertRedirect();

    $this->get(route('audit-log.index'))
        ->assertOk()
        ->assertSee('CUSTOMER_CREATED')
        ->assertSee('Nadia Audit Test');

    $this->get(route('audit-log.index', ['search' => 'Nadia']))
        ->assertOk()
        ->assertSee('CUSTOMER_CREATED');
});

test('audit log JSON diff modal renders before and after data', function () {
    $admin = demoUser('admin@example.test');
    $this->actingAs($admin);

    $this->get(route('audit-log.index'))
        ->assertOk()
        ->assertSee('Kondisi Sebelum')
        ->assertSee('Kondisi Sesudah');
});

test('payment report shows reversal badge for reversed payments', function () {
    $cashier = demoUser('cashier@example.test');
    $admin = demoUser('admin@example.test');

    $loan = Loan::where('status', Loan::STATUS_ACTIVE)->firstOrFail();
    $first = $loan->installments()->orderBy('installment_number')->firstOrFail();

    $this->actingAs($cashier)
        ->post(route('payments.store'), [
            'loan_id' => $loan->id,
            'amount' => $first->total_due,
            'payment_method' => 'CASH',
            'payment_date' => now()->toDateString(),
        ])
        ->assertRedirect();

    $payment = Payment::where('loan_id', $loan->id)->latest('id')->firstOrFail();

    $this->actingAs($admin)
        ->post(route('payments.reverse', $payment), ['reason' => 'Setoran salah nominal pada data uji laporan'])
        ->assertRedirect();

    $this->assertDatabaseHas('payment_reversals', [
        'payment_id' => $payment->id,
    ]);

    $this->actingAs($admin)
        ->get(route('reports.payments'))
        ->assertOk()
        ->assertSee('Dibalik')
        ->assertSee($payment->payment_number);
});

test('print summary document renders for an active loan', function () {
    $admin = demoUser('admin@example.test');
    $loan = Loan::where('status', Loan::STATUS_ACTIVE)->firstOrFail();

    $this->actingAs($admin)
        ->get(route('loans.print-summary', $loan))
        ->assertOk()
        ->assertSee('Ringkasan Perjanjian Pinjaman')
        ->assertSee($loan->loan_number)
        ->assertSee(format_rupiah($loan->principal_amount));
});

test('installment schedule document lists every installment', function () {
    $admin = demoUser('admin@example.test');
    $loan = Loan::where('status', Loan::STATUS_ACTIVE)->firstOrFail();

    $this->actingAs($admin)
        ->get(route('loans.print-installments', $loan))
        ->assertOk()
        ->assertSee('Jadwal Angsuran')
        ->assertSee('Angsuran Ke-')
        ->assertSee($loan->installments->first()->installment_number);
});

test('collateral receipt document renders', function () {
    $admin = demoUser('admin@example.test');
    $collateral = Collateral::firstOrFail();

    $this->actingAs($admin)
        ->get(route('collaterals.receipt', $collateral))
        ->assertOk()
        ->assertSee('Surat Tanda Terima Jaminan')
        ->assertSee($collateral->collateral_code);
});

test('verification result document renders a verified result', function () {
    $admin = demoUser('admin@example.test');
    $verification = IdentityVerification::where('result', IdentityVerification::RESULT_VERIFIED)->firstOrFail();

    $this->actingAs($admin)
        ->get(route('verifications.print', $verification))
        ->assertOk()
        ->assertSee('Hasil Verifikasi Identitas')
        ->assertSee('Terverifikasi')
        ->assertSee($verification->verified_name);
});

test('users without verifications.view cannot print verification results', function () {
    $lo = demoUser('lo@example.test');
    $verification = IdentityVerification::firstOrFail();

    $this->actingAs($lo)
        ->get(route('verifications.print', $verification))
        ->assertForbidden();
});

test('users without reports.view cannot open report pages', function () {
    $cashier = demoUser('cashier@example.test');

    $this->actingAs($cashier)
        ->get(route('reports.customers'))
        ->assertForbidden();
});

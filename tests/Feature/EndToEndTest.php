<?php

use App\Models\AuditLog;
use App\Models\Collateral;
use App\Models\CollateralRelease;
use App\Models\Customer;
use App\Models\IdentityVerification;
use App\Models\Installment;
use App\Models\Loan;
use App\Models\Payment;
use App\Models\User;
use App\Services\CollateralReleaseService;
use App\Services\CollateralService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;

beforeEach(function () {
    $this->seed([
        RoleSeeder::class,
        PermissionSeeder::class,
        UserSeeder::class,
    ]);
});

function e2eCustomerPayload(array $overrides = []): array
{
    return array_merge([
        'full_name' => 'Nasabah Uji E2E',
        'national_id_number' => '3501000000000002',
        'date_of_birth' => '1992-08-15',
        'gender' => 'MALE',
        'phone' => '082111223344',
        'email' => 'nasabah-e2e@uji.test',
        'address' => 'Jl. Uji Coba No. 17',
        'city' => 'Surabaya',
        'emergency_contact_name' => 'Keluarga Uji E2E',
        'emergency_contact_phone' => '082111223345',
        'employment' => [
            'company_name' => 'PT Uji Sintetis',
            'department' => 'Produksi',
            'position' => 'Operator',
            'employment_type' => 'PERMANENT',
            'employment_start_date' => '2019-02-01',
            'estimated_monthly_income' => '5200000',
            'employment_status' => 'ACTIVE',
            'notes' => null,
        ],
    ], $overrides);
}

test('26 langkah skenario E2E kritis berjalan dari awal hingga selesai tanpa error', function () {
    // Langkah 1 — Login sebagai Admin.
    $admin = User::where('email', 'admin@example.test')->firstOrFail();
    $this->post('/login', ['email' => 'admin@example.test', 'password' => 'password'])
        ->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($admin);

    // Langkah 2 — Buat nasabah sintetis beserta data pekerjaan.
    $customerPayload = e2eCustomerPayload([
        'full_name' => 'Nasabah Uji E2E',
        'national_id_number' => '3501000000000002',
        'email' => 'nasabah-e2e@uji.test',
        'phone' => '082111223344',
    ]);
    $this->post(route('customers.store'), $customerPayload)->assertRedirect();

    $customer = Customer::where('national_id_number', '3501000000000002')->firstOrFail();
    expect($customer->customer_code)->toMatch('/^CUS-'.date('Y').'-\d{6}$/');
    expect($customer->employments()->count())->toBe(1);

    // Langkah 3 — Login sebagai LO.
    $lo = User::where('email', 'lo@example.test')->firstOrFail();
    $this->actingAs($lo);

    // Langkah 4 — Buat pinjaman (pokok 10juta, bunga FLAT 2%, tenor 10 bulan).
    $loanPayload = [
        'customer_id' => $customer->id,
        'principal_amount' => 10000000,
        'interest_rate' => '2',
        'interest_method' => 'FLAT',
        'tenor' => 10,
        'installment_frequency' => 'MONTHLY',
        'disbursement_date' => now()->toDateString(),
        'first_due_date' => now()->addMonth()->toDateString(),
    ];
    $this->post(route('loans.store'), $loanPayload)->assertRedirect();

    $loan = Loan::latest('id')->firstOrFail();
    expect($loan->loan_number)->toMatch('/^NADI-LOAN-'.date('Y').'-\d{6}$/');
    expect($loan->status)->toBe(Loan::STATUS_DRAFT);
    expect($loan->installment_amount)->toBe(1200000);

    // Langkah 5 — Submit pinjaman: DRAFT → SUBMITTED.
    $this->post(route('loans.submit', $loan))->assertRedirect();
    expect($loan->fresh()->status)->toBe(Loan::STATUS_SUBMITTED);

    // Langkah 6 — Admin mereview pengajuan.
    $this->actingAs($admin)->post(route('loans.review', $loan))->assertRedirect();
    expect($loan->fresh()->status)->toBe(Loan::STATUS_UNDER_REVIEW);

    // Langkah 7 — Admin menyetujui pinjaman.
    $this->post(route('loans.approve', $loan))->assertRedirect();
    expect($loan->fresh()->status)->toBe(Loan::STATUS_APPROVED);

    // Langkah 8 — Pinjaman siap dicairkan.
    $this->post(route('loans.ready', $loan))->assertRedirect();
    expect($loan->fresh()->status)->toBe(Loan::STATUS_READY_FOR_DISBURSEMENT);

    // Langkah 9 — Admin mencairkan pinjaman → ACTIVE.
    $this->post(route('loans.disburse', $loan))->assertRedirect();
    $loan->refresh();
    expect($loan->status)->toBe(Loan::STATUS_ACTIVE);
    expect($loan->disbursed_at)->not->toBeNull();
    expect($loan->outstanding_total)->toBe(12000000);

    // Langkah 10 — Sistem menghasilkan jadwal angsuran lengkap.
    expect($loan->installments()->count())->toBe(10);
    expect($loan->installments()->where('status', Installment::STATUS_PENDING)->count())->toBe(10);

    // Langkah 11 — Collateral Officer menerima agunan fisik.
    $collateralOfficer = User::where('email', 'collateral@example.test')->firstOrFail();
    $this->actingAs($collateralOfficer);

    $this->post(route('collaterals.store'), [
        'loan_id' => $loan->id,
        'collateral_type' => Collateral::TYPE_VEHICLE,
        'description' => 'Sepeda motor uji E2E, plat AB 0001 CV, data sintetis',
        'identification_number' => 'AB 0001 CV',
        'estimated_value' => 15_000_000,
        'received_date' => now()->toDateString(),
        'condition_on_receipt' => 'Kondisi baik, surat-surat lengkap',
        'storage_location' => 'Brankas A-02',
    ])->assertRedirect();

    $collateral = Collateral::where('loan_id', $loan->id)->firstOrFail();
    expect($collateral->collateral_code)->toMatch('/^COL-'.date('Y').'-\d{6}$/');
    expect($collateral->custody_status)->toBe(Collateral::STATUS_RECEIVED);

    // Jaminan dipindahkan ke penyimpanan (IN_CUSTODY) dan ditandai siap diserahkan.
    $this->post(route('collaterals.update-custody', $collateral), [
        'custody_status' => Collateral::STATUS_IN_CUSTODY,
    ])->assertRedirect();

    $collateral->refresh();
    $this->post(route('collaterals.update-custody', $collateral), [
        'custody_status' => Collateral::STATUS_READY_FOR_RELEASE,
    ])->assertRedirect();
    expect($collateral->fresh()->custody_status)->toBe(Collateral::STATUS_READY_FOR_RELEASE);

    // Langkah 12 — LC melihat tagihan pada dasbor penagihan.
    $lc = User::where('email', 'lc@example.test')->firstOrFail();
    $this->actingAs($lc)
        ->get(route('collections.index'))
        ->assertOk()
        ->assertSee('Dasbor Penagihan');

    // Langkah 13 — Cashier mencatat pembayaran angsuran.
    $cashier = User::where('email', 'cashier@example.test')->firstOrFail();
    $this->actingAs($cashier);

    $this->post(route('payments.store'), [
        'loan_id' => $loan->id,
        'amount' => 1200000,
        'payment_method' => 'CASH',
        'payment_date' => now()->toDateString(),
    ])->assertRedirect();

    $payment = Payment::where('loan_id', $loan->id)->orderBy('id')->firstOrFail();
    expect($payment->payment_number)->toMatch('/^PAY-'.date('Y').'-\d{6}$/');

    // Langkah 14 — Alokasi bertingkat: Denda → Bunga → Pokok.
    expect($payment->penalty_component)->toBe(0);
    expect($payment->interest_component)->toBe(200000);
    expect($payment->principal_component)->toBe(1000000);

    // Langkah 15 — Status angsuran pertama berubah menjadi PAID.
    $first = $loan->installments()->orderBy('installment_number')->firstOrFail();
    expect($first->fresh()->status)->toBe(Installment::STATUS_PAID);

    // Langkah 16 — Lanjutkan pembayaran hingga kewajiban lunas.
    $loan->refresh();
    $this->post(route('payments.store'), [
        'loan_id' => $loan->id,
        'amount' => $loan->outstanding_total,
        'payment_method' => 'BANK_TRANSFER',
        'payment_date' => now()->toDateString(),
    ])->assertRedirect();

    $loan->refresh();
    expect($loan->status)->toBe(Loan::STATUS_COMPLETED);
    expect($loan->completed_at)->not->toBeNull();
    expect($loan->outstanding_total)->toBe(0);
    expect($loan->installments()->where('status', Installment::STATUS_PAID)->count())->toBe(10);

    // Langkah 17 — Jaminan menjadi memenuhi syarat (pinjaman lunas).
    expect($loan->isPaidOff())->toBeTrue();

    // Langkah 18 — Identity Verifier memverifikasi nasabah → VERIFIED.
    $verifier = User::where('email', 'verifier@example.test')->firstOrFail();
    $this->actingAs($verifier);

    $this->post(route('verifications.store'), [
        'loan_id' => $loan->id,
        'verification_method' => IdentityVerification::METHOD_GOVERNMENT_ID,
        'verified_name' => $customer->full_name,
        'verified_id_number' => $customer->national_id_number,
        'result' => IdentityVerification::RESULT_VERIFIED,
    ])->assertRedirect();

    $verification = IdentityVerification::where('loan_id', $loan->id)
        ->where('result', IdentityVerification::RESULT_VERIFIED)
        ->firstOrFail();

    // Langkah 19 — Collateral Officer membuka alur pengambilan jaminan.
    $this->actingAs($collateralOfficer);

    // Langkah 20 — Sistem memeriksa kelayakan: seluruh checklist hijau.
    $this->get(route('releases.create', ['collateral' => $collateral->id]))
        ->assertOk()
        ->assertSee('Daftar Periksa')
        ->assertSee($collateral->collateral_code);

    $checklist = app(CollateralReleaseService::class)->getReleaseChecklist($collateral->fresh());
    expect($checklist)->not->toBeEmpty();
    foreach ($checklist as $item) {
        expect($item['passed'])->toBeTrue();
    }

    // Langkah 21 — Sistem mengonfirmasi identitas penerima terverifikasi.
    expect($verification->result)->toBe(IdentityVerification::RESULT_VERIFIED);

    // Langkah 22 — Operator mengonfirmasi serah terima pada modal.
    $this->post(route('releases.store'), [
        'collateral_id' => $collateral->id,
        'identity_verification_id' => $verification->id,
        'released_to_name' => $customer->full_name,
        'relationship_to_customer' => 'Pemilik (Nasabah)',
        'release_date' => now()->toDateString(),
        'release_location' => 'Kantor NADI, Lantai 1',
        'handover_notes' => 'Serah terima melalui alur E2E (data sintetis).',
    ])->assertRedirect();

    // Langkah 23 — Transaksi pelepasan tercatat dengan nomor REL-YYYY-XXXXXX.
    $release = CollateralRelease::where('collateral_id', $collateral->id)->firstOrFail();
    expect($release->release_number)->toMatch('/^REL-'.date('Y').'-\d{6}$/');
    expect($release->verified_identity_id)->toBe($verification->id);

    // Langkah 24 — Status agunan menjadi RELEASED dengan pelaksana & waktu tercatat.
    $collateral->refresh();
    expect($collateral->custody_status)->toBe(Collateral::STATUS_RELEASED);
    expect($collateral->released_by)->toBe($collateralOfficer->id);
    expect($collateral->released_at)->not->toBeNull();

    // Langkah 25 — Cetak Berita Acara Penyerahan.
    $this->actingAs($collateralOfficer)
        ->get(route('releases.receipt', $release))
        ->assertOk()
        ->assertSee('Berita Acara Serah Terima Jaminan')
        ->assertSee($release->release_number);

    // Langkah 26 — Jejak audit lengkap dari langkah 1 s.d. 25 tanpa terputus.
    $expectedEvents = [
        'CUSTOMER_CREATED',
        'LOAN_CREATED',
        'LOAN_SUBMITTED',
        'LOAN_APPROVED',
        'LOAN_DISBURSED',
        'COLLATERAL_RECEIVED',
        'PAYMENT_CREATED',
        'IDENTITY_VERIFIED',
        'COLLATERAL_RELEASED',
    ];

    foreach ($expectedEvents as $event) {
        $this->assertDatabaseHas('audit_logs', ['action' => $event]);
    }

    // Kontinuitas: seluruh event kunci terikat ke entitas yang sama dan berurutan.
    $loanAudit = AuditLog::query()
        ->where('entity_type', Loan::class)
        ->where('entity_id', $loan->id)
        ->orderBy('id')
        ->pluck('action');

    expect($loanAudit)->toContain('LOAN_CREATED', 'LOAN_SUBMITTED', 'LOAN_APPROVED', 'LOAN_DISBURSED');

    $releaseAudit = AuditLog::query()
        ->where('entity_type', CollateralRelease::class)
        ->where('entity_id', $release->id)
        ->firstOrFail();

    expect($releaseAudit->action)->toBe('COLLATERAL_RELEASED');
});

test('release flow remains blocked server-side during an incomplete E2E run', function () {
    $admin = User::where('email', 'admin@example.test')->firstOrFail();
    $lo = User::where('email', 'lo@example.test')->firstOrFail();

    // Buat nasabah + pinjaman ACTIVE tanpa pelunasan.
    $this->actingAs($admin)->post(route('customers.store'), e2eCustomerPayload([
        'national_id_number' => '3501000000000003',
    ]))->assertRedirect();

    $customer = Customer::where('national_id_number', '3501000000000003')->firstOrFail();

    $this->actingAs($lo)->post(route('loans.store'), [
        'customer_id' => $customer->id,
        'principal_amount' => 10000000,
        'interest_rate' => '2',
        'interest_method' => 'FLAT',
        'tenor' => 10,
        'installment_frequency' => 'MONTHLY',
        'disbursement_date' => now()->toDateString(),
        'first_due_date' => now()->addMonth()->toDateString(),
    ])->assertRedirect();

    $loan = Loan::latest('id')->firstOrFail();

    $this->actingAs($lo)->post(route('loans.submit', $loan))->assertRedirect();
    $this->actingAs($admin)->post(route('loans.review', $loan))->assertRedirect();
    $this->actingAs($admin)->post(route('loans.approve', $loan))->assertRedirect();
    $this->actingAs($admin)->post(route('loans.ready', $loan))->assertRedirect();
    $this->actingAs($admin)->post(route('loans.disburse', $loan))->assertRedirect();

    $loan->refresh();
    expect($loan->status)->toBe(Loan::STATUS_ACTIVE);

    // Coba eksekusi pelepasan padahal pinjaman belum lunas.
    $verification = app(CollateralReleaseService::class)->verifyIdentity($loan, [
        'verification_method' => IdentityVerification::METHOD_GOVERNMENT_ID,
        'verified_name' => $customer->full_name,
        'verified_id_number' => $customer->national_id_number,
        'result' => IdentityVerification::RESULT_VERIFIED,
        'verifier_id' => User::where('email', 'verifier@example.test')->firstOrFail()->id,
    ]);

    $collateral = app(CollateralService::class)->receiveCollateral($loan, [
        'collateral_type' => Collateral::TYPE_DOCUMENT,
        'description' => 'Sertifikat uji E2E (data sintetis)',
        'identification_number' => 'SHM-UJI-0001',
        'estimated_value' => 5_000_000,
        'received_date' => now()->toDateString(),
        'condition_on_receipt' => 'Baik',
        'storage_location' => 'Rak E2E',
        'received_by' => User::where('email', 'collateral@example.test')->firstOrFail()->id,
    ]);

    $officer = User::where('email', 'collateral@example.test')->firstOrFail();
    $this->actingAs($officer);

    $this->post(route('collaterals.update-custody', $collateral), [
        'custody_status' => Collateral::STATUS_IN_CUSTODY,
    ])->assertRedirect();
    $collateral->refresh();
    $this->post(route('collaterals.update-custody', $collateral), [
        'custody_status' => Collateral::STATUS_READY_FOR_RELEASE,
    ])->assertRedirect();

    // Serah terima ditolak karena syarat pelunasan belum terpenuhi.
    $this->post(route('releases.store'), [
        'collateral_id' => $collateral->id,
        'identity_verification_id' => $verification->id,
        'released_to_name' => $customer->full_name,
        'relationship_to_customer' => 'Pemilik (Nasabah)',
        'release_date' => now()->toDateString(),
        'release_location' => 'Kantor NADI, Lantai 1',
    ])->assertRedirect()->assertSessionHas('error');

    expect($collateral->fresh()->custody_status)->toBe(Collateral::STATUS_READY_FOR_RELEASE);
    $this->assertDatabaseCount('collateral_releases', 0);
});

<?php

use App\Models\Customer;
use App\Models\User;
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

function customerDemoUser(string $email): User
{
    return User::where('email', $email)->firstOrFail();
}

function customerPayload(array $overrides = []): array
{
    return array_merge([
        'full_name' => 'Andi Wijaya',
        'national_id_number' => '3501000000000001',
        'date_of_birth' => '1990-05-12',
        'gender' => 'MALE',
        'phone' => '081234567890',
        'email' => 'andi@contoh.test',
        'address' => 'Jl. Kenangan No. 10',
        'city' => 'Surabaya',
        'emergency_contact_name' => 'Siti Aminah',
        'emergency_contact_phone' => '081111222333',
        'employment' => [
            'company_name' => 'PT Contoh Sejahtera',
            'department' => 'Produksi',
            'position' => 'Operator',
            'employment_type' => 'PERMANENT',
            'employment_start_date' => '2018-03-01',
            'estimated_monthly_income' => '5000000',
            'employment_status' => 'ACTIVE',
            'notes' => null,
        ],
    ], $overrides);
}

test('guests are redirected to login on customer module pages', function () {
    $this->get(route('customers.index'))->assertRedirect(route('login'));
    $this->get(route('customers.create'))->assertRedirect(route('login'));
});

test('only authorized roles can access the customer list', function () {
    $lo = customerDemoUser('lo@example.test');
    $this->actingAs($lo)->get(route('customers.index'))->assertOk();

    $cashier = customerDemoUser('cashier@example.test');
    $this->actingAs($cashier)->get(route('customers.index'))->assertForbidden();
});

test('only roles with create permission can open the customer create form', function () {
    $lo = customerDemoUser('lo@example.test');
    $this->actingAs($lo)->get(route('customers.create'))->assertOk();

    $auditor = customerDemoUser('auditor@example.test');
    $this->actingAs($auditor)->get(route('customers.create'))->assertForbidden();
});

test('customer creation generates the correct sequential code', function () {
    $lo = customerDemoUser('lo@example.test');

    $this->actingAs($lo)
        ->post(route('customers.store'), customerPayload())
        ->assertRedirect();

    $first = Customer::where('full_name', 'Andi Wijaya')->firstOrFail();
    expect($first->customer_code)->toMatch('/^CUS-'.date('Y').'-000001$/');

    $this->actingAs($lo)
        ->post(route('customers.store'), customerPayload([
            'full_name' => 'Andi Wijaya Kedua',
            'national_id_number' => '3501000000000002',
            'phone' => '081234567891',
            'email' => 'andi2@contoh.test',
        ]))
        ->assertRedirect();

    $second = Customer::where('full_name', 'Andi Wijaya Kedua')->firstOrFail();
    expect($second->customer_code)->toMatch('/^CUS-'.date('Y').'-000002$/');
});

test('customer creation stores employment and records an audit trail', function () {
    $lo = customerDemoUser('lo@example.test');

    $this->actingAs($lo)
        ->post(route('customers.store'), customerPayload())
        ->assertRedirect(route('customers.show', $created = Customer::where('full_name', 'Andi Wijaya')->firstOrFail()));

    $this->assertDatabaseHas('customers', [
        'id' => $created->id,
        'status' => 'ACTIVE',
    ]);

    $this->assertDatabaseHas('employments', [
        'customer_id' => $created->id,
        'company_name' => 'PT Contoh Sejahtera',
        'estimated_monthly_income' => 5000000,
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'CUSTOMER_CREATED',
        'entity_type' => Customer::class,
        'entity_id' => $created->id,
    ]);
});

test('customer creation rejects duplicate NIK and duplicate phone', function () {
    $lo = customerDemoUser('lo@example.test');

    $this->actingAs($lo)->post(route('customers.store'), customerPayload())->assertRedirect();

    $this->actingAs($lo)
        ->from(route('customers.create'))
        ->post(route('customers.store'), customerPayload([
            'full_name' => 'Pengguna Baru',
            'phone' => '081234567890',
            'national_id_number' => '3501000000000003',
        ]))
        ->assertRedirect(route('customers.create'))
        ->assertSessionHasErrors(['phone']);

    $this->actingAs($lo)
        ->from(route('customers.create'))
        ->post(route('customers.store'), customerPayload([
            'full_name' => 'Pengguna Baru',
            'phone' => '081234567891',
            'national_id_number' => '3501000000000001',
        ]))
        ->assertRedirect(route('customers.create'))
        ->assertSessionHasErrors(['national_id_number']);

    $this->assertDatabaseCount('customers', 1);
});

test('customer creation is validated server-side', function () {
    $lo = customerDemoUser('lo@example.test');

    $this->actingAs($lo)
        ->from(route('customers.create'))
        ->post(route('customers.store'), [])
        ->assertRedirect(route('customers.create'))
        ->assertSessionHasErrors(['full_name', 'national_id_number', 'date_of_birth', 'gender', 'phone', 'address', 'city', 'emergency_contact_name', 'emergency_contact_phone']);

    $this->assertDatabaseCount('customers', 0);
});

test('update customer reflects new values and records before/after audit trail', function () {
    $lo = customerDemoUser('lo@example.test');

    $this->actingAs($lo)->post(route('customers.store'), customerPayload())->assertRedirect();

    $customer = Customer::where('full_name', 'Andi Wijaya')->firstOrFail();

    $this->actingAs($lo)
        ->put(route('customers.update', $customer), customerPayload([
            'full_name' => 'Andi Wijaya Baru',
            'phone' => '081234567892',
            'employment' => [
                'company_name' => 'PT Berubah Jaya',
                'position' => 'Supervisor',
                'employment_type' => 'PERMANENT',
                'employment_start_date' => '2019-01-01',
                'estimated_monthly_income' => '7000000',
                'employment_status' => 'ACTIVE',
            ],
        ]))
        ->assertRedirect(route('customers.show', $customer));

    $customer->refresh();

    expect($customer->full_name)->toBe('Andi Wijaya Baru');
    expect($customer->activeEmployment->company_name)->toBe('PT Berubah Jaya');
    expect($customer->activeEmployment->estimated_monthly_income)->toBe(7000000);

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'CUSTOMER_UPDATED',
        'entity_type' => Customer::class,
        'entity_id' => $customer->id,
    ]);
});

test('unauthorized roles cannot create customers through the store endpoint', function () {
    $cashier = customerDemoUser('cashier@example.test');

    $this->actingAs($cashier)
        ->post(route('customers.store'), customerPayload())
        ->assertForbidden();

    $this->assertDatabaseCount('customers', 0);
});

test('customer detail page is accessible by view permission holders', function () {
    $lo = customerDemoUser('lo@example.test');
    $this->actingAs($lo)->post(route('customers.store'), customerPayload())->assertRedirect();

    $customer = Customer::where('full_name', 'Andi Wijaya')->firstOrFail();

    $this->actingAs($lo)
        ->get(route('customers.show', $customer))
        ->assertOk()
        ->assertSee($customer->customer_code);

    // AUDITOR has read-only customers.view
    $auditor = customerDemoUser('auditor@example.test');
    $this->actingAs($auditor)
        ->get(route('customers.show', $customer))
        ->assertOk();
});

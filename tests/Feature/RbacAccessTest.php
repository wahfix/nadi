<?php

use App\Models\Role;
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

function demoUser(string $email): User
{
    return User::where('email', $email)->firstOrFail();
}

$modules = [
    'customers.index' => 'customers.view',
    'loans.index' => 'loans.view',
    'installments.index' => 'installments.view',
    'payments.index' => 'payments.view',
    'collections.index' => 'collections.view',
    'collaterals.index' => 'collaterals.view',
    'verifications.index' => 'verifications.view',
    'releases.index' => 'releases.view',
    'users.index' => 'users.view',
    'reports.index' => 'reports.view',
    'audit-log.index' => 'audit_logs.view',
];

$roleAccounts = [
    'admin@example.test',
    'lo@example.test',
    'lc@example.test',
    'cashier@example.test',
    'collateral@example.test',
    'verifier@example.test',
    'auditor@example.test',
];

test('guests are redirected to login on every protected module page', function () use ($modules) {
    foreach (array_keys($modules) as $routeName) {
        $this->get(route($routeName))->assertRedirect(route('login'));
    }
});

test('module access strictly follows assigned role permissions', function (string $email) use ($modules) {
    $user = demoUser($email);
    $this->actingAs($user);

    foreach ($modules as $routeName => $permission) {
        $expectedStatus = $user->hasPermission($permission) ? 200 : 403;

        $this->get(route($routeName))
            ->assertStatus($expectedStatus);
    }
})->with($roleAccounts);

test('sidebar navigation reflects the granted permissions of the logged-in role', function () {
    $lo = demoUser('lo@example.test');
    $this->actingAs($lo)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Nasabah')
        ->assertSee('Pinjaman')
        ->assertDontSee('Jaminan');

    $collateral = demoUser('collateral@example.test');
    $this->actingAs($collateral)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Pengambilan Jaminan')
        ->assertDontSee('Verifikasi Identitas');

    $verifier = demoUser('verifier@example.test');
    $this->actingAs($verifier)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Verifikasi Identitas')
        ->assertDontSee('Jaminan');
});

test('only users with the manage permission can access the user create form', function () {
    $admin = demoUser('admin@example.test');
    $this->actingAs($admin)->get(route('users.create'))->assertOk();

    $lo = demoUser('lo@example.test');
    $this->actingAs($lo)->get(route('users.create'))->assertForbidden();

    $cashier = demoUser('cashier@example.test');
    $this->actingAs($cashier)->get(route('users.create'))->assertForbidden();
});

test('admin can create a new user with a role and the audit trail is recorded', function () {
    $admin = demoUser('admin@example.test');
    $role = Role::where('name', Role::LO)->firstOrFail();

    $this->actingAs($admin)
        ->post(route('users.store'), [
            'name' => 'Petugas Tes',
            'email' => 'petugas@example.test',
            'password' => 'password',
            'role_id' => $role->id,
        ])
        ->assertRedirect(route('users.index'));

    $created = User::where('email', 'petugas@example.test')->firstOrFail();

    expect($created->name)->toBe('Petugas Tes');
    expect($created->hasRole(Role::LO))->toBeTrue();

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'USER_CREATED',
        'entity_type' => User::class,
        'entity_id' => $created->id,
    ]);
});

test('user creation is validated server-side', function () {
    $admin = demoUser('admin@example.test');

    $this->actingAs($admin)
        ->from(route('users.create'))
        ->post(route('users.store'), [])
        ->assertRedirect(route('users.create'))
        ->assertSessionHasErrors(['name', 'email', 'password', 'role_id']);

    $this->assertDatabaseCount('users', 7);
});

test('unauthorized roles cannot create users through the store endpoint', function () {
    $lo = demoUser('lo@example.test');
    $role = Role::where('name', Role::LO)->firstOrFail();

    $this->actingAs($lo)
        ->post(route('users.store'), [
            'name' => 'Penyusup',
            'email' => 'penyusup@example.test',
            'password' => 'password',
            'role_id' => $role->id,
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('users', ['email' => 'penyusup@example.test']);
});

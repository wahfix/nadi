<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * Create a user with a single primary role and record an audit trail.
     *
     * @param  array{name: string, email: string, password: string, role_id: int}  $data
     */
    public function createUser(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $role = Role::findOrFail($data['role_id']);

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'email_verified_at' => now(),
            ]);

            $user->roles()->attach($role->id);

            $this->auditLogService->log(
                AuditLogService::USER_CREATED,
                $user,
                null,
                null,
                [
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $role->name,
                ],
            );

            return $user;
        });
    }
}

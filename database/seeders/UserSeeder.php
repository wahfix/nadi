<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $demoUsers = [
            [
                'name' => 'Administrator NADI',
                'email' => 'admin@example.test',
                'role' => Role::ADMIN,
            ],
            [
                'name' => 'Loan Officer Budi',
                'email' => 'lo@example.test',
                'role' => Role::LO,
            ],
            [
                'name' => 'Loan Collector Agus',
                'email' => 'lc@example.test',
                'role' => Role::LC,
            ],
            [
                'name' => 'Kasir Siti',
                'email' => 'cashier@example.test',
                'role' => Role::CASHIER,
            ],
            [
                'name' => 'Petugas Agunan Hendra',
                'email' => 'collateral@example.test',
                'role' => Role::COLLATERAL_OFFICER,
            ],
            [
                'name' => 'Petugas Verifikasi Rina',
                'email' => 'verifier@example.test',
                'role' => Role::IDENTITY_VERIFIER,
            ],
            [
                'name' => 'Auditor Dewi',
                'email' => 'auditor@example.test',
                'role' => Role::AUDITOR,
            ],
        ];

        foreach ($demoUsers as $userData) {
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            $role = Role::where('name', $userData['role'])->first();
            if ($role) {
                $user->roles()->syncWithoutDetaching([$role->id]);
            }
        }
    }
}

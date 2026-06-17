<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StaffSeeder extends Seeder
{
    public function run(): void
    {
        $staff = [
            ['name' => 'Rosa Mendoza', 'username' => 'reception', 'email' => 'reception@bonoandental.test', 'role' => UserRole::Receptionist],
            ['name' => 'Dr. Elena Santos', 'username' => 'drsantos', 'email' => 'dentist1@bonoandental.test', 'role' => UserRole::Dentist],
            ['name' => 'Dr. Marco Cruz', 'username' => 'drcruz', 'email' => 'dentist2@bonoandental.test', 'role' => UserRole::Dentist],
        ];

        foreach ($staff as $member) {
            User::updateOrCreate(
                ['email' => $member['email']],
                [
                    'name' => $member['name'],
                    'username' => $member['username'],
                    'role' => $member['role'],
                    'is_active' => true,
                    'email_verified_at' => now(),
                    'password' => Hash::make('Password123!'),
                ],
            );
        }
    }
}

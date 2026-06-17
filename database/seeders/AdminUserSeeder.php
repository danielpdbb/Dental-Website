<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed the default Management/Admin account.
     *
     * Idempotent: uses updateOrCreate keyed on the email so re-running the
     * seeder never creates duplicates.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'dental@admin.com'],
            [
                'name' => 'Clinic Administrator',
                'username' => 'admindental',
                'role' => UserRole::Management,
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => Hash::make('Bonoan123!'),
            ],
        );
    }
}

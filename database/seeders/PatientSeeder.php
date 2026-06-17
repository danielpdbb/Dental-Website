<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PatientSeeder extends Seeder
{
    public function run(): void
    {
        $patients = [
            ['first' => 'Juan', 'last' => 'Dela Cruz', 'gender' => 'Male', 'dob' => '1990-04-12', 'phone' => '0917-100-0001', 'blood' => 'O+', 'history' => 'Hypertension, controlled with medication.', 'allergies' => [['Penicillin', 'severe'], ['Latex', 'moderate']]],
            ['first' => 'Maria', 'last' => 'Reyes', 'gender' => 'Female', 'dob' => '1985-09-23', 'phone' => '0917-100-0002', 'blood' => 'A+', 'history' => 'No significant medical history.', 'allergies' => [['Ibuprofen', 'mild']]],
            ['first' => 'Pedro', 'last' => 'Bautista', 'gender' => 'Male', 'dob' => '2000-01-05', 'phone' => '0917-100-0003', 'blood' => 'B+', 'history' => 'Asthma.', 'allergies' => []],
            ['first' => 'Ana', 'last' => 'Villanueva', 'gender' => 'Female', 'dob' => '1995-12-30', 'phone' => '0917-100-0004', 'blood' => 'AB+', 'history' => 'Pregnant (2nd trimester).', 'allergies' => [['Aspirin', 'moderate']]],
            ['first' => 'Carlos', 'last' => 'Garcia', 'gender' => 'Male', 'dob' => '1978-07-18', 'phone' => '0917-100-0005', 'blood' => 'O-', 'history' => 'Diabetes type 2.', 'allergies' => []],
        ];

        foreach ($patients as $i => $p) {
            $n = $i + 1;

            $user = User::updateOrCreate(
                ['email' => "patient{$n}@bonoandental.test"],
                [
                    'name' => "{$p['first']} {$p['last']}",
                    'username' => "patient{$n}",
                    'role' => UserRole::Patient,
                    'is_active' => true,
                    'email_verified_at' => now(),
                    'password' => Hash::make('Password123!'),
                ],
            );

            $patient = Patient::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'first_name' => $p['first'],
                    'last_name' => $p['last'],
                    'date_of_birth' => $p['dob'],
                    'gender' => $p['gender'],
                    'phone' => $p['phone'],
                    'address' => 'Bonoan, Dagupan City, Pangasinan',
                    'emergency_contact_name' => 'Family Member',
                    'emergency_contact_phone' => '0917-200-000'.$n,
                    'blood_type' => $p['blood'],
                    'medical_history' => $p['history'],
                ],
            );

            $patient->allergies()->delete();
            foreach ($p['allergies'] as [$name, $severity]) {
                $patient->allergies()->create(['name' => $name, 'severity' => $severity]);
            }
        }

        // One walk-in style patient with no linked login account.
        Patient::updateOrCreate(
            ['first_name' => 'Walk-in', 'last_name' => 'Guest'],
            [
                'phone' => '0917-300-0000',
                'address' => 'Dagupan City',
                'medical_history' => 'Walk-in patient, no portal account.',
            ],
        );
    }
}

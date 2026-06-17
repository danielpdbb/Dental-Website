<?php

namespace Database\Seeders;

use App\Enums\RecommendationStatus;
use App\Enums\ReferralStatus;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;

class ClinicalRecordSeeder extends Seeder
{
    public function run(): void
    {
        $d1 = User::where('email', 'dentist1@bonoandental.test')->first();
        $d2 = User::where('email', 'dentist2@bonoandental.test')->first();

        $patients = Patient::whereNotNull('user_id')->orderBy('id')->get()->values();

        $rootCanal = Service::where('name', 'Root Canal Treatment')->first();
        $cleaning = Service::where('name', 'Dental Cleaning')->first();
        $filling = Service::where('name', 'Composite Filling')->first();
        $crown = Service::where('name', 'Dental Crown')->first();
        $whitening = Service::where('name', 'Teeth Whitening')->first();
        $braces = Service::where('name', 'Orthodontic Braces')->first();

        // Treatment history
        $patients[0]->treatments()->create([
            'dentist_id' => $d1->id,
            'service_id' => $rootCanal->id,
            'procedure_name' => 'Root canal on lower right molar (#46)',
            'treatment_date' => now()->subDays(7)->toDateString(),
            'notes' => 'Procedure successful. Crown recommended as follow-up.',
        ]);
        $patients[1]->treatments()->create([
            'dentist_id' => $d2->id,
            'service_id' => $cleaning->id,
            'procedure_name' => 'Routine cleaning and fluoride treatment',
            'treatment_date' => now()->subDays(30)->toDateString(),
            'notes' => 'Mild gingivitis noted; advised better flossing.',
        ]);
        $patients[4]->treatments()->create([
            'dentist_id' => $d1->id,
            'service_id' => $filling->id,
            'procedure_name' => 'Composite filling on upper left premolar (#24)',
            'treatment_date' => now()->subDays(60)->toDateString(),
            'notes' => 'Small cavity restored.',
        ]);

        // Procedure recommendations
        $patients[0]->recommendations()->create([
            'dentist_id' => $d1->id,
            'service_id' => $crown->id,
            'recommendation' => 'Dental crown on #46 following the root canal.',
            'status' => RecommendationStatus::Pending,
            'notes' => 'Schedule within 4 weeks to protect the treated tooth.',
        ]);
        $patients[1]->recommendations()->create([
            'dentist_id' => $d2->id,
            'service_id' => $whitening->id,
            'recommendation' => 'In-office teeth whitening for aesthetic improvement.',
            'status' => RecommendationStatus::Pending,
        ]);

        // Referrals
        $patients[2]->referrals()->create([
            'service_id' => $braces->id,
            'reason' => 'Requesting referral to an orthodontist for braces consultation.',
            'status' => ReferralStatus::Requested,
            'requested_by' => $patients[2]->user_id,
        ]);
        $patients[0]->referrals()->create([
            'reason' => 'Referral for oral surgery assessment of wisdom teeth.',
            'status' => ReferralStatus::InProgress,
            'requested_by' => $patients[0]->user_id,
            'handled_by' => User::where('email', 'reception@bonoandental.test')->value('id'),
            'notes' => 'Coordinating with partner clinic.',
        ]);
    }
}

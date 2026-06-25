<?php

namespace Database\Seeders;

use App\Enums\AdviceStatus;
use App\Enums\AppointmentStatus;
use App\Enums\ProcedureStatus;
use App\Enums\ToothCondition;
use App\Models\Appointment;
use App\Models\ToothRecord;
use App\Services\ML\AppointmentRecommender;
use Illuminate\Database\Seeder;

/**
 * Backfills the appointment-based clinical data introduced on 2026-06-24 — pre-visit
 * intakes, dentist findings, regression recommendations, odontogram tooth records and
 * itemised billing statements — onto existing COMPLETED visits, so every new screen
 * has realistic demo data. Idempotent: skips appointments already populated.
 */
class ClinicalDemoSeeder extends Seeder
{
    public function run(): void
    {
        $recommender = app(AppointmentRecommender::class);

        $appointments = Appointment::where('status', AppointmentStatus::Completed->value)
            ->whereHas('procedures', fn ($q) => $q->where('status', ProcedureStatus::Performed->value))
            ->with(['procedures', 'patient', 'dentist'])
            ->latest('scheduled_at')
            ->take(60)->get();

        if ($appointments->isEmpty()) {
            $this->command?->warn('ClinicalDemoSeeder: no completed appointments with performed procedures. Skipped.');

            return;
        }

        $fdiPool = array_keys(ToothRecord::FDI_UNIVERSAL);
        $conditions = [ToothCondition::Caries, ToothCondition::Filled, ToothCondition::Crown, ToothCondition::RootCanal, ToothCondition::Sealant];
        $meds = ['Amoxicillin 500mg', 'Mefenamic acid 500mg', 'Ibuprofen 400mg', 'Chlorhexidine rinse', null];
        $count = 0;

        foreach ($appointments as $appt) {
            if ($appt->intake()->exists()) {
                continue; // already populated
            }

            // Stage-1 pre-visit intake.
            $appt->intake()->create([
                'main_concern' => fake()->randomElement(['Toothache', 'Routine check-up', 'Sensitivity', 'Bleeding gums', 'Cleaning']),
                'pain_level' => random_int(0, 9),
                'toothache' => fake()->boolean(45),
                'sensitivity' => fake()->boolean(40),
                'bleeding_gums' => fake()->boolean(30),
                'bad_breath' => fake()->boolean(25),
                'swelling' => fake()->boolean(20),
                'brushing_per_day' => random_int(1, 3),
                'flosses' => fake()->boolean(40),
                'smoker' => fake()->boolean(20),
                'sugar_level' => fake()->randomElement(['low', 'medium', 'high']),
                'months_since_cleaning' => random_int(3, 24),
                'last_visit_bucket' => fake()->randomElement(['under_6m', '6_12m', 'more_than_1y']),
                'submitted_by' => $appt->patient?->user_id,
            ]);

            // Stage-2 clinical findings.
            $appt->finding()->create([
                'cavity_found' => fake()->boolean(50),
                'gum_inflammation' => fake()->randomElement(['none', 'mild', 'moderate', 'severe']),
                'plaque_level' => fake()->randomElement(['low', 'medium', 'high']),
                'tooth_mobility' => fake()->boolean(15),
                'infection_signs' => fake()->randomElement(['none', 'none', 'possible', 'present']),
                'xray_needed' => fake()->boolean(35),
                'treatment_done_today' => $appt->procedures->first()?->procedure_name,
                'remarks' => fake()->randomElement(['Needs follow-up treatment.', 'Good oral hygiene.', 'Advised better flossing.', null]),
                'recorded_by' => $appt->dentist_id,
            ]);

            // Regression recommendations (Stage 1 + Stage 2); accept & send the next-visit one.
            $appt->refresh()->load(['intake', 'finding', 'patient']);
            $recommender->generateStage1($appt);
            $stage2 = $recommender->generateStage2($appt);
            if (fake()->boolean(70)) {
                $stage2->update([
                    'status' => AdviceStatus::Accepted,
                    'accepted_by' => $appt->dentist_id,
                    'accepted_at' => $appt->scheduled_at,
                    'suggested_at' => $appt->scheduled_at->copy()->addWeeks($stage2->follow_up_weeks ?: 4),
                    'sent_to_patient_at' => fake()->boolean(80) ? $appt->scheduled_at : null,
                ]);
            }

            // A few odontogram tooth records.
            $teeth = fake()->randomElements($fdiPool, random_int(2, 5));
            foreach ($teeth as $fdi) {
                $appt->toothRecords()->create([
                    'fdi_number' => $fdi,
                    'condition' => fake()->randomElement($conditions),
                    'treatment_done' => fake()->randomElement(['Composite filling', 'Scaling', 'Crown prep', 'RCT', null]),
                    'medicine_given' => fake()->randomElement($meds),
                    'observation' => fake()->randomElement(['Mild discomfort on percussion.', 'Restoration intact.', null]),
                    'surfaces' => fake()->randomElements(['M', 'O', 'D', 'B', 'L'], random_int(0, 2)),
                    'recorded_by' => $appt->dentist_id,
                ]);
            }

            // Itemised billing statement + invoice (these visits are paid).
            $performed = $appt->procedures->where('status', ProcedureStatus::Performed);
            $subtotal = round((float) $performed->sum('price'), 2);
            $statement = $appt->billingStatement()->updateOrCreate(
                ['appointment_id' => $appt->id],
                [
                    'statement_no' => 'BS-'.$appt->scheduled_at->format('Ymd').'-'.str_pad((string) $appt->id, 5, '0', STR_PAD_LEFT),
                    'subtotal' => $subtotal, 'discount' => 0, 'total' => $subtotal,
                    'created_by' => $appt->dentist_id, 'issued_at' => $appt->scheduled_at,
                ]
            );
            $statement->items()->delete();
            foreach ($performed as $p) {
                $statement->items()->create([
                    'appointment_procedure_id' => $p->id,
                    'description' => $p->procedure_name,
                    'quantity' => 1, 'unit_price' => $p->price, 'line_total' => $p->price,
                ]);
            }
            if (! $statement->invoice_no) {
                $statement->update([
                    'invoice_no' => 'INV-'.$appt->scheduled_at->format('Ymd').'-'.str_pad((string) $appt->id, 5, '0', STR_PAD_LEFT),
                    'paid_at' => $appt->scheduled_at,
                ]);
            }

            $count++;
        }

        $this->command?->info("ClinicalDemoSeeder: populated {$count} completed visits with clinical + billing demo data.");
    }
}

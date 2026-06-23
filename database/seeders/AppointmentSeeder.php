<?php

namespace Database\Seeders;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;

class AppointmentSeeder extends Seeder
{
    public function run(): void
    {
        $reception = User::where('email', 'reception@bonoandental.test')->first();
        $d1 = User::where('email', 'dentist1@bonoandental.test')->first();
        $d2 = User::where('email', 'dentist2@bonoandental.test')->first();

        $patients = Patient::whereNotNull('user_id')->orderBy('id')->get()->values();
        $walkIn = Patient::whereNull('user_id')->first();

        $cleaning = Service::where('name', 'Dental Cleaning')->first();
        $rootCanal = Service::where('name', 'Root Canal Treatment')->first();
        $filling = Service::where('name', 'Composite Filling')->first();
        $whitening = Service::where('name', 'Teeth Whitening')->first();
        $braces = Service::where('name', 'Orthodontic Braces')->first();

        // Completed + fully paid — patient 1, dentist 1
        $a1 = Appointment::create([
            'patient_id' => $patients[0]->id, 'dentist_id' => $d1->id, 'service_id' => $rootCanal->id,
            'scheduled_at' => now()->subDays(7)->setTime(10, 0), 'duration_minutes' => $rootCanal->duration_minutes,
            'total_amount' => $rootCanal->price, 'status' => AppointmentStatus::Completed, 'created_by' => $reception->id,
        ]);
        $a1->payments()->create([
            'amount' => $rootCanal->price, 'method' => PaymentMethod::Cash, 'status' => PaymentStatus::Paid,
            'gateway' => 'manual', 'paid_at' => now()->subDays(7)->setTime(11, 30), 'recorded_by' => $reception->id,
        ]);

        // Completed + PARTIALLY paid — patient 5 (braces, big ticket): paid 10k of 30k
        $a2 = Appointment::create([
            'patient_id' => $patients[4]->id, 'dentist_id' => $d2->id, 'service_id' => $braces->id,
            'scheduled_at' => now()->subDays(10)->setTime(9, 0), 'duration_minutes' => $braces->duration_minutes,
            'total_amount' => $braces->price, 'status' => AppointmentStatus::Completed, 'created_by' => $reception->id,
        ]);
        $a2->payments()->create([
            'amount' => 10000, 'method' => PaymentMethod::Gcash, 'status' => PaymentStatus::Paid,
            'gateway' => 'manual', 'paid_at' => now()->subDays(10)->setTime(10, 0), 'recorded_by' => $reception->id,
            'notes' => 'Initial down payment (installment).',
        ]);

        // A couple of booked (not-yet-completed) appointments dated 2026-06-23 — the
        // boundary date — so the dentist→billing workflow is testable out of the box.
        // No appointment is seeded later than this; the user creates future ones.
        Appointment::create([
            'patient_id' => $patients[0]->id, 'dentist_id' => $d1->id, 'service_id' => $cleaning->id,
            'scheduled_at' => \Illuminate\Support\Carbon::create(2026, 6, 23, 9, 0), 'duration_minutes' => $cleaning->duration_minutes,
            'total_amount' => $cleaning->price, 'status' => AppointmentStatus::Booked, 'created_by' => $patients[0]->user_id,
        ]);
        Appointment::create([
            'patient_id' => $patients[1]->id, 'dentist_id' => $d2->id, 'service_id' => $whitening->id,
            'scheduled_at' => \Illuminate\Support\Carbon::create(2026, 6, 23, 13, 0), 'duration_minutes' => $whitening->duration_minutes,
            'total_amount' => $whitening->price, 'status' => AppointmentStatus::Booked, 'created_by' => $patients[1]->user_id,
        ]);

        // Cancelled — patient 2
        Appointment::create([
            'patient_id' => $patients[1]->id, 'dentist_id' => $d1->id, 'service_id' => $filling->id,
            'scheduled_at' => now()->subDays(2)->setTime(14, 0), 'duration_minutes' => $filling->duration_minutes,
            'total_amount' => $filling->price, 'status' => AppointmentStatus::Cancelled, 'created_by' => $patients[1]->user_id,
            'cancelled_by' => $patients[1]->user_id, 'cancelled_at' => now()->subDays(3),
            'cancellation_reason' => 'Patient had a schedule conflict.',
        ]);

        // No-show — patient 3
        Appointment::create([
            'patient_id' => $patients[2]->id, 'dentist_id' => $d2->id, 'service_id' => $cleaning->id,
            'scheduled_at' => now()->subDays(1)->setTime(15, 0), 'duration_minutes' => $cleaning->duration_minutes,
            'total_amount' => $cleaning->price, 'status' => AppointmentStatus::NoShow, 'created_by' => $reception->id,
        ]);

        // Walk-in completed + paid (GCash)
        if ($walkIn) {
            $w = Appointment::create([
                'patient_id' => $walkIn->id, 'dentist_id' => $d1->id, 'service_id' => $filling->id,
                'scheduled_at' => now()->subDays(4)->setTime(11, 0), 'duration_minutes' => $filling->duration_minutes,
                'total_amount' => $filling->price, 'status' => AppointmentStatus::Completed, 'is_walk_in' => true,
                'created_by' => $reception->id,
            ]);
            $w->payments()->create([
                'amount' => $filling->price, 'method' => PaymentMethod::Gcash, 'status' => PaymentStatus::Paid,
                'gateway' => 'manual', 'paid_at' => now()->subDays(4)->setTime(11, 45), 'recorded_by' => $reception->id,
            ]);
        }

        // Give every seeded appointment a matching procedure line item.
        Appointment::with('service')->whereDoesntHave('procedures')->get()->each(function (Appointment $a) {
            $done = $a->status === AppointmentStatus::Completed;
            $a->procedures()->create([
                'service_id' => $a->service_id,
                'procedure_name' => $a->service?->name ?? 'Procedure',
                'price' => $a->total_amount > 0 ? $a->total_amount : ($a->service?->price ?? 0),
                'duration_minutes' => $a->duration_minutes,
                'status' => $done ? \App\Enums\ProcedureStatus::Performed : \App\Enums\ProcedureStatus::Planned,
                'performed_by' => $done ? $a->dentist_id : null,
                'performed_at' => $done ? $a->scheduled_at : null,
            ]);
        });
    }
}

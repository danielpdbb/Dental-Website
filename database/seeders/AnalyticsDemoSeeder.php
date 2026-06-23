<?php

namespace Database\Seeders;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Generates ~12 months of synthetic appointments + payments so the analytics
 * dashboard (and, later, the ML models) have realistic volume to work with.
 *
 * Standalone & guarded: run with `php artisan db:seed --class=AnalyticsDemoSeeder`.
 * Skips itself if plenty of historical data already exists, so repeat runs don't
 * keep piling on.
 */
class AnalyticsDemoSeeder extends Seeder
{
    public function run(): void
    {
        $patients = Patient::pluck('id')->all();
        $services = Service::all();
        $dentists = User::where('role', UserRole::Dentist)->pluck('id')->all();
        $staffId = User::whereIn('role', [UserRole::Receptionist, UserRole::Management])->value('id');

        if (empty($patients) || $services->isEmpty() || empty($dentists)) {
            $this->command?->warn('AnalyticsDemoSeeder: need patients, services and dentists first. Skipped.');

            return;
        }

        $alreadyHistoric = \App\Models\Appointment::where('scheduled_at', '<', now()->subMonths(2))->count();
        if ($alreadyHistoric >= 80) {
            $this->command?->info('AnalyticsDemoSeeder: historical data already present. Skipped.');

            return;
        }

        $openHours = range(
            (int) substr(config('clinic.open_time', '09:00'), 0, 2),
            (int) substr(config('clinic.close_time', '17:00'), 0, 2) - 1
        );

        $made = 0;

        // Do not seed anything later than this — the user creates future appointments.
        $cap = Carbon::create(2026, 6, 23, 23, 59, 59);

        // 12 months back → today.
        for ($monthsAgo = 11; $monthsAgo >= 0; $monthsAgo--) {
            $monthStart = now()->copy()->startOfMonth()->subMonths($monthsAgo);
            $perMonth = random_int(18, 34);

            for ($i = 0; $i < $perMonth; $i++) {
                $day = $monthStart->copy()->addDays(random_int(0, $monthStart->daysInMonth - 1));

                // Clinic is closed Sundays — nudge to Saturday.
                if ($day->isoWeekday() === 7) {
                    $day->subDay();
                }

                $hour = $openHours[array_rand($openHours)];
                $minute = [0, 30][array_rand([0, 30])];
                $scheduledAt = $day->copy()->setTime($hour, $minute);

                // Skip anything past the cap date.
                if ($scheduledAt->gt($cap)) {
                    continue;
                }

                /** @var Service $service */
                $service = $services->random();
                $status = $this->weightedStatus();

                $appointment = \App\Models\Appointment::create([
                    'patient_id' => $patients[array_rand($patients)],
                    'dentist_id' => $dentists[array_rand($dentists)],
                    'service_id' => $service->id,
                    'scheduled_at' => $scheduledAt,
                    'duration_minutes' => $service->duration_minutes,
                    'total_amount' => $service->price,
                    'status' => $status,
                    'is_walk_in' => random_int(1, 100) <= 15,
                    'created_by' => $staffId,
                    'created_at' => $scheduledAt->copy()->subDays(random_int(0, 30)),
                ]);

                // One procedure line item per appointment (mirrors the service).
                $appointment->procedures()->create([
                    'service_id' => $service->id,
                    'procedure_name' => $service->name,
                    'price' => $service->price,
                    'duration_minutes' => $service->duration_minutes,
                    'status' => $status === AppointmentStatus::Completed
                        ? \App\Enums\ProcedureStatus::Performed
                        : \App\Enums\ProcedureStatus::Planned,
                    'performed_by' => $status === AppointmentStatus::Completed ? $appointment->dentist_id : null,
                    'performed_at' => $status === AppointmentStatus::Completed ? $appointment->scheduled_at : null,
                ]);

                // Payments only for visits that happened.
                if ($status === AppointmentStatus::Completed) {
                    $this->addPayments($appointment, (float) $service->price, $staffId);
                }

                $made++;
            }
        }

        $this->command?->info("AnalyticsDemoSeeder: created {$made} synthetic appointments.");
    }

    private function weightedStatus(): AppointmentStatus
    {
        $roll = random_int(1, 100);

        return match (true) {
            $roll <= 68 => AppointmentStatus::Completed,
            $roll <= 80 => AppointmentStatus::NoShow,
            default => AppointmentStatus::Cancelled,
        };
    }

    private function addPayments(\App\Models\Appointment $appointment, float $price, ?int $staffId): void
    {
        $method = [PaymentMethod::Cash, PaymentMethod::Gcash, PaymentMethod::Card][array_rand([0, 1, 2])];
        $fullyPaid = random_int(1, 100) <= 80;

        $amount = $fullyPaid ? $price : round($price * (random_int(40, 90) / 100), 2);

        $appointment->payments()->create([
            'amount' => $amount,
            'method' => $method,
            'status' => PaymentStatus::Paid,
            'gateway' => 'manual',
            'paid_at' => $appointment->scheduled_at,
            'recorded_by' => $staffId,
        ]);
    }
}

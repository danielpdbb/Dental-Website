<?php

namespace Database\Seeders;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ProcedureStatus;
use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\Service;
use App\Models\ToothRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Generates ~12 months of realistic synthetic appointments + payments so the analytics
 * dashboard AND the ML models have enough volume and *structure* to work with.
 *
 * Two things make this dataset useful for training (not just a pile of rows):
 *  - A realistic patient base (~120 patients) so visits spread out and the
 *    prior-visits / prior-no-show features actually vary per patient.
 *  - "Kept vs missed" is NOT random — it depends on each patient's reliability and the
 *    booking lead time, so the scheduling Decision Tree has a genuine signal to learn.
 *
 * Standalone & guarded: run with `php artisan db:seed --class=AnalyticsDemoSeeder`.
 * Skips itself if plenty of historical data already exists, so repeat runs don't pile on.
 */
class AnalyticsDemoSeeder extends Seeder
{
    /** Target size of the synthetic patient base. */
    private const PATIENT_TARGET = 120;

    /** Services that apply to a specific tooth (get an FDI number); the rest are whole-mouth. */
    private const TOOTH_SPECIFIC = [
        'Tooth Extraction', 'Composite Filling', 'Root Canal Treatment', 'Dental Crown', 'Dental Implant',
    ];

    /** Per-patient reliability (0–1): how likely they are to keep an appointment. */
    private array $reliability = [];

    public function run(): void
    {
        $services = Service::all();
        $dentists = User::where('role', UserRole::Dentist)->pluck('id')->all();
        $staffId = User::whereIn('role', [UserRole::Receptionist, UserRole::Management])->value('id');

        if ($services->isEmpty() || empty($dentists)) {
            $this->command?->warn('AnalyticsDemoSeeder: need services and dentists first. Skipped.');

            return;
        }

        $alreadyHistoric = \App\Models\Appointment::where('scheduled_at', '<', now()->subMonths(2))->count();
        if ($alreadyHistoric >= 80) {
            $this->command?->info('AnalyticsDemoSeeder: historical data already present. Skipped.');

            return;
        }

        $patients = $this->ensurePatientBase();
        $fdiPool = array_keys(ToothRecord::FDI_UNIVERSAL);

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
            $perMonth = random_int(52, 68);

            for ($i = 0; $i < $perMonth; $i++) {
                $day = $monthStart->copy()->addDays(random_int(0, $monthStart->daysInMonth - 1));

                // Clinic is closed Sundays — nudge to Saturday.
                if ($day->isoWeekday() === 7) {
                    $day->subDay();
                }

                $hour = $openHours[array_rand($openHours)];
                $minute = [0, 30][array_rand([0, 30])];
                $scheduledAt = $day->copy()->setTime($hour, $minute);

                if ($scheduledAt->gt($cap)) {
                    continue; // skip anything past the cap date
                }

                $patientId = $patients[array_rand($patients)];
                $isWalkIn = random_int(1, 100) <= 15;
                $createdAt = $isWalkIn
                    ? $scheduledAt->copy()
                    : $scheduledAt->copy()->subDays(random_int(0, 45));
                $leadDays = (int) round($createdAt->diffInDays($scheduledAt));

                $status = $this->decideStatus($patientId, $leadDays, $isWalkIn);

                // Build the procedure lines first so the appointment total is their sum.
                $lines = $this->procedureLines($services, $status === AppointmentStatus::Completed, $fdiPool);
                $total = array_sum(array_column($lines, 'price'));
                $duration = array_sum(array_column($lines, 'duration_minutes'));

                $appointment = \App\Models\Appointment::create([
                    'patient_id' => $patientId,
                    'dentist_id' => $dentists[array_rand($dentists)],
                    'service_id' => $lines[0]['service_id'],
                    'scheduled_at' => $scheduledAt,
                    'duration_minutes' => $duration,
                    'total_amount' => $total,
                    'status' => $status,
                    'is_walk_in' => $isWalkIn,
                    'created_by' => $staffId,
                    'created_at' => $createdAt,
                ]);

                foreach ($lines as $line) {
                    $appointment->procedures()->create($line + [
                        'status' => $status === AppointmentStatus::Completed
                            ? ProcedureStatus::Performed
                            : ProcedureStatus::Planned,
                        'performed_by' => $status === AppointmentStatus::Completed ? $appointment->dentist_id : null,
                        'performed_at' => $status === AppointmentStatus::Completed ? $appointment->scheduled_at : null,
                    ]);
                }

                // Payments only for visits that happened (completed = paid in full).
                if ($status === AppointmentStatus::Completed) {
                    $this->addPayments($appointment, (float) $total, $staffId);
                }

                $made++;
            }
        }

        $this->command?->info("AnalyticsDemoSeeder: created {$made} synthetic appointments across ".count($patients).' patients.');
    }

    /**
     * Make sure a realistic patient base exists, returning all patient ids. Bulk patients
     * are account-less records (like walk-ins) — enough for analytics, search and the
     * scheduling model without creating throwaway logins.
     *
     * @return list<int>
     */
    private function ensurePatientBase(): array
    {
        $first = ['Juan', 'Maria', 'Jose', 'Andrea', 'Mark', 'Angelica', 'Carlo', 'Patricia', 'Daniel', 'Kristine',
            'Miguel', 'Sofia', 'Gabriel', 'Bea', 'Rafael', 'Camille', 'Paolo', 'Trisha', 'Enrico', 'Joy',
            'Nathaniel', 'Erika', 'Vincent', 'Aira', 'Francis', 'Mae', 'Lorenzo', 'Hannah', 'Diego', 'Reyna'];
        $last = ['Dela Cruz', 'Reyes', 'Santos', 'Bautista', 'Garcia', 'Mendoza', 'Torres', 'Flores', 'Villanueva',
            'Ramos', 'Aquino', 'Castillo', 'Domingo', 'Navarro', 'Salazar', 'Mercado', 'Pascual', 'Gonzales',
            'Rivera', 'Cruz', 'Fernandez', 'Aguilar', 'Soriano', 'Velasco', 'Magtangol'];

        $needed = self::PATIENT_TARGET - Patient::count();
        for ($i = 0; $i < $needed; $i++) {
            Patient::create([
                'first_name' => $first[array_rand($first)],
                'last_name' => $last[array_rand($last)],
                'date_of_birth' => now()->subYears(random_int(7, 78))->subDays(random_int(0, 364))->toDateString(),
                'gender' => ['Male', 'Female'][array_rand([0, 1])],
                'phone' => '09'.random_int(100000000, 999999999),
                'address' => 'Bonoan, Dagupan City, Pangasinan',
                'medical_history' => 'No significant medical history.',
            ]);
        }

        return Patient::pluck('id')->all();
    }

    /**
     * Decide the outcome. Reliability (per patient) + booking lead time drive the odds, so
     * the Decision Tree learns "long lead + low-reliability patient ⇒ higher miss risk".
     */
    private function decideStatus(int $patientId, int $leadDays, bool $walkIn): AppointmentStatus
    {
        $reliability = $this->reliability[$patientId] ??= mt_rand(62, 97) / 100;

        $missChance = (1 - $reliability)
            + ($leadDays > 30 ? 0.10 : ($leadDays > 14 ? 0.04 : 0))
            + ($walkIn ? -0.06 : 0); // walk-ins are already there, rarely "miss"
        $missChance = max(0.02, min(0.6, $missChance));

        if (mt_rand() / mt_getrandmax() < $missChance) {
            return random_int(1, 100) <= 62 ? AppointmentStatus::NoShow : AppointmentStatus::Cancelled;
        }

        return AppointmentStatus::Completed;
    }

    /**
     * One or (occasionally) two procedure lines, with tooth numbers on tooth-specific ones.
     *
     * @return list<array<string, mixed>>
     */
    private function procedureLines(\Illuminate\Support\Collection $services, bool $completed, array $fdiPool): array
    {
        $lines = [$this->lineFor($services->random(), $fdiPool)];

        // ~22% of visits bundle a second procedure (e.g. cleaning + a filling).
        if (random_int(1, 100) <= 22) {
            $lines[] = $this->lineFor($services->random(), $fdiPool);
        }

        return $lines;
    }

    private function lineFor(Service $service, array $fdiPool): array
    {
        $toothSpecific = in_array($service->name, self::TOOTH_SPECIFIC, true);

        return [
            'service_id' => $service->id,
            'procedure_name' => $service->name,
            'tooth_fdi' => $toothSpecific ? $fdiPool[array_rand($fdiPool)] : null,
            'price' => $service->price,
            'duration_minutes' => $service->duration_minutes,
        ];
    }

    private function addPayments(\App\Models\Appointment $appointment, float $price, ?int $staffId): void
    {
        $method = [PaymentMethod::Cash, PaymentMethod::Gcash, PaymentMethod::Card][array_rand([0, 1, 2])];

        $appointment->payments()->create([
            'amount' => $price,
            'method' => $method,
            'status' => PaymentStatus::Paid,
            'gateway' => 'manual',
            'paid_at' => $appointment->scheduled_at,
            'recorded_by' => $staffId,
        ]);
    }
}

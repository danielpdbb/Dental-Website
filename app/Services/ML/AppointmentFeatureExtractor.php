<?php

namespace App\Services\ML;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;

/**
 * Turns appointments into the numeric feature vectors the scheduling Decision Tree
 * learns from / predicts on. Feature order is fixed and shared by training and
 * prediction so the model always sees the same columns.
 *
 * Label: "kept" (Completed) vs "missed" (No-show / Cancelled).
 */
class AppointmentFeatureExtractor
{
    /** Human-readable feature names (same order as vector()). */
    public const FEATURES = [
        'lead_time_days', 'day_of_week', 'hour', 'is_walk_in',
        'duration_minutes', 'total_amount', 'patient_age', 'prior_visits', 'prior_no_shows',
    ];

    /**
     * Build [samples, labels] from every appointment that has a final outcome.
     * Prior-visit / prior-no-show counts are computed chronologically so each row
     * only "knows" what happened before it (no leakage from the future).
     *
     * @return array{0: list<list<float>>, 1: list<string>}
     */
    public function trainingData(): array
    {
        $appointments = Appointment::with('patient')->orderBy('scheduled_at')->orderBy('id')->get();

        $priorVisits = [];
        $priorNoShows = [];
        $samples = [];
        $labels = [];

        foreach ($appointments as $a) {
            $pid = $a->patient_id;
            $pv = $priorVisits[$pid] ?? 0;
            $pn = $priorNoShows[$pid] ?? 0;

            if (($label = $this->label($a)) !== null) {
                $samples[] = $this->vector(
                    $a->scheduled_at, (int) $a->duration_minutes, (float) $a->total_amount,
                    (bool) $a->is_walk_in, $this->ageOf($a->patient), $pv, $pn, $a->created_at,
                );
                $labels[] = $label;
            }

            $priorVisits[$pid] = $pv + 1;
            if ($a->status === AppointmentStatus::NoShow) {
                $priorNoShows[$pid] = $pn + 1;
            }
        }

        return [$samples, $labels];
    }

    public function label(Appointment $a): ?string
    {
        return match ($a->status) {
            AppointmentStatus::Completed => 'kept',
            AppointmentStatus::NoShow, AppointmentStatus::Cancelled => 'missed',
            default => null, // booked / in-progress have no outcome yet
        };
    }

    /** Feature vector for an existing appointment (for a risk badge). */
    public function appointmentVector(Appointment $a): array
    {
        [$pv, $pn] = $this->priorStats($a->patient, $a->scheduled_at, $a->id);

        return $this->vector(
            $a->scheduled_at, (int) $a->duration_minutes, (float) $a->total_amount,
            (bool) $a->is_walk_in, $this->ageOf($a->patient), $pv, $pn, $a->created_at,
        );
    }

    /** Feature vector for a prospective slot (for ranking optimal times). */
    public function slotVector(?Patient $patient, Carbon $start, int $duration, float $amount): array
    {
        [$pv, $pn] = $patient ? $this->priorStats($patient) : [0, 0];

        return $this->vector($start, $duration, $amount, false, $this->ageOf($patient), $pv, $pn, now());
    }

    /**
     * @return list<float>
     */
    private function vector(Carbon $start, int $duration, float $amount, bool $walkIn, int $age, int $priorVisits, int $priorNoShows, ?Carbon $createdAt): array
    {
        $lead = $createdAt ? max(0, (int) round($createdAt->diffInDays($start))) : 0;

        return [
            (float) $lead,
            (float) $start->isoWeekday(),
            (float) $start->hour,
            $walkIn ? 1.0 : 0.0,
            (float) $duration,
            (float) $amount,
            (float) $age,
            (float) $priorVisits,
            (float) $priorNoShows,
        ];
    }

    /** @return array{0:int,1:int} [visits, no_shows] prior to $before */
    private function priorStats(?Patient $patient, ?Carbon $before = null, ?int $ignoreId = null): array
    {
        if (! $patient) {
            return [0, 0];
        }

        $base = $patient->appointments();
        if ($before) {
            $base->where('scheduled_at', '<', $before);
        }
        if ($ignoreId) {
            $base->where('id', '!=', $ignoreId);
        }

        return [
            (clone $base)->count(),
            (clone $base)->where('status', AppointmentStatus::NoShow->value)->count(),
        ];
    }

    private function ageOf(?Patient $patient): int
    {
        return $patient?->date_of_birth ? (int) $patient->date_of_birth->age : 0;
    }
}

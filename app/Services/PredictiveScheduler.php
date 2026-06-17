<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Simple, rules-based "next free slots" suggester (NOT machine learning).
 *
 * It builds candidate slots from the clinic's opening hours (config/clinic.php),
 * drops past slots and any that overlap the dentist's existing appointments, and
 * returns the earliest free ones — rolling forward day by day until it has enough.
 */
class PredictiveScheduler
{
    /**
     * @return Collection<int, Carbon>  the suggested start times
     */
    public function suggestSlots(User $dentist, int $durationMinutes, Carbon $fromDate, int $count = 6): Collection
    {
        $slots = collect();
        $day = $fromDate->copy()->startOfDay();
        $openDays = config('clinic.open_days');
        $slotStep = (int) config('clinic.slot_minutes');
        $safetyLimit = 30; // don't scan more than ~30 days ahead

        for ($scanned = 0; $scanned < $safetyLimit && $slots->count() < $count; $scanned++, $day->addDay()) {
            if (! in_array($day->isoWeekday(), $openDays, true)) {
                continue;
            }

            $open = $day->copy()->setTimeFromTimeString(config('clinic.open_time'));
            $close = $day->copy()->setTimeFromTimeString(config('clinic.close_time'));
            $existing = $this->existingAppointments($dentist, $day);

            for ($cursor = $open->copy(); $cursor->copy()->addMinutes($durationMinutes)->lte($close); $cursor->addMinutes($slotStep)) {
                $start = $cursor->copy();

                if ($start->isPast()) {
                    continue;
                }
                if ($this->overlaps($start, $durationMinutes, $existing)) {
                    continue;
                }

                $slots->push($start);
                if ($slots->count() >= $count) {
                    break;
                }
            }
        }

        return $slots->take($count);
    }

    /**
     * Every slot for a given day with an availability flag — for the booking grid.
     * Returns an empty collection if the clinic is closed that day.
     *
     * @return Collection<int, array{time: Carbon, available: bool}>
     */
    public function daySlots(User $dentist, int $durationMinutes, Carbon $date): Collection
    {
        $slots = collect();

        if (! in_array($date->isoWeekday(), config('clinic.open_days'), true)) {
            return $slots;
        }

        $open = $date->copy()->setTimeFromTimeString(config('clinic.open_time'));
        $close = $date->copy()->setTimeFromTimeString(config('clinic.close_time'));
        $step = (int) config('clinic.slot_minutes');

        for ($cursor = $open->copy(); $cursor->copy()->addMinutes($durationMinutes)->lte($close); $cursor->addMinutes($step)) {
            $start = $cursor->copy();
            $slots->push([
                'time' => $start,
                'available' => $start->isFuture() && $this->isSlotAvailable($dentist, $start, $durationMinutes),
            ]);
        }

        return $slots;
    }

    /**
     * Is a specific start time free for this dentist?
     */
    public function isSlotAvailable(User $dentist, Carbon $start, int $durationMinutes, ?int $ignoreAppointmentId = null): bool
    {
        $existing = $this->existingAppointments($dentist, $start)
            ->when($ignoreAppointmentId, fn ($c) => $c->where('id', '!=', $ignoreAppointmentId));

        return ! $this->overlaps($start, $durationMinutes, $existing);
    }

    private function existingAppointments(User $dentist, Carbon $day): Collection
    {
        return Appointment::where('dentist_id', $dentist->id)
            ->whereDate('scheduled_at', $day->toDateString())
            ->where('status', '!=', AppointmentStatus::Cancelled->value)
            ->get(['id', 'scheduled_at', 'duration_minutes']);
    }

    private function overlaps(Carbon $start, int $durationMinutes, Collection $existing): bool
    {
        $end = $start->copy()->addMinutes($durationMinutes);

        return $existing->contains(function (Appointment $a) use ($start, $end) {
            $aStart = $a->scheduled_at;
            $aEnd = $a->scheduled_at->copy()->addMinutes($a->duration_minutes);

            return $start->lt($aEnd) && $end->gt($aStart);
        });
    }
}

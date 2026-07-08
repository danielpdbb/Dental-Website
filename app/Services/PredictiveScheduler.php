<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\DentistSchedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Slot engine for booking. Builds candidate slots from the DENTIST's working hours
 * (customisable weekly rules + specific-date blocks in dentist_schedules, falling
 * back to the clinic defaults in config/clinic.php), drops past slots and any that
 * overlap existing appointments.
 *
 * Also provides a month overview (open / fully booked / closed / past per day) for
 * the calendar-style pickers.
 */
class PredictiveScheduler
{
    /** How far ahead booking is allowed (calendar + validation). */
    public const MAX_MONTHS_AHEAD = 3;

    /** @var array<int, Collection<int, DentistSchedule>> per-dentist rule cache */
    private array $rules = [];

    /**
     * The dentist's working window for a date, or null if they're off / clinic closed.
     * Date override → weekly rule → clinic default, in that order.
     *
     * @return array{0: Carbon, 1: Carbon}|null [open, close]
     */
    public function hoursFor(User $dentist, Carbon $date): ?array
    {
        $rules = $this->rules[$dentist->id] ??= DentistSchedule::where('dentist_id', $dentist->id)->get();

        $override = $rules->first(fn ($r) => $r->date && $r->date->isSameDay($date));
        $weekly = $rules->first(fn ($r) => $r->weekday === $date->isoWeekday() && ! $r->date);

        $rule = $override ?? $weekly;

        if ($rule?->is_off) {
            return null;
        }

        // No rule at all → clinic defaults (closed on non-open days).
        if (! $rule && ! in_array($date->isoWeekday(), config('clinic.open_days'), true)) {
            return null;
        }

        $start = $rule?->start_time ?: config('clinic.open_time');
        $end = $rule?->end_time ?: config('clinic.close_time');

        $open = $date->copy()->setTimeFromTimeString($start);
        $close = $date->copy()->setTimeFromTimeString($end);

        return $open->lt($close) ? [$open, $close] : null;
    }

    /**
     * @return Collection<int, Carbon>  the next free start times
     */
    public function suggestSlots(User $dentist, int $durationMinutes, Carbon $fromDate, int $count = 6): Collection
    {
        $slots = collect();
        $day = $fromDate->copy()->startOfDay();
        $slotStep = (int) config('clinic.slot_minutes');
        $safetyLimit = 45; // don't scan more than ~45 days ahead

        for ($scanned = 0; $scanned < $safetyLimit && $slots->count() < $count; $scanned++, $day->addDay()) {
            $hours = $this->hoursFor($dentist, $day);
            if (! $hours) {
                continue;
            }
            [$open, $close] = $hours;
            $existing = $this->existingAppointments($dentist, $day);

            for ($cursor = $open->copy(); $cursor->copy()->addMinutes($durationMinutes)->lte($close); $cursor->addMinutes($slotStep)) {
                $start = $cursor->copy();

                if ($start->isPast() || $this->overlaps($start, $durationMinutes, $existing)) {
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
     * Every slot for a given day with a status — for the booking grid.
     * status: 'free' | 'taken' (already booked) | 'past'. Empty collection = day off/closed.
     *
     * @return Collection<int, array{time: Carbon, end: Carbon, available: bool, status: string}>
     */
    public function daySlots(User $dentist, int $durationMinutes, Carbon $date, ?int $ignoreAppointmentId = null): Collection
    {
        $slots = collect();

        $hours = $this->hoursFor($dentist, $date);
        if (! $hours) {
            return $slots;
        }
        [$open, $close] = $hours;

        $step = (int) config('clinic.slot_minutes');
        $existing = $this->existingAppointments($dentist, $date, $ignoreAppointmentId);

        for ($cursor = $open->copy(); $cursor->copy()->addMinutes($durationMinutes)->lte($close); $cursor->addMinutes($step)) {
            $start = $cursor->copy();
            $status = ! $start->isFuture() ? 'past'
                : ($this->overlaps($start, $durationMinutes, $existing) ? 'taken' : 'free');

            $slots->push([
                'time' => $start,
                'end' => $start->copy()->addMinutes($durationMinutes),
                'available' => $status === 'free',
                'status' => $status,
            ]);
        }

        return $slots;
    }

    /**
     * Day-by-day availability for a calendar month: 'past' | 'closed' | 'full' | 'open'
     * (+ free-slot count). Appointments for the month are loaded in ONE query.
     *
     * @return array<string, array{status: string, free: int}> keyed by Y-m-d
     */
    public function monthOverview(User $dentist, Carbon $monthStart, int $durationMinutes, ?int $ignoreAppointmentId = null): array
    {
        $monthStart = $monthStart->copy()->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $step = (int) config('clinic.slot_minutes');

        $byDate = Appointment::where('dentist_id', $dentist->id)
            ->whereBetween('scheduled_at', [$monthStart, $monthEnd->copy()->endOfDay()])
            ->where('status', '!=', AppointmentStatus::Cancelled->value)
            ->when($ignoreAppointmentId, fn ($q) => $q->where('id', '!=', $ignoreAppointmentId))
            ->get(['id', 'scheduled_at', 'duration_minutes'])
            ->groupBy(fn ($a) => $a->scheduled_at->toDateString());

        $out = [];
        for ($day = $monthStart->copy(); $day->lte($monthEnd); $day->addDay()) {
            $key = $day->toDateString();

            if ($day->isBefore(today())) {
                $out[$key] = ['status' => 'past', 'free' => 0];

                continue;
            }

            $hours = $this->hoursFor($dentist, $day);
            if (! $hours) {
                $out[$key] = ['status' => 'closed', 'free' => 0];

                continue;
            }
            [$open, $close] = $hours;

            $existing = $byDate->get($key, collect());
            $free = 0;
            for ($cursor = $open->copy(); $cursor->copy()->addMinutes($durationMinutes)->lte($close); $cursor->addMinutes($step)) {
                if ($cursor->isFuture() && ! $this->overlaps($cursor, $durationMinutes, $existing)) {
                    $free++;
                }
            }

            $out[$key] = ['status' => $free > 0 ? 'open' : 'full', 'free' => $free];
        }

        return $out;
    }

    /**
     * Is a specific start time free for this dentist (within their working hours)?
     */
    public function isSlotAvailable(User $dentist, Carbon $start, int $durationMinutes, ?int $ignoreAppointmentId = null): bool
    {
        $hours = $this->hoursFor($dentist, $start);
        if (! $hours) {
            return false;
        }
        [$open, $close] = $hours;
        if ($start->lt($open) || $start->copy()->addMinutes($durationMinutes)->gt($close)) {
            return false;
        }

        $existing = $this->existingAppointments($dentist, $start)
            ->when($ignoreAppointmentId, fn ($c) => $c->where('id', '!=', $ignoreAppointmentId));

        return ! $this->overlaps($start, $durationMinutes, $existing);
    }

    private function existingAppointments(User $dentist, Carbon $day, ?int $ignoreAppointmentId = null): Collection
    {
        return Appointment::where('dentist_id', $dentist->id)
            ->whereDate('scheduled_at', $day->toDateString())
            ->where('status', '!=', AppointmentStatus::Cancelled->value)
            ->when($ignoreAppointmentId, fn ($q) => $q->where('id', '!=', $ignoreAppointmentId))
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

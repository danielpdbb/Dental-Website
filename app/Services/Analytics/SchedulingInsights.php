<?php

namespace App\Services\Analytics;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\AppointmentProcedure;
use App\Models\User;
use Carbon\Carbon;

/**
 * Decision-Tree / scheduling KPIs for the management dashboard: busy days, peak
 * hours, no-show risk, dentist workload, underused slots, procedure demand and
 * schedule efficiency — computed from appointment history over a date window.
 */
class SchedulingInsights
{
    public function __construct(private Carbon $from, private Carbon $to) {}

    public function kpis(): array
    {
        $appts = Appointment::whereBetween('scheduled_at', [$this->from->copy()->startOfDay(), $this->to->copy()->endOfDay()])
            ->get(['id', 'scheduled_at', 'status', 'dentist_id', 'duration_minutes', 'total_amount']);

        $total = $appts->count();
        $dayNames = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];

        // Demand by weekday (only the clinic's open days).
        $openDays = (array) config('clinic.open_days');
        $byDayCounts = [];
        foreach ($openDays as $d) {
            $byDayCounts[$d] = 0;
        }
        foreach ($appts as $a) {
            $d = $a->scheduled_at->dayOfWeekIso;
            if (array_key_exists($d, $byDayCounts)) {
                $byDayCounts[$d]++;
            }
        }
        $busiestDayKey = $total ? array_keys($byDayCounts, max($byDayCounts))[0] ?? null : null;

        // Demand by open hour.
        $open = (int) Carbon::parse(config('clinic.open_time'))->format('H');
        $close = (int) Carbon::parse(config('clinic.close_time'))->format('H');
        $byHourCounts = [];
        for ($h = $open; $h < $close; $h++) {
            $byHourCounts[$h] = 0;
        }
        foreach ($appts as $a) {
            $h = (int) $a->scheduled_at->format('H');
            if (array_key_exists($h, $byHourCounts)) {
                $byHourCounts[$h]++;
            }
        }
        $peakHour = $total ? array_keys($byHourCounts, max($byHourCounts))[0] ?? null : null;
        $underusedHour = $total ? array_keys($byHourCounts, min($byHourCounts))[0] ?? null : null;

        // No-show / cancellation rate + the revenue those missed visits would have made.
        $missedAppts = $appts->whereIn('status', [AppointmentStatus::NoShow, AppointmentStatus::Cancelled]);
        $missed = $missedAppts->count();
        $noShowPct = $total ? (int) round($missed / $total * 100) : 0;
        $lostRevenue = (float) $missedAppts->sum('total_amount');

        // Dentist workload (active appointments + hours).
        $active = $appts->whereNotIn('status', [AppointmentStatus::Cancelled, AppointmentStatus::NoShow]);
        $workload = $active->groupBy('dentist_id')->map(fn ($g) => [
            'count' => $g->count(),
            'minutes' => (int) $g->sum('duration_minutes'),
        ]);
        $topDentistId = $workload->isNotEmpty() ? $workload->sortByDesc('minutes')->keys()->first() : null;
        $topDentist = $topDentistId ? [
            'name' => User::find($topDentistId)?->name ?? '—',
            'count' => $workload[$topDentistId]['count'],
            'hours' => round($workload[$topDentistId]['minutes'] / 60, 1),
        ] : null;

        // Most-booked procedure.
        $topProc = AppointmentProcedure::whereHas('appointment', fn ($q) => $q->whereBetween('scheduled_at', [$this->from->copy()->startOfDay(), $this->to->copy()->endOfDay()]))
            ->selectRaw('procedure_name, COUNT(*) as c')
            ->groupBy('procedure_name')->orderByDesc('c')->first();

        // Schedule efficiency = booked active minutes ÷ available minutes.
        $bookedMinutes = (int) $active->sum('duration_minutes');
        $dentistCount = max(1, User::where('role', \App\Enums\UserRole::Dentist)->count());
        $openDayCount = $this->countOpenDays();
        $dailyMinutes = max(1, ($close - $open) * 60);
        $availableMinutes = max(1, $openDayCount * $dentistCount * $dailyMinutes);
        $efficiency = (int) round(min(100, $bookedMinutes / $availableMinutes * 100));

        return [
            'total' => $total,
            'busiestDay' => $busiestDayKey ? ['label' => $dayNames[$busiestDayKey], 'count' => $byDayCounts[$busiestDayKey]] : null,
            'peakHour' => $peakHour !== null ? ['label' => $this->hourLabel($peakHour), 'count' => $byHourCounts[$peakHour]] : null,
            'underused' => $underusedHour !== null ? ['label' => $this->hourLabel($underusedHour), 'count' => $byHourCounts[$underusedHour]] : null,
            'noShowPct' => $noShowPct,
            'lostRevenue' => $lostRevenue,
            'missed' => $missed,
            'topDentist' => $topDentist,
            'topProcedure' => $topProc ? ['name' => $topProc->procedure_name, 'count' => (int) $topProc->c] : null,
            'efficiency' => $efficiency,
            'byDay' => [
                'labels' => array_map(fn ($d) => substr($dayNames[$d], 0, 3), array_keys($byDayCounts)),
                'counts' => array_values($byDayCounts),
            ],
            'byHour' => [
                'labels' => array_map(fn ($h) => $this->hourLabel($h), array_keys($byHourCounts)),
                'counts' => array_values($byHourCounts),
            ],
        ];
    }

    private function hourLabel(int $h): string
    {
        return Carbon::createFromTime($h)->format('g A');
    }

    private function countOpenDays(): int
    {
        $openDays = (array) config('clinic.open_days');
        $count = 0;
        $cursor = $this->from->copy()->startOfDay();
        $end = $this->to->copy()->endOfDay();
        while ($cursor->lte($end)) {
            if (in_array($cursor->dayOfWeekIso, $openDays, true)) {
                $count++;
            }
            $cursor->addDay();
        }

        return max(1, $count);
    }
}

<?php

namespace App\Http\Controllers\Clinic;

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DentistScheduleController extends Controller
{
    /** Dentist calendar. Dentists see their own; management/reception may pick one. */
    public function index(Request $request): View
    {
        $user = $request->user();
        $isDentist = $user->role === UserRole::Dentist;

        $dentists = $isDentist
            ? collect()
            : User::where('role', UserRole::Dentist)->orderBy('name')->get();

        $dentist = $isDentist
            ? $user
            : ($request->filled('dentist_id')
                ? User::where('role', UserRole::Dentist)->find($request->integer('dentist_id'))
                : $dentists->first());

        $date = $request->filled('date') ? Carbon::parse($request->date('date')) : Carbon::today();
        $viewMode = in_array($request->string('view')->toString(), ['month', 'day'], true)
            ? $request->string('view')->toString()
            : 'month';

        $rangeStart = $viewMode === 'month' ? $date->copy()->startOfMonth()->startOfWeek() : $date->copy()->startOfDay();
        $rangeEnd = $viewMode === 'month' ? $date->copy()->endOfMonth()->endOfWeek() : $date->copy()->endOfDay();

        $appointments = $dentist
            ? Appointment::where('dentist_id', $dentist->id)
                ->whereBetween('scheduled_at', [$rangeStart, $rangeEnd])
                ->where('status', '!=', AppointmentStatus::Cancelled->value)
                ->with(['patient', 'service', 'procedures', 'intake', 'recommendations', 'parent'])
                ->orderBy('scheduled_at')
                ->get()
            : collect();

        // Calendar-grid bounds: half-hour rows spanning the earliest/latest activity
        // of the day (falls back to the clinic's default hours when nothing's booked).
        $dayOpen = Carbon::parse(config('clinic.open_time'));
        $dayClose = Carbon::parse(config('clinic.close_time'));
        $gridStartMin = min($dayOpen->hour * 60 + $dayOpen->minute, $appointments->min(fn ($a) => $a->scheduled_at->hour * 60 + $a->scheduled_at->minute) ?? PHP_INT_MAX);
        $gridEndMin = max($dayClose->hour * 60 + $dayClose->minute, $appointments->max(fn ($a) => $a->scheduled_at->hour * 60 + $a->scheduled_at->minute + $a->duration_minutes) ?? 0);
        $gridStartMin = (int) (floor($gridStartMin / 30) * 30);
        $gridEndMin = (int) (ceil($gridEndMin / 30) * 30);
        $rows = max(1, (int) (($gridEndMin - $gridStartMin) / 30));

        $tiles = $appointments->map(function (Appointment $a) use ($gridStartMin) {
            $startMin = $a->scheduled_at->hour * 60 + $a->scheduled_at->minute;
            $hasNext = $a->recommendations->firstWhere('source', \App\Enums\RecommendationSource::Stage2Next);

            return [
                'id' => $a->id,
                'row' => (int) (($startMin - $gridStartMin) / 30) + 1,
                'span' => max(1, (int) ceil($a->duration_minutes / 30)),
                'time' => $a->scheduled_at->format('g:i A'),
                'dateKey' => $a->scheduled_at->toDateString(),
                'patient' => $a->patient?->fullName() ?? '—',
                'procedures' => $a->proceduresLabel(),
                'duration' => $a->duration_minutes,
                'status' => $a->status->label(),
                'statusClasses' => $a->status->badgeClasses(),
                'isWalkIn' => $a->is_walk_in,
                'isFollowUp' => (bool) $a->parent_appointment_id,
                'parentDate' => $a->parent?->scheduled_at?->format('M j, Y'),
                'hasAssessment' => (bool) $a->intake,
                'hasNextVisit' => $hasNext ? $hasNext->status->label() : null,
                'treatmentUrl' => route('clinic.appointments.treatment', $a),
                'patientUrl' => $a->patient ? route('clinic.patients.show', $a->patient) : null,
            ];
        })->values();

        $monthDays = collect();
        if ($viewMode === 'month') {
            for ($day = $rangeStart->copy(); $day->lte($rangeEnd); $day->addDay()) {
                $monthDays->push([
                    'date' => $day->copy(),
                    'inMonth' => $day->month === $date->month,
                    'tiles' => $tiles->where('dateKey', $day->toDateString())->values(),
                ]);
            }
        }

        return view('clinic.schedule.mine', [
            'isDentist' => $isDentist,
            'dentist' => $dentist,
            'dentists' => $dentists,
            'date' => $date,
            'viewMode' => $viewMode,
            'monthDays' => $monthDays,
            'appointments' => $appointments,
            'tiles' => $tiles,
            'gridStartMin' => $gridStartMin,
            'rows' => $rows,
        ]);
    }
}

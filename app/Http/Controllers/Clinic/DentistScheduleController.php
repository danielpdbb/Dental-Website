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
    /**
     * A dentist's day at a glance. Dentists see their own; management/reception
     * can pick which dentist to view.
     */
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

        $appointments = $dentist
            ? Appointment::where('dentist_id', $dentist->id)
                ->whereDate('scheduled_at', $date->toDateString())
                ->where('status', '!=', AppointmentStatus::Cancelled->value)
                ->with(['patient', 'service', 'procedures', 'intake', 'recommendations'])
                ->orderBy('scheduled_at')
                ->get()
            : collect();

        return view('clinic.schedule.mine', [
            'isDentist' => $isDentist,
            'dentist' => $dentist,
            'dentists' => $dentists,
            'date' => $date,
            'appointments' => $appointments,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Clinic;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\User;
use App\Services\PredictiveScheduler;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SchedulingController extends Controller
{
    /**
     * Predictive scheduling helper — suggest the next free slots for a dentist.
     */
    public function index(Request $request, PredictiveScheduler $scheduler): View
    {
        abort_unless(in_array($request->user()->role, [UserRole::Receptionist, UserRole::Management], true), 403);

        $suggestions = collect();
        $dentist = $request->filled('dentist_id') ? User::find($request->integer('dentist_id')) : null;
        $service = $request->filled('service_id') ? Service::find($request->integer('service_id')) : null;
        $fromDate = $request->filled('date') ? Carbon::parse($request->date('date')) : now();

        if ($dentist && $service) {
            $suggestions = $scheduler->suggestSlots($dentist, $service->duration_minutes, $fromDate, 9);
        }

        return view('clinic.scheduling.index', [
            'dentists' => User::where('role', UserRole::Dentist)->orderBy('name')->get(),
            'services' => Service::active()->orderBy('name')->get(),
            'suggestions' => $suggestions,
            'selected' => [
                'dentist_id' => $dentist?->id,
                'service_id' => $service?->id,
                'date' => $fromDate->toDateString(),
            ],
        ]);
    }
}

<?php

namespace App\Http\Controllers\Clinic;

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use App\Services\ML\AppointmentFeatureExtractor;
use App\Services\ML\SchedulingModel;
use App\Services\PredictiveScheduler;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SchedulingController extends Controller
{
    /**
     * Predictive scheduling — suggest the next free slots, ranked by the Decision
     * Tree's predicted attendance ("keep") likelihood so the best times surface.
     */
    public function index(Request $request, PredictiveScheduler $scheduler, SchedulingModel $model, AppointmentFeatureExtractor $extractor): View
    {
        abort_unless(in_array($request->user()->role, [UserRole::Receptionist, UserRole::Management], true), 403);

        $suggestions = collect();
        $dentist = $request->filled('dentist_id') ? User::find($request->integer('dentist_id')) : null;
        $service = $request->filled('service_id') ? Service::find($request->integer('service_id')) : null;
        // Optional: score the slots for a SPECIFIC patient so the no-show risk reflects
        // their own history (walk-in / no selection = generic baseline).
        $patient = $request->filled('patient_id') ? Patient::find($request->integer('patient_id')) : null;
        $fromDate = $request->filled('date') ? Carbon::parse($request->date('date')) : now();

        $suggestedAction = null;

        // Patient context (prior visits / no-shows) shown to the receptionist.
        $patientContext = null;
        if ($patient) {
            $patientContext = [
                'patient' => $patient,
                'visits' => $patient->appointments()->count(),
                'noShows' => $patient->appointments()->where('status', AppointmentStatus::NoShow->value)->count(),
            ];
        }

        if ($dentist && $service) {
            $slots = $scheduler->suggestSlots($dentist, $service->duration_minutes, $fromDate, 9);

            $suggestions = $slots->map(function (Carbon $slot) use ($model, $extractor, $service, $patient) {
                $keep = $model->keepProbability(
                    $extractor->slotVector($patient, $slot, $service->duration_minutes, (float) $service->price)
                );

                return [
                    'time' => $slot,
                    'keep' => $keep,
                    'noShow' => $keep !== null ? (int) round((1 - $keep) * 100) : null,
                    'risk' => $keep !== null ? $model->riskBadge($keep) : null,
                ];
            });

            // Flag the slot(s) with the highest predicted attendance as "recommended".
            $best = $suggestions->whereNotNull('keep')->max('keep');
            $suggestions = $suggestions->map(fn ($s) => $s + [
                'recommended' => $s['keep'] !== null && $best !== null && $best > 0 && $s['keep'] >= $best - 0.0001,
            ]);

            // Suggested action — patient history takes precedence over the slot baseline.
            if ($patientContext && $patientContext['noShows'] > 0) {
                $suggestedAction = "This patient has missed {$patientContext['noShows']} appointment(s) before — send an SMS/email reminder and confirm by phone the day before.";
            } elseif ($best !== null) {
                $bestNoShow = (int) round((1 - $best) * 100);
                $suggestedAction = $bestNoShow >= 40
                    ? 'Higher no-show risk — send an SMS/email reminder and confirm by phone a day before.'
                    : ($bestNoShow >= 20
                        ? 'Send a standard appointment reminder the day before.'
                        : 'Low risk — a routine reminder is enough.');
            }
        }

        return view('clinic.scheduling.index', [
            'dentists' => User::where('role', UserRole::Dentist)->orderBy('name')->get(),
            'services' => Service::active()->orderBy('name')->get(),
            'patients' => Patient::orderBy('last_name')->get(['id', 'first_name', 'last_name']),
            'suggestions' => $suggestions,
            'modelTrained' => $model->isTrained(),
            'suggestedAction' => $suggestedAction,
            'patientContext' => $patientContext,
            'selected' => [
                'dentist_id' => $dentist?->id,
                'service_id' => $service?->id,
                'patient_id' => $patient?->id,
                'date' => $fromDate->toDateString(),
            ],
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Enums\ProcedureStatus;
use App\Models\Appointment;
use App\Models\AppointmentRecommendation;
use App\Models\Service;
use App\Services\ML\AppointmentRecommender;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Stage-1 pre-appointment assessment. Fillable by the patient (own booking) or any
 * staff member (walk-in / phone). On save it runs the regression to produce a
 * "possible current treatment" suggestion attached to this appointment.
 */
class PreVisitAssessmentController extends Controller
{
    public function save(Request $request, Appointment $appointment, AppointmentRecommender $recommender): RedirectResponse
    {
        $this->authorize('submitIntake', $appointment);

        $data = $request->validate([
            'main_concern' => ['nullable', 'string', 'max:255'],
            'pain_level' => ['required', 'integer', 'min:0', 'max:10'],
            'brushing_per_day' => ['required', 'integer', 'min:0', 'max:10'],
            'sugar_level' => ['required', Rule::in(['low', 'medium', 'high'])],
            'months_since_cleaning' => ['required', 'integer', 'min:0', 'max:240'],
            'last_visit_bucket' => ['nullable', Rule::in(['under_6m', '6_12m', 'more_than_1y', 'never'])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        foreach (['toothache', 'sensitivity', 'bleeding_gums', 'bad_breath', 'swelling', 'flosses', 'smoker'] as $flag) {
            $data[$flag] = $request->boolean($flag);
        }
        $data['submitted_by'] = $request->user()->id;

        $appointment->intake()->updateOrCreate(['appointment_id' => $appointment->id], $data);
        $recommender->generateStage1($appointment->fresh('intake'));

        // Let the dentist know the patient's pre-visit assessment is in (unless they filled it themselves).
        if ($appointment->dentist_id && $appointment->dentist_id !== $request->user()->id) {
            \App\Support\Notifier::user(
                $appointment->dentist,
                'Pre-visit assessment ready',
                ($appointment->patient?->fullName() ?? 'A patient').' completed their pre-visit assessment for the '.$appointment->scheduled_at->format('M j · g:i A').' visit.',
                route('clinic.appointments.treatment', $appointment),
            );
        }

        return back()->with('status', 'Pre-visit assessment saved. A suggested treatment has been generated.');
    }

    /**
     * One-click: add the Stage-1 suggested treatment as a planned procedure on the
     * still-open appointment.
     */
    public function addSuggested(Request $request, Appointment $appointment, AppointmentRecommendation $recommendation): RedirectResponse
    {
        $this->authorize('submitIntake', $appointment);
        abort_unless($recommendation->appointment_id === $appointment->id, 404);

        abort_if(! in_array($appointment->status, [AppointmentStatus::Booked, AppointmentStatus::InTreatment], true),
            403, 'This appointment is no longer open for changes.');

        $service = $recommendation->linked_service_id
            ? Service::find($recommendation->linked_service_id)
            : null;

        if (! $service) {
            return back()->with('error', 'No linked service to add — please add the procedure manually.');
        }

        // Avoid duplicating a procedure that is already on the appointment.
        $already = $appointment->procedures()->where('service_id', $service->id)->exists();
        if ($already) {
            return back()->with('status', 'That treatment is already on this appointment.');
        }

        $appointment->procedures()->create([
            'service_id' => $service->id,
            'procedure_name' => $service->name,
            'price' => $service->price,
            'duration_minutes' => $service->duration_minutes,
            'status' => ProcedureStatus::Planned,
            'notes' => 'Added from pre-visit suggestion.',
        ]);

        $appointment->recomputeTotals();

        return back()->with('status', $service->name.' added to this appointment.');
    }
}

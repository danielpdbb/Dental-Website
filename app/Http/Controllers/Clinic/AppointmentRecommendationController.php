<?php

namespace App\Http\Controllers\Clinic;

use App\Enums\AdviceStatus;
use App\Enums\Priority;
use App\Http\Controllers\Controller;
use App\Mail\RecommendationReadyMail;
use App\Models\Appointment;
use App\Models\AppointmentRecommendation;
use App\Services\ML\AppointmentRecommender;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

/**
 * The dentist's review of a regression recommendation: verify/edit, accept (which
 * proposes a Decision-Tree follow-up date), reject, print, and send to the patient.
 */
class AppointmentRecommendationController extends Controller
{
    public function update(Request $request, Appointment $appointment, AppointmentRecommendation $recommendation): RedirectResponse
    {
        $this->authorize('recordTreatment', $appointment);
        abort_unless($recommendation->appointment_id === $appointment->id, 404);

        $data = $request->validate([
            'recommendation' => ['required', 'string', 'max:1000'],
            'linked_service_id' => ['nullable', 'exists:services,id'],
            'priority' => ['required', Rule::enum(Priority::class)],
            'follow_up_weeks' => ['nullable', 'integer', 'min:0', 'max:52'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $recommendation->update($data);

        return back()->with('status', 'Recommendation updated.');
    }

    public function accept(Request $request, Appointment $appointment, AppointmentRecommendation $recommendation, AppointmentRecommender $recommender): RedirectResponse
    {
        $this->authorize('recordTreatment', $appointment);
        abort_unless($recommendation->appointment_id === $appointment->id, 404);

        $recommendation->loadMissing(['appointment.dentist', 'service']);

        // Decision-Tree proposes a follow-up date/time for a next-visit recommendation.
        $followUp = $recommendation->source === \App\Enums\RecommendationSource::Stage2Next
            ? $recommender->suggestFollowUp($recommendation)
            : null;

        $recommendation->update([
            'status' => AdviceStatus::Accepted,
            'accepted_by' => $request->user()->id,
            'accepted_at' => now(),
            'suggested_at' => $followUp,
        ]);

        $msg = 'Recommendation accepted.';
        if ($followUp) {
            $msg .= ' Suggested follow-up: '.$followUp->format('M j, Y g:i A').'.';
        }

        return back()->with('status', $msg);
    }

    public function reject(Request $request, Appointment $appointment, AppointmentRecommendation $recommendation): RedirectResponse
    {
        $this->authorize('recordTreatment', $appointment);
        abort_unless($recommendation->appointment_id === $appointment->id, 404);

        $recommendation->update(['status' => AdviceStatus::Rejected]);

        return back()->with('status', 'Recommendation rejected.');
    }

    /**
     * Send an accepted recommendation to the patient — in-app dashboard card + email.
     */
    public function send(Request $request, Appointment $appointment, AppointmentRecommendation $recommendation): RedirectResponse
    {
        $this->authorize('recordTreatment', $appointment);
        abort_unless($recommendation->appointment_id === $appointment->id, 404);

        if (! $recommendation->isAccepted()) {
            return back()->with('error', 'Accept the recommendation before sending it to the patient.');
        }

        $recommendation->update(['sent_to_patient_at' => now()]);

        $patientUser = $appointment->loadMissing('patient.user')->patient?->user;
        if ($patientUser) {
            // In-app bell notification.
            $patientUser->notify(new \App\Notifications\RecommendationReadyNotification($recommendation));
            // Email (best-effort — won't block the action if the mailer fails).
            if ($patientUser->email) {
                try {
                    Mail::to($patientUser->email)->send(new RecommendationReadyMail($recommendation));
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }

        return back()->with('status', 'Recommendation sent to the patient’s dashboard, notifications'.($patientUser?->email ? ' and email.' : '.'));
    }

    /**
     * Printable PDF of the recommendation (staff side).
     */
    public function print(Appointment $appointment, AppointmentRecommendation $recommendation)
    {
        $this->authorize('recordTreatment', $appointment);
        abort_unless($recommendation->appointment_id === $appointment->id, 404);

        $recommendation->loadMissing(['appointment.patient', 'appointment.dentist', 'service']);

        return Pdf::loadView('clinic.recommendations.document', [
            'rec' => $recommendation,
            'appointment' => $appointment,
            'issuedAt' => now(),
        ])->stream('recommendation-'.$recommendation->id.'.pdf');
    }
}

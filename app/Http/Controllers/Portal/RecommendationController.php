<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\AppointmentRecommendation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

/**
 * Patient-facing access to recommendations their dentist has accepted and sent.
 */
class RecommendationController extends Controller
{
    public function print(Request $request, AppointmentRecommendation $recommendation)
    {
        $recommendation->loadMissing(['appointment.patient.user', 'appointment.dentist', 'service']);

        // Only the owning patient, and only once it has been sent to them.
        abort_unless($recommendation->appointment->patient?->user_id === $request->user()->id, 403);
        abort_if($recommendation->sent_to_patient_at === null, 404);

        return Pdf::loadView('clinic.recommendations.document', [
            'rec' => $recommendation,
            'appointment' => $recommendation->appointment,
            'issuedAt' => $recommendation->accepted_at ?? now(),
        ])->stream('my-recommendation-'.$recommendation->id.'.pdf');
    }
}

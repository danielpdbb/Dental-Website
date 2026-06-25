<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Services\ML\AppointmentRecommender;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Stage-2 dentist clinical findings → next-visit recommendation (regression).
 */
class ClinicalFindingController extends Controller
{
    public function save(Request $request, Appointment $appointment, AppointmentRecommender $recommender): RedirectResponse
    {
        $this->authorize('recordTreatment', $appointment);

        $data = $request->validate([
            'gum_inflammation' => ['required', Rule::in(['none', 'mild', 'moderate', 'severe'])],
            'plaque_level' => ['required', Rule::in(['low', 'medium', 'high'])],
            'infection_signs' => ['required', Rule::in(['none', 'possible', 'present'])],
            'missing_teeth' => ['nullable', 'integer', 'min:0', 'max:32'],
            'existing_fillings' => ['nullable', 'integer', 'min:0', 'max:32'],
            'treatment_done_today' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        foreach (['cavity_found', 'tooth_mobility', 'xray_needed'] as $flag) {
            $data[$flag] = $request->boolean($flag);
        }
        $data['recorded_by'] = $request->user()->id;

        $appointment->finding()->updateOrCreate(['appointment_id' => $appointment->id], $data);
        $recommender->generateStage2($appointment->fresh(['finding', 'intake', 'patient']));

        return back()->with('status', 'Clinical findings saved. A next-visit recommendation has been generated.');
    }
}

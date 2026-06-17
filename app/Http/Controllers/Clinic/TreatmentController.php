<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Patient\StoreTreatmentRequest;
use App\Models\Patient;
use App\Models\Treatment;
use Illuminate\Http\RedirectResponse;

class TreatmentController extends Controller
{
    public function store(StoreTreatmentRequest $request, Patient $patient): RedirectResponse
    {
        $patient->treatments()->create($request->validated());

        return back()->with('status', 'Treatment recorded.');
    }

    public function destroy(Patient $patient, Treatment $treatment): RedirectResponse
    {
        $this->authorize('update', $patient);
        abort_unless($treatment->patient_id === $patient->id, 404);

        $treatment->delete();

        return back()->with('status', 'Treatment removed.');
    }
}

<?php

namespace App\Http\Controllers\Clinic;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patient\StoreTreatmentRequest;
use App\Models\Patient;
use App\Models\Service;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TreatmentController extends Controller
{
    public function store(StoreTreatmentRequest $request, Patient $patient): RedirectResponse
    {
        $patient->treatments()->create($request->validated());

        return back()->with('status', 'Treatment recorded.');
    }

    public function edit(Patient $patient, Treatment $treatment): View
    {
        $this->authorize('update', $patient);
        abort_unless($treatment->patient_id === $patient->id, 404);

        return view('clinic.patients.treatments.edit', [
            'patient' => $patient,
            'treatment' => $treatment,
            'dentists' => User::where('role', UserRole::Dentist)->orderBy('name')->get(),
            'services' => Service::active()->orderBy('name')->get(),
        ]);
    }

    public function update(StoreTreatmentRequest $request, Patient $patient, Treatment $treatment): RedirectResponse
    {
        abort_unless($treatment->patient_id === $patient->id, 404);

        $treatment->update($request->validated());

        return redirect()->route('clinic.patients.show', $patient)->with('status', 'Treatment updated.');
    }

    public function destroy(Patient $patient, Treatment $treatment): RedirectResponse
    {
        $this->authorize('update', $patient);
        abort_unless($treatment->patient_id === $patient->id, 404);

        $treatment->delete();

        return back()->with('status', 'Treatment removed.');
    }
}

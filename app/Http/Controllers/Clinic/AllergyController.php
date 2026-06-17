<?php

namespace App\Http\Controllers\Clinic;

use App\Enums\AllergySeverity;
use App\Http\Controllers\Controller;
use App\Models\Allergy;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AllergyController extends Controller
{
    public function store(Request $request, Patient $patient): RedirectResponse
    {
        $this->authorize('update', $patient);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'severity' => ['required', Rule::enum(AllergySeverity::class)],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $patient->allergies()->create($data);

        return back()->with('status', 'Allergy added.');
    }

    public function destroy(Patient $patient, Allergy $allergy): RedirectResponse
    {
        $this->authorize('update', $patient);
        abort_unless($allergy->patient_id === $patient->id, 404);

        $allergy->delete();

        return back()->with('status', 'Allergy removed.');
    }
}

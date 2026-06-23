<?php

namespace App\Http\Controllers\Clinic;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClinicalIntakeController extends Controller
{
    /**
     * Create/update a patient's clinical intake (symptoms, behaviors, indicators)
     * that feeds the procedure-recommendation model.
     */
    public function save(Request $request, Patient $patient): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, [UserRole::Dentist, UserRole::Receptionist, UserRole::Management], true), 403);

        $data = $request->validate([
            'brushing_per_day' => ['required', 'integer', 'min:0', 'max:10'],
            'sugar_level' => ['required', Rule::in(['low', 'medium', 'high'])],
            'months_since_cleaning' => ['required', 'integer', 'min:0', 'max:240'],
            'gum_condition' => ['required', Rule::in(['healthy', 'gingivitis', 'periodontitis'])],
            'missing_teeth' => ['required', 'integer', 'min:0', 'max:32'],
            'existing_fillings' => ['required', 'integer', 'min:0', 'max:32'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        foreach (['toothache', 'sensitivity', 'bleeding_gums', 'bad_breath', 'swelling', 'flosses', 'smoker', 'visible_plaque', 'decay_observed'] as $flag) {
            $data[$flag] = $request->boolean($flag);
        }
        $data['updated_by'] = $request->user()->id;

        $patient->intake()->updateOrCreate(['patient_id' => $patient->id], $data);

        return back()->with('status', 'Clinical intake saved.');
    }
}

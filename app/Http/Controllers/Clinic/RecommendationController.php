<?php

namespace App\Http\Controllers\Clinic;

use App\Enums\RecommendationStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patient\StoreRecommendationRequest;
use App\Http\Requests\Patient\UpdateRecommendationRequest;
use App\Models\Patient;
use App\Models\ProcedureRecommendation;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RecommendationController extends Controller
{
    public function store(StoreRecommendationRequest $request, Patient $patient): RedirectResponse
    {
        $patient->recommendations()->create([
            ...$request->validated(),
            // Attribute the recommendation to the dentist who made it.
            'dentist_id' => $request->user()->role === UserRole::Dentist ? $request->user()->id : null,
            'status' => RecommendationStatus::Pending,
        ]);

        return back()->with('status', 'Recommendation added.');
    }

    public function edit(Patient $patient, ProcedureRecommendation $recommendation): View
    {
        abort_unless(in_array(request()->user()->role, [UserRole::Dentist, UserRole::Management], true), 403);
        abort_unless($recommendation->patient_id === $patient->id, 404);

        return view('clinic.patients.recommendations.edit', [
            'patient' => $patient,
            'recommendation' => $recommendation,
            'services' => Service::active()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateRecommendationRequest $request, Patient $patient, ProcedureRecommendation $recommendation): RedirectResponse
    {
        abort_unless($recommendation->patient_id === $patient->id, 404);

        $recommendation->update($request->validated());

        return redirect()->route('clinic.patients.show', $patient)->with('status', 'Recommendation updated.');
    }

    public function updateStatus(Request $request, Patient $patient, ProcedureRecommendation $recommendation): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, [UserRole::Dentist, UserRole::Management], true), 403);
        abort_unless($recommendation->patient_id === $patient->id, 404);

        $data = $request->validate([
            'status' => ['required', Rule::enum(RecommendationStatus::class)],
        ]);

        $recommendation->update($data);

        return back()->with('status', 'Recommendation updated.');
    }
}

<?php

namespace App\Http\Controllers\Clinic;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patient\StorePatientRequest;
use App\Http\Requests\Patient\UpdatePatientRequest;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PatientController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Patient::class);

        $patients = Patient::query()
            ->with(['user', 'appointments.payments'])
            ->when($request->string('search')->trim()->value(), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($request->string('account')->value(), function ($query, $account) {
                $account === 'walkin'
                    ? $query->whereNull('user_id')
                    : $query->whereNotNull('user_id');
            })
            ->orderBy('last_name')
            ->paginate(15)
            ->withQueryString();

        return view('clinic.patients.index', [
            'patients' => $patients,
            'filters' => $request->only('search', 'account'),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Patient::class);

        return view('clinic.patients.create');
    }

    public function store(StorePatientRequest $request): RedirectResponse
    {
        $patient = Patient::create($request->validated());

        return redirect()
            ->route('clinic.patients.show', $patient)
            ->with('status', 'Patient record created.');
    }

    public function show(Patient $patient): View
    {
        $this->authorize('view', $patient);

        $patient->load(['user', 'allergies', 'treatments.dentist', 'treatments.service',
            'recommendations.dentist', 'recommendations.service', 'appointments.service',
            'appointments.dentist', 'appointments.payments']);

        return view('clinic.patients.show', [
            'patient' => $patient,
            'dentists' => User::where('role', UserRole::Dentist)->orderBy('name')->get(),
            'services' => Service::active()->orderBy('name')->get(),
        ]);
    }

    public function edit(Patient $patient): View
    {
        $this->authorize('update', $patient);

        return view('clinic.patients.edit', ['patient' => $patient]);
    }

    public function update(UpdatePatientRequest $request, Patient $patient): RedirectResponse
    {
        $patient->update($request->validated());

        return redirect()
            ->route('clinic.patients.show', $patient)
            ->with('status', 'Patient record updated.');
    }

    public function destroy(Patient $patient): RedirectResponse
    {
        $this->authorize('delete', $patient);

        $patient->delete();

        return redirect()
            ->route('clinic.patients.index')
            ->with('status', 'Patient record removed.');
    }
}

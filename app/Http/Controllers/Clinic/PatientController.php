<?php

namespace App\Http\Controllers\Clinic;

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patient\StorePatientRequest;
use App\Http\Requests\Patient\UpdatePatientRequest;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use App\Services\ML\IntakeFeatureExtractor;
use App\Services\ML\ProcedureRecommendationModel;
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

    public function show(Request $request, Patient $patient): View
    {
        $this->authorize('view', $patient);

        $patient->load(['user', 'allergies', 'appointments.payments']);

        // Paginated, filterable appointment list (the full set can get long).
        $apptStatus = $request->string('appt_status')->toString() ?: null;
        $appointments = $patient->appointments()
            ->with(['service', 'dentist'])
            ->when($apptStatus, fn ($q) => $q->where('status', $apptStatus))
            ->latest('scheduled_at')
            ->paginate(8, ['*'], 'appts')
            ->withQueryString();

        // Clinical summary — one card per past visit that has findings or an accepted
        // follow-up. Paginated (3/page) + filterable by doctor / procedure.
        $acceptedRec = fn ($r) => $r
            ->where('status', \App\Enums\AdviceStatus::Accepted->value)
            ->where('source', \App\Enums\RecommendationSource::Stage2Next->value);

        $csDentist = $request->integer('cs_dentist') ?: null;
        $csService = $request->integer('cs_service') ?: null;

        $clinicalVisits = $patient->appointments()
            ->where(fn ($q) => $q->whereHas('finding')->orWhereHas('recommendations', $acceptedRec))
            ->when($csDentist, fn ($q) => $q->where('dentist_id', $csDentist))
            ->when($csService, fn ($q) => $q->whereHas('procedures', fn ($p) => $p->where('service_id', $csService)))
            ->with([
                'dentist', 'finding',
                'procedures' => fn ($p) => $p->where('status', \App\Enums\ProcedureStatus::Performed->value),
                'recommendations' => fn ($r) => $acceptedRec($r)->latest('accepted_at'),
            ])
            ->latest('scheduled_at')
            ->paginate(3, ['*'], 'summary')
            ->withQueryString();

        // Filter options scoped to this patient's own history.
        $csDentists = User::whereIn('id', $patient->appointments()->whereNotNull('dentist_id')->distinct()->pluck('dentist_id'))
            ->orderBy('name')->get(['id', 'name']);
        $csServices = Service::whereIn('id', $patient->appointments()->whereNotNull('service_id')->distinct()->pluck('service_id'))
            ->orderBy('name')->get(['id', 'name']);

        // Odontogram: latest state per tooth + per-tooth timeline across all visits.
        $patientTeeth = \App\Models\ToothRecord::whereHas('appointment', fn ($q) => $q->where('patient_id', $patient->id))
            ->with('recorder')->get();

        return view('clinic.patients.show', [
            'patient' => $patient,
            'appointments' => $appointments,
            'apptStatus' => $apptStatus,
            'apptStatuses' => AppointmentStatus::options(),
            'clinicalVisits' => $clinicalVisits,
            'csDentist' => $csDentist,
            'csService' => $csService,
            'csDentists' => $csDentists,
            'csServices' => $csServices,
            'teeth' => \App\Models\ToothRecord::chartArray($patientTeeth),
            'teethHistory' => \App\Models\ToothRecord::historyArray($patientTeeth),
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

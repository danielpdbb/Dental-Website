<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RecordController extends Controller
{
    /**
     * The logged-in patient's own record — read only.
     */
    public function show(Request $request): View
    {
        $patient = $this->resolvePatient($request->user());

        $patient->load(['allergies', 'treatments.dentist', 'treatments.service',
            'recommendations.dentist', 'recommendations.service']);

        // Treatment history (performed procedures on paid visits) — filterable + paginated.
        $serviceFilter = $request->integer('service') ?: null;
        $history = $patient->treatmentHistoryQuery()
            ->when($serviceFilter, fn ($q) => $q->where('service_id', $serviceFilter))
            ->paginate(8, ['*'], 'history')
            ->withQueryString();

        // For the "view details" modal — the rest of that visit's context.
        $history->getCollection()->loadMissing(['appointment.dentist', 'appointment.finding',
            'appointment.procedures' => fn ($q) => $q->where('status', \App\Enums\ProcedureStatus::Performed->value)]);

        // Procedures the patient has actually had, for the filter dropdown.
        $historyServices = \App\Models\AppointmentProcedure::query()
            ->where('status', \App\Enums\ProcedureStatus::Performed->value)
            ->whereHas('appointment', fn ($q) => $q->where('patient_id', $patient->id)
                ->where('status', \App\Enums\AppointmentStatus::Completed->value))
            ->whereNotNull('service_id')
            ->groupBy('service_id')
            ->selectRaw('service_id, MAX(procedure_name) as procedure_name')
            ->get();

        // Patient's own odontogram — latest condition per tooth + per-tooth history.
        $patientTeeth = \App\Models\ToothRecord::whereHas('appointment', fn ($q) => $q->where('patient_id', $patient->id))
            ->with('recorder')->get();

        return view('portal.record.show', [
            'patient' => $patient,
            'history' => $history,
            'historyServices' => $historyServices,
            'serviceFilter' => $serviceFilter,
            'teeth' => \App\Models\ToothRecord::chartArray($patientTeeth),
            'teethHistory' => \App\Models\ToothRecord::historyArray($patientTeeth),
        ]);
    }

    /**
     * Find (or lazily create) the patient record for this user. New self-registered
     * patients won't have a record yet, so we seed a minimal one from their name.
     */
    public static function resolvePatient(User $user): Patient
    {
        return $user->patient()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'first_name' => Str::before($user->name, ' ') ?: $user->name,
                'last_name' => Str::contains($user->name, ' ') ? Str::after($user->name, ' ') : '',
            ],
        );
    }
}

<?php

namespace App\Http\Controllers\Clinic;

use App\Enums\AppointmentStatus;
use App\Enums\ProcedureStatus;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentProcedure;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The dentist's "current treatment" workspace for one appointment/session:
 * add the procedures to be done, mark them performed, then endorse to reception.
 */
class CurrentTreatmentController extends Controller
{
    public function edit(Appointment $appointment): View
    {
        // Any dentist / management can open a visit read-only; editing is gated below.
        $this->authorize('viewTreatment', $appointment);

        $appointment->load([
            'patient', 'procedures.service', 'procedures.performer',
            'intake', 'finding', 'recommendations.service', 'toothRecords.recorder',
        ]);

        // History of tooth records for THIS patient across all their visits (timeline).
        $patientToothRecords = \App\Models\ToothRecord::query()
            ->whereHas('appointment', fn ($q) => $q->where('patient_id', $appointment->patient_id))
            ->with('recorder')->get();

        return view('clinic.appointments.treatment', [
            'appointment' => $appointment,
            'canEdit' => request()->user()->can('recordTreatment', $appointment),
            'services' => Service::active()->orderBy('name')->get(),
            'stage1' => $appointment->recommendations->firstWhere('source', \App\Enums\RecommendationSource::Stage1Current),
            'stage2' => $appointment->recommendations->firstWhere('source', \App\Enums\RecommendationSource::Stage2Next),
            'teethRecords' => \App\Models\ToothRecord::chartArray($appointment->toothRecords),
            'teethAll' => \App\Models\ToothRecord::chartArray($patientToothRecords),
            'teethHistory' => \App\Models\ToothRecord::historyArray($patientToothRecords),
            'teethProcedures' => $appointment->procedures->map(fn ($p) => ['id' => $p->id, 'name' => $p->procedure_name])->all(),
        ]);
    }

    public function addProcedure(Request $request, Appointment $appointment): RedirectResponse
    {
        $this->authorize('recordTreatment', $appointment);
        $this->guardOpen($appointment);

        $data = $request->validate([
            'service_id' => ['required', Rule::exists('services', 'id')->where('is_active', true)],
            'tooth_fdi' => ['nullable', Rule::in(array_keys(\App\Models\ToothRecord::FDI_UNIVERSAL))],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $service = Service::findOrFail($data['service_id']);

        $appointment->procedures()->create([
            'service_id' => $service->id,
            'procedure_name' => $service->name,
            'tooth_fdi' => $data['tooth_fdi'] ?? null,
            'price' => $service->price,
            'duration_minutes' => $service->duration_minutes,
            'status' => ProcedureStatus::Planned,
            'notes' => $data['notes'] ?? null,
        ]);

        $this->markInTreatment($appointment);
        $appointment->recomputeTotals();

        return back()->with('status', 'Procedure added to the current treatment.');
    }

    public function togglePerformed(Request $request, Appointment $appointment, AppointmentProcedure $procedure): RedirectResponse
    {
        $this->authorize('recordTreatment', $appointment);
        $this->guardOpen($appointment);
        abort_unless($procedure->appointment_id === $appointment->id, 404);

        $nowPerformed = $procedure->status !== ProcedureStatus::Performed;

        $procedure->update([
            'status' => $nowPerformed ? ProcedureStatus::Performed : ProcedureStatus::Planned,
            'performed_by' => $nowPerformed ? $request->user()->id : null,
            'performed_at' => $nowPerformed ? now() : null,
        ]);

        $this->markInTreatment($appointment);

        return back()->with('status', $nowPerformed ? 'Marked as performed.' : 'Marked as planned.');
    }

    public function removeProcedure(Appointment $appointment, AppointmentProcedure $procedure): RedirectResponse
    {
        $this->authorize('recordTreatment', $appointment);
        $this->guardOpen($appointment);
        abort_unless($procedure->appointment_id === $appointment->id, 404);

        $procedure->delete();
        $appointment->recomputeTotals();

        return back()->with('status', 'Procedure removed.');
    }

    /**
     * Endorse the session to the receptionist for billing.
     */
    public function endorse(Appointment $appointment): RedirectResponse
    {
        $this->authorize('recordTreatment', $appointment);
        $this->guardOpen($appointment);

        $appointment->load('procedures');

        $performed = $appointment->procedures->where('status', ProcedureStatus::Performed);
        if ($performed->isEmpty()) {
            return back()->with('error', 'Mark at least one procedure as performed before endorsing.');
        }

        $appointment->update([
            'status' => AppointmentStatus::ForBilling,
            'endorsed_at' => now(),
            'endorsed_by' => request()->user()->id,
        ]);

        return redirect()->route('clinic.my-schedule')
            ->with('status', 'Endorsed to reception for billing.');
    }

    /**
     * Only editable before it has been endorsed/billed/completed.
     */
    private function guardOpen(Appointment $appointment): void
    {
        abort_if(in_array($appointment->status, [
            AppointmentStatus::ForBilling,
            AppointmentStatus::Billed,
            AppointmentStatus::Completed,
            AppointmentStatus::Cancelled,
            AppointmentStatus::NoShow,
        ], true), 403, 'This appointment is no longer open for treatment edits.');
    }

    private function markInTreatment(Appointment $appointment): void
    {
        if ($appointment->status === AppointmentStatus::Booked) {
            $appointment->update(['status' => AppointmentStatus::InTreatment]);
        }
    }
}

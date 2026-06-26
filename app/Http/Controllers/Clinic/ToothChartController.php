<?php

namespace App\Http\Controllers\Clinic;

use App\Enums\ToothCondition;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\ToothRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Saves dentist annotations from the interactive odontogram — one record per tooth
 * per appointment (re-clicking a tooth updates it).
 */
class ToothChartController extends Controller
{
    public function store(Request $request, Appointment $appointment): JsonResponse
    {
        $this->authorize('recordTreatment', $appointment);

        $data = $request->validate([
            'fdi_number' => ['required', 'integer', Rule::in(array_keys(ToothRecord::FDI_UNIVERSAL))],
            'condition' => ['required', Rule::enum(ToothCondition::class)],
            'treatment_done' => ['nullable', 'string', 'max:255'],
            'medicine_given' => ['nullable', 'string', 'max:255'],
            'special_procedure' => ['nullable', 'string', 'max:255'],
            'observation' => ['nullable', 'string', 'max:1000'],
            'surfaces' => ['nullable', 'array'],
            'surfaces.*' => ['in:M,O,D,B,L'],
            'appointment_procedure_id' => ['nullable', Rule::exists('appointment_procedures', 'id')->where('appointment_id', $appointment->id)],
        ]);

        $record = $appointment->toothRecords()->updateOrCreate(
            ['fdi_number' => $data['fdi_number']],
            [...$data, 'recorded_by' => $request->user()->id],
        );

        $condition = $record->condition;

        return response()->json([
            'ok' => true,
            'fdi' => $record->fdi_number,
            'color' => $condition->color(),
            'condition' => $condition->value,
            'label' => $condition->label(),
            'treatment_done' => $record->treatment_done,
            'medicine_given' => $record->medicine_given,
            'special_procedure' => $record->special_procedure,
            'observation' => $record->observation,
            'surfaces' => $record->surfaces ?? [],
            'appointment_procedure_id' => $record->appointment_procedure_id,
        ]);
    }
}

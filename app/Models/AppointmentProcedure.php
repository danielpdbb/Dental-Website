<?php

namespace App\Models;

use App\Enums\ProcedureStatus;
use App\Enums\ToothCondition;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * One procedure line item on an appointment. The collection of these on an
 * appointment is the "current treatment"; once the appointment is paid they
 * become part of the patient's treatment history.
 */
#[Fillable([
    'appointment_id', 'service_id', 'procedure_name', 'tooth_fdi', 'tooth_condition',
    'medicine_given', 'tooth_surfaces', 'price', 'duration_minutes',
    'status', 'performed_by', 'performed_at', 'notes',
])]
class AppointmentProcedure extends Model
{
    protected function casts(): array
    {
        return [
            'tooth_fdi' => 'integer',
            'tooth_surfaces' => 'array',
            'price' => 'decimal:2',
            'duration_minutes' => 'integer',
            'status' => ProcedureStatus::class,
            'performed_at' => 'datetime',
        ];
    }

    /** Short label for the linked tooth, e.g. "Tooth 16 (Univ 3)" — or null if whole-mouth. */
    public function toothLabel(): ?string
    {
        if (! $this->tooth_fdi) {
            return null;
        }

        $uni = ToothRecord::fdiToUniversal($this->tooth_fdi);

        return 'Tooth '.$this->tooth_fdi.($uni ? " (Univ {$uni})" : '');
    }

    /**
     * The tooth condition to paint on the chart: the dentist's explicit choice, or a
     * sensible default inferred from the procedure name.
     */
    public function chartCondition(): ToothCondition
    {
        if ($this->tooth_condition && ($c = ToothCondition::tryFrom($this->tooth_condition))) {
            return $c;
        }

        $name = Str::lower($this->procedure_name);

        return match (true) {
            str_contains($name, 'root canal') => ToothCondition::RootCanal,
            str_contains($name, 'crown') => ToothCondition::Crown,
            str_contains($name, 'bridge') => ToothCondition::Bridge,
            str_contains($name, 'implant') => ToothCondition::Implant,
            str_contains($name, 'extract') => ToothCondition::Extracted,
            str_contains($name, 'sealant') => ToothCondition::Sealant,
            str_contains($name, 'filling') || str_contains($name, 'restor') => ToothCondition::Filled,
            default => ToothCondition::Filled,
        };
    }

    /**
     * Mirror this performed, tooth-linked procedure onto the appointment's dental chart
     * (one record per tooth per visit). No-op when there's no tooth.
     */
    public function syncToothRecord(?User $performer = null): void
    {
        if (! $this->tooth_fdi) {
            return;
        }

        $this->appointment->toothRecords()->updateOrCreate(
            ['fdi_number' => $this->tooth_fdi],
            [
                'appointment_procedure_id' => $this->id,
                'condition' => $this->chartCondition()->value,
                'treatment_done' => $this->procedure_name,
                'medicine_given' => $this->medicine_given,
                'observation' => $this->notes,
                'surfaces' => $this->tooth_surfaces ?? [],
                'recorded_by' => $performer?->id ?? $this->performed_by ?? $this->appointment->dentist_id,
            ],
        );
    }

    /** Remove the chart record this procedure created (used when un-performing it). */
    public function removeToothRecord(): void
    {
        $this->appointment->toothRecords()
            ->where('appointment_procedure_id', $this->id)
            ->delete();
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function isPerformed(): bool
    {
        return $this->status === ProcedureStatus::Performed;
    }
}

<?php

namespace App\Models;

use App\Enums\ProcedureStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One procedure line item on an appointment. The collection of these on an
 * appointment is the "current treatment"; once the appointment is paid they
 * become part of the patient's treatment history.
 */
#[Fillable([
    'appointment_id', 'service_id', 'procedure_name', 'tooth_fdi', 'price', 'duration_minutes',
    'status', 'performed_by', 'performed_at', 'notes',
])]
class AppointmentProcedure extends Model
{
    protected function casts(): array
    {
        return [
            'tooth_fdi' => 'integer',
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

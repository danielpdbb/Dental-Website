<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stage-2 dentist clinical findings for one appointment — the basis of the
 * next-visit recommendation.
 */
#[Fillable([
    'appointment_id', 'cavity_found', 'gum_inflammation', 'plaque_level',
    'tooth_mobility', 'infection_signs', 'xray_needed', 'missing_teeth',
    'existing_fillings', 'treatment_done_today', 'remarks', 'recorded_by',
])]
class AppointmentFinding extends Model
{
    protected function casts(): array
    {
        return [
            'cavity_found' => 'boolean',
            'tooth_mobility' => 'boolean',
            'xray_needed' => 'boolean',
            'missing_teeth' => 'integer',
            'existing_fillings' => 'integer',
        ];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}

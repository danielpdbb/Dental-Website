<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stage-1 pre-appointment assessment (patient- or reception-filled) for one
 * appointment. Feeds the procedure-recommendation regression.
 */
#[Fillable([
    'appointment_id', 'main_concern', 'pain_level',
    'toothache', 'sensitivity', 'bleeding_gums', 'bad_breath', 'swelling',
    'brushing_per_day', 'flosses', 'smoker', 'sugar_level', 'months_since_cleaning',
    'last_visit_bucket', 'notes', 'submitted_by',
])]
class AppointmentIntake extends Model
{
    protected function casts(): array
    {
        return [
            'pain_level' => 'integer',
            'toothache' => 'boolean',
            'sensitivity' => 'boolean',
            'bleeding_gums' => 'boolean',
            'bad_breath' => 'boolean',
            'swelling' => 'boolean',
            'flosses' => 'boolean',
            'smoker' => 'boolean',
            'brushing_per_day' => 'integer',
            'months_since_cleaning' => 'integer',
        ];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }
}

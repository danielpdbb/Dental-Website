<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'patient_id', 'toothache', 'sensitivity', 'bleeding_gums', 'bad_breath', 'swelling',
    'brushing_per_day', 'flosses', 'smoker', 'sugar_level', 'months_since_cleaning',
    'visible_plaque', 'decay_observed', 'gum_condition', 'missing_teeth', 'existing_fillings',
    'notes', 'updated_by',
])]
class ClinicalIntake extends Model
{
    protected function casts(): array
    {
        return [
            'toothache' => 'boolean',
            'sensitivity' => 'boolean',
            'bleeding_gums' => 'boolean',
            'bad_breath' => 'boolean',
            'swelling' => 'boolean',
            'flosses' => 'boolean',
            'smoker' => 'boolean',
            'visible_plaque' => 'boolean',
            'decay_observed' => 'boolean',
            'brushing_per_day' => 'integer',
            'months_since_cleaning' => 'integer',
            'missing_teeth' => 'integer',
            'existing_fillings' => 'integer',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}

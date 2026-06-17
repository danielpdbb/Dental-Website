<?php

namespace App\Models;

use App\Enums\AllergySeverity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['patient_id', 'name', 'severity', 'notes'])]
class Allergy extends Model
{
    protected function casts(): array
    {
        return [
            'severity' => AllergySeverity::class,
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}

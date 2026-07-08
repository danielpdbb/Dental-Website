<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One availability rule for a dentist — either a recurring weekly rule
 * (weekday set) or a specific-date override/block (date set).
 */
#[Fillable(['dentist_id', 'weekday', 'date', 'is_off', 'start_time', 'end_time', 'note'])]
class DentistSchedule extends Model
{
    protected function casts(): array
    {
        return [
            'weekday' => 'integer',
            'date' => 'date',
            'is_off' => 'boolean',
        ];
    }

    public function dentist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dentist_id');
    }

    /** "9:00 AM – 12:00 PM" or "Day off". */
    public function windowLabel(): string
    {
        if ($this->is_off) {
            return 'Unavailable (blocked)';
        }
        if ($this->start_time && $this->end_time) {
            return date('g:i A', strtotime($this->start_time)).' – '.date('g:i A', strtotime($this->end_time));
        }

        return 'Clinic default hours';
    }
}

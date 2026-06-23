<?php

namespace App\Models;

use App\Enums\ReferralSignupStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'referrer_id', 'referred_id', 'code', 'status',
    'qualifying_appointment_id', 'qualified_at', 'referrer_points', 'welcome_points',
])]
class ReferralSignup extends Model
{
    protected function casts(): array
    {
        return [
            'status' => ReferralSignupStatus::class,
            'qualified_at' => 'datetime',
            'referrer_points' => 'integer',
            'welcome_points' => 'integer',
        ];
    }

    /** The existing patient who shared their code. */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    /** The new patient who signed up with the code. */
    public function referred(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_id');
    }

    public function qualifyingAppointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'qualifying_appointment_id');
    }
}

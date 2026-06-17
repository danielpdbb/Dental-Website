<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'user_id', 'first_name', 'last_name', 'date_of_birth', 'gender', 'phone',
    'address', 'emergency_contact_name', 'emergency_contact_phone', 'blood_type',
    'medical_history', 'notes',
])]
class Patient extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Total still owed across all of this patient's appointments.
     */
    public function outstandingBalance(): float
    {
        return (float) $this->appointments
            ->sum(fn (Appointment $appointment) => $appointment->balance());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function allergies(): HasMany
    {
        return $this->hasMany(Allergy::class);
    }

    public function treatments(): HasMany
    {
        return $this->hasMany(Treatment::class)->latest('treatment_date');
    }

    public function recommendations(): HasMany
    {
        return $this->hasMany(ProcedureRecommendation::class)->latest();
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class)->latest();
    }
}

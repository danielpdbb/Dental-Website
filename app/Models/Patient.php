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

    public function intake(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ClinicalIntake::class);
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

    /**
     * Has the patient completed (and paid for) at least one visit? Gates procedure
     * recommendations and their download (per the clinic's workflow rules).
     */
    public function hasCompletedVisit(): bool
    {
        return $this->appointments()
            ->where('status', \App\Enums\AppointmentStatus::Completed->value)
            ->exists();
    }

    /**
     * Treatment history = procedures actually performed on COMPLETED (paid) visits.
     * This is what moves into "history" once the patient has paid.
     *
     * @return \Illuminate\Support\Collection<int, \App\Models\AppointmentProcedure>
     */
    public function treatmentHistoryQuery()
    {
        return AppointmentProcedure::query()
            ->where('status', \App\Enums\ProcedureStatus::Performed)
            ->whereHas('appointment', fn ($q) => $q
                ->where('patient_id', $this->id)
                ->where('status', \App\Enums\AppointmentStatus::Completed))
            ->with(['appointment', 'service', 'performer'])
            ->orderByDesc('performed_at');
    }

    public function treatmentHistory(): \Illuminate\Support\Collection
    {
        return $this->treatmentHistoryQuery()->get();
    }
}

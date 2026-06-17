<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'patient_id', 'dentist_id', 'service_id', 'scheduled_at', 'duration_minutes',
    'status', 'is_walk_in', 'notes', 'created_by', 'cancelled_by', 'cancelled_at',
    'cancellation_reason',
])]
class Appointment extends Model
{
    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'status' => AppointmentStatus::class,
            'is_walk_in' => 'boolean',
            'duration_minutes' => 'integer',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function dentist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dentist_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('scheduled_at', '>=', now())
            ->whereIn('status', [AppointmentStatus::Booked->value]);
    }

    public function scopePast(Builder $query): Builder
    {
        return $query->where('scheduled_at', '<', now())
            ->orWhereIn('status', [
                AppointmentStatus::Completed->value,
                AppointmentStatus::Cancelled->value,
                AppointmentStatus::NoShow->value,
            ]);
    }

    public function isCancellable(): bool
    {
        return $this->status === AppointmentStatus::Booked
            && $this->scheduled_at->isFuture();
    }
}

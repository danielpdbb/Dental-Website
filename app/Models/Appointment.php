<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'patient_id', 'dentist_id', 'service_id', 'scheduled_at', 'duration_minutes',
    'total_amount', 'status', 'is_walk_in', 'notes', 'created_by', 'cancelled_by',
    'cancelled_at', 'cancellation_reason',
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
            'total_amount' => 'decimal:2',
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

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest();
    }

    /**
     * Total successfully collected for this appointment.
     */
    public function amountPaid(): float
    {
        return (float) $this->payments
            ->where('status', PaymentStatus::Paid)
            ->sum('amount');
    }

    /**
     * Remaining balance = charge − collected (never negative).
     */
    public function balance(): float
    {
        return max(0, (float) $this->total_amount - $this->amountPaid());
    }

    public function isFullyPaid(): bool
    {
        return $this->balance() <= 0 && $this->total_amount > 0;
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

<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'patient_id', 'dentist_id', 'service_id', 'scheduled_at', 'duration_minutes',
    'total_amount', 'status', 'is_walk_in', 'notes', 'created_by', 'cancelled_by',
    'cancelled_at', 'cancellation_reason',
    'endorsed_at', 'endorsed_by', 'billed_at', 'billed_by',
])]
class Appointment extends Model
{
    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'endorsed_at' => 'datetime',
            'billed_at' => 'datetime',
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
     * The procedure line items ("current treatment") for this appointment.
     */
    public function procedures(): HasMany
    {
        return $this->hasMany(AppointmentProcedure::class);
    }

    /**
     * Recalculate total_amount + duration_minutes from the procedure line items.
     */
    public function recomputeTotals(): void
    {
        $this->loadMissing('procedures');

        $this->update([
            'total_amount' => round((float) $this->procedures->sum('price'), 2),
            'duration_minutes' => max(15, (int) $this->procedures->sum('duration_minutes')),
        ]);
    }

    /**
     * Comma-separated list of the procedures (falls back to the legacy service).
     */
    public function proceduresLabel(): string
    {
        $names = $this->procedures->pluck('procedure_name')->filter();

        return $names->isNotEmpty() ? $names->join(', ') : ($this->service?->name ?? '—');
    }

    public function billingStatement(): HasOne
    {
        return $this->hasOne(BillingStatement::class);
    }

    public function endorser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'endorsed_by');
    }

    /** Whether all listed procedures have been performed. */
    public function allProceduresPerformed(): bool
    {
        return $this->procedures->isNotEmpty()
            && $this->procedures->every(fn ($p) => $p->status === \App\Enums\ProcedureStatus::Performed);
    }

    /**
     * Once a billed visit is fully paid, mark it Completed → it becomes treatment
     * history and unlocks procedure recommendations. Safe to call after any payment.
     */
    public function settleIfPaid(): void
    {
        $this->load('payments'); // force-refresh in case a payment was just added

        $eligible = in_array($this->status, [
            AppointmentStatus::Billed,
            AppointmentStatus::ForBilling,
            AppointmentStatus::InTreatment,
        ], true);

        if ($eligible && $this->total_amount > 0 && $this->balance() <= 0) {
            $this->update(['status' => AppointmentStatus::Completed]);
        }
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

    /**
     * Payment is only accepted once the receptionist has issued the billing
     * statement (status Billed) — i.e. after the dentist performed & endorsed.
     */
    public function isPayable(): bool
    {
        return $this->status === AppointmentStatus::Billed && $this->balance() > 0;
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

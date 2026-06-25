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

    /** Stage-1 pre-appointment assessment (patient/reception filled). */
    public function intake(): HasOne
    {
        return $this->hasOne(AppointmentIntake::class);
    }

    /** Stage-2 dentist clinical findings. */
    public function finding(): HasOne
    {
        return $this->hasOne(AppointmentFinding::class);
    }

    /** Regression recommendations (Stage 1 + Stage 2) for this appointment. */
    public function recommendations(): HasMany
    {
        return $this->hasMany(AppointmentRecommendation::class)->latest();
    }

    /** Interactive odontogram annotations made this visit. */
    public function toothRecords(): HasMany
    {
        return $this->hasMany(ToothRecord::class);
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
            $this->stampInvoice();

            // Notify the patient their payment landed and the invoice is ready.
            $this->patient?->user?->notify(new \App\Notifications\PatientAlert(
                'Payment received',
                'We received full payment for your '.$this->scheduled_at->format('M j').' visit. Your official invoice is ready to print.',
                route('portal.appointments.index'),
                email: true,
            ));
        }
    }

    /**
     * On full payment, finalise the billing statement into an invoice (number + date)
     * so the patient and front desk can print an official receipt.
     */
    public function stampInvoice(): void
    {
        $statement = $this->billingStatement;
        if ($statement && ! $statement->invoice_no) {
            $statement->update([
                'invoice_no' => 'INV-'.now()->format('Ymd').'-'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT),
                'paid_at' => now(),
            ]);
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
     * Remaining balance = charge − collected (never negative). A cancelled or
     * no-show visit is never charged, so it can never carry a balance.
     */
    public function balance(): float
    {
        if (in_array($this->status, [AppointmentStatus::Cancelled, AppointmentStatus::NoShow], true)) {
            return 0.0;
        }

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

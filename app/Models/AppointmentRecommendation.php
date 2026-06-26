<?php

namespace App\Models;

use App\Enums\AdviceStatus;
use App\Enums\Priority;
use App\Enums\RecommendationSource;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A regression-produced recommendation tied to one appointment — either the
 * possible current treatment (Stage 1) or the recommended next visit (Stage 2).
 */
#[Fillable([
    'appointment_id', 'source', 'recommendation', 'linked_service_id', 'confidence',
    'priority', 'follow_up_weeks', 'suggested_at', 'status', 'created_by',
    'accepted_by', 'accepted_at', 'sent_to_patient_at', 'notes',
])]
class AppointmentRecommendation extends Model
{
    protected function casts(): array
    {
        return [
            'source' => RecommendationSource::class,
            'status' => AdviceStatus::class,
            'priority' => Priority::class,
            'confidence' => 'float',
            'follow_up_weeks' => 'integer',
            'suggested_at' => 'datetime',
            'accepted_at' => 'datetime',
            'sent_to_patient_at' => 'datetime',
        ];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'linked_service_id');
    }

    public function acceptor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    public function isAccepted(): bool
    {
        return $this->status === AdviceStatus::Accepted;
    }

    /**
     * Accepted next-visit recommendations that were sent to the patient and whose
     * suggested date hasn't already passed — newest-soonest first (undated last).
     * Used by the portal booking pages and the patient dashboard so they all match.
     */
    public function scopeSentUpcoming(Builder $query): Builder
    {
        return $query
            ->where('source', RecommendationSource::Stage2Next->value)
            ->whereNotNull('sent_to_patient_at')
            ->where(fn ($q) => $q->whereNull('suggested_at')->orWhere('suggested_at', '>=', now()))
            ->orderByRaw('suggested_at IS NULL')
            ->orderBy('suggested_at');
    }
}

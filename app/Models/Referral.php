<?php

namespace App\Models;

use App\Enums\ReferralStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'patient_id', 'service_id', 'reason', 'status', 'requested_by', 'handled_by', 'notes',
    'referred_to_name', 'referred_to_clinic', 'referred_to_address', 'letter_notes',
    'letter_no', 'letter_issued_at', 'letter_issued_by',
])]
class Referral extends Model
{
    protected function casts(): array
    {
        return [
            'status' => ReferralStatus::class,
            'letter_issued_at' => 'datetime',
        ];
    }

    public function letterIssuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'letter_issued_by');
    }

    public function hasLetter(): bool
    {
        return $this->letter_issued_at !== null;
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}

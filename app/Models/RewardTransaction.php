<?php

namespace App\Models;

use App\Enums\RewardTransactionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'type', 'points', 'description',
    'referral_signup_id', 'appointment_id', 'payment_id', 'recorded_by', 'expires_at',
])]
class RewardTransaction extends Model
{
    protected function casts(): array
    {
        return [
            'type' => RewardTransactionType::class,
            'points' => 'integer',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function referralSignup(): BelongsTo
    {
        return $this->belongsTo(ReferralSignup::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}

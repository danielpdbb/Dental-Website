<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Referral;
use App\Models\User;

class ReferralPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->role === UserRole::Management ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return true; // patients see their own (scoped), receptionists track all
    }

    public function view(User $user, Referral $referral): bool
    {
        if ($user->role === UserRole::Receptionist) {
            return true;
        }

        return $user->role === UserRole::Patient && $referral->patient?->user_id === $user->id;
    }

    /**
     * Patients request referrals; receptionists may also create them.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::Patient, UserRole::Receptionist], true);
    }

    /**
     * Only the front desk updates referral status.
     */
    public function update(User $user, Referral $referral): bool
    {
        return $user->role === UserRole::Receptionist;
    }
}

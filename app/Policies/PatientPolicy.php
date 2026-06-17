<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\User;

class PatientPolicy
{
    /**
     * Management has full access to everything.
     */
    public function before(User $user, string $ability): ?bool
    {
        return $user->role === UserRole::Management ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $this->isClinicalStaff($user);
    }

    /**
     * Staff may view any record; a patient may view only their own.
     */
    public function view(User $user, Patient $patient): bool
    {
        if ($this->isClinicalStaff($user)) {
            return true;
        }

        return $user->role === UserRole::Patient && $patient->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $this->isClinicalStaff($user);
    }

    public function update(User $user, Patient $patient): bool
    {
        return $this->isClinicalStaff($user);
    }

    public function delete(User $user, Patient $patient): bool
    {
        return false; // only Management, handled by before()
    }

    private function isClinicalStaff(User $user): bool
    {
        return in_array($user->role, [UserRole::Receptionist, UserRole::Dentist], true);
    }
}

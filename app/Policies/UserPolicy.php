<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Only Management may view the user list.
     */
    public function viewAny(User $user): bool
    {
        return $user->canManageUsers();
    }

    public function view(User $user, User $model): bool
    {
        return $user->canManageUsers();
    }

    public function create(User $user): bool
    {
        return $user->canManageUsers();
    }

    /**
     * Management may edit anyone, including themselves.
     */
    public function update(User $user, User $model): bool
    {
        return $user->canManageUsers();
    }

    /**
     * Management may delete others, but never their own account
     * (prevents locking the last admin out of the system).
     */
    public function delete(User $user, User $model): bool
    {
        return $user->canManageUsers() && $user->id !== $model->id;
    }
}

<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->role === UserRole::Management ? true : null;
    }

    /**
     * Everyone authenticated can see "appointments" — patients get their own
     * list (scoped in the controller), staff get all.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Appointment $appointment): bool
    {
        if ($this->isStaff($user)) {
            return true;
        }

        return $this->ownsAppointment($user, $appointment);
    }

    /**
     * Patients book their own; receptionists book/walk-in for anyone.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::Patient, UserRole::Receptionist], true);
    }

    /**
     * Receptionists can cancel any; patients can cancel their own future bookings.
     */
    public function cancel(User $user, Appointment $appointment): bool
    {
        if ($user->role === UserRole::Receptionist) {
            return true;
        }

        return $user->role === UserRole::Patient
            && $this->ownsAppointment($user, $appointment)
            && $appointment->isCancellable();
    }

    /**
     * Rescheduling — same rules as cancelling (own future booking, or front desk).
     */
    public function reschedule(User $user, Appointment $appointment): bool
    {
        if ($user->role === UserRole::Receptionist) {
            return true;
        }

        return $user->role === UserRole::Patient
            && $this->ownsAppointment($user, $appointment)
            && $appointment->isCancellable();
    }

    /**
     * Marking completed / no-show is a front-desk action.
     */
    public function updateStatus(User $user, Appointment $appointment): bool
    {
        return $user->role === UserRole::Receptionist;
    }

    private function isStaff(User $user): bool
    {
        return in_array($user->role, [UserRole::Receptionist, UserRole::Dentist], true);
    }

    private function ownsAppointment(User $user, Appointment $appointment): bool
    {
        return $appointment->patient?->user_id === $user->id;
    }
}

<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\StaffAlert;
use Illuminate\Support\Facades\Notification;

/**
 * Tiny helper for pushing in-app bell notifications to staff. Keeps the "who counts
 * as the front desk" rule in one place so controllers stay readable.
 */
class Notifier
{
    /**
     * Alert the front desk (receptionists + management) — e.g. new booking, cancellation,
     * a visit ready to bill, an online payment.
     */
    public static function desk(string $title, string $body, ?string $url = null): void
    {
        $staff = User::whereIn('role', [UserRole::Receptionist->value, UserRole::Management->value])->get();

        if ($staff->isNotEmpty()) {
            Notification::send($staff, new StaffAlert($title, $body, $url));
        }
    }

    /** Alert one specific staff user (e.g. the dentist on a visit). No-op if null. */
    public static function user(?User $user, string $title, string $body, ?string $url = null): void
    {
        $user?->notify(new StaffAlert($title, $body, $url));
    }
}

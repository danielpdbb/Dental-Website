<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Clinic-branded version of Laravel's password-reset message.
 * Extends the framework class so the signed reset URL + token handling are done
 * for us; we only customise the wording/branding to match the verification email.
 */
class ResetPasswordNotification extends BaseResetPassword
{
    protected function buildMailMessage($url): MailMessage
    {
        $minutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

        return (new MailMessage)
            ->subject("Reset your password — Bonoan's Dental Clinic")
            ->greeting('Password reset request')
            ->line('We received a request to reset the password for your Bonoan\'s Dental Clinic account.')
            ->action('Reset my password', $url)
            ->line("For your security, this link expires in {$minutes} minutes.")
            ->line("If you didn't request a password reset, no action is needed — your password will stay the same.")
            ->salutation("Your smile. Our passion. Our pride.\nBonoan's Dental Clinic — Bonoan, Dagupan City");
    }
}

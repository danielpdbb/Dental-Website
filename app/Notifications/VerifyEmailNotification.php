<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Clinic-branded version of Laravel's email-verification message.
 * Extends the framework class so the signed verification URL is generated for us;
 * we only customise the wording/branding.
 */
class VerifyEmailNotification extends BaseVerifyEmail
{
    protected function buildMailMessage($url): MailMessage
    {
        return (new MailMessage)
            ->subject("Verify your email — Bonoan's Dental Clinic")
            ->greeting("Welcome to Bonoan's Dental Clinic!")
            ->line('Thank you for creating your patient account. Please confirm your email address to activate your account and start booking appointments online.')
            ->action('Verify my email', $url)
            ->line('For your security, this link expires in 60 minutes.')
            ->line("If you didn't create an account with us, no further action is required.")
            ->salutation("Your smile. Our passion. Our pride.\nBonoan's Dental Clinic — Bonoan, Dagupan City");
    }
}

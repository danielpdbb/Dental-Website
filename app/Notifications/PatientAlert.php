<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Generic patient-facing alert for the in-app bell (and optionally email):
 * bill ready, payment received, booking confirmed, reminders, etc.
 */
class PatientAlert extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $body,
        public ?string $url = null,
        public bool $email = false,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return $this->email ? ['database', 'mail'] : ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return ['title' => $this->title, 'body' => $this->body, 'url' => $this->url];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title)
            ->greeting('Bonoan’s Dental Clinic')
            ->line($this->body)
            ->action('Open your dashboard', $this->url ?? route('dashboard'));
    }
}

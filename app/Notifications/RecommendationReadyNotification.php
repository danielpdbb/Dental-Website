<?php

namespace App\Notifications;

use App\Models\AppointmentRecommendation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * In-app (database) notification the patient sees in the bell when their dentist
 * sends an accepted recommendation. Email is sent separately via RecommendationReadyMail.
 */
class RecommendationReadyNotification extends Notification
{
    use Queueable;

    public function __construct(public AppointmentRecommendation $recommendation) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'recommendation_id' => $this->recommendation->id,
            'title' => 'New recommendation from your dentist',
            'body' => $this->recommendation->recommendation,
            'source' => $this->recommendation->source->label(),
            'url' => route('portal.recommendations.print', $this->recommendation),
        ];
    }
}

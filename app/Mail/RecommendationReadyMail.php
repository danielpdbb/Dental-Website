<?php

namespace App\Mail;

use App\Models\AppointmentRecommendation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RecommendationReadyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public AppointmentRecommendation $recommendation) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your dentist’s recommendation — Bonoan’s Dental Clinic',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.recommendation-ready',
            with: ['rec' => $this->recommendation->loadMissing(['appointment.patient', 'appointment.dentist', 'service'])],
        );
    }
}

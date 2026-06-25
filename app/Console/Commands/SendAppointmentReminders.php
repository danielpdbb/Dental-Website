<?php

namespace App\Console\Commands;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Notifications\PatientAlert;
use Illuminate\Console\Command;

/**
 * Reminds patients (bell + email) about their appointment scheduled for tomorrow.
 * Run daily via the scheduler (see routes/console.php).
 */
class SendAppointmentReminders extends Command
{
    protected $signature = 'appointments:send-reminders';

    protected $description = 'Notify patients about appointments scheduled for tomorrow';

    public function handle(): int
    {
        $appts = Appointment::with('patient.user', 'dentist')
            ->whereDate('scheduled_at', now()->addDay()->toDateString())
            ->whereIn('status', [AppointmentStatus::Booked->value, AppointmentStatus::Billed->value])
            ->get();

        $sent = 0;
        foreach ($appts as $a) {
            $user = $a->patient?->user;
            if (! $user) {
                continue;
            }
            $user->notify(new PatientAlert(
                'Appointment reminder',
                'Reminder: you have an appointment with '.($a->dentist?->name ?? 'your dentist').' tomorrow at '.$a->scheduled_at->format('g:i A').'.',
                route('portal.appointments.index'),
                email: true,
            ));
            $sent++;
        }

        $this->info("Sent {$sent} appointment reminder(s).");

        return self::SUCCESS;
    }
}

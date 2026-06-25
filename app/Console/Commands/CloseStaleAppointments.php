<?php

namespace App\Console\Commands;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Illuminate\Console\Command;

/**
 * Marks past-dated appointments that were never processed as no-show, so a booking
 * whose date has passed doesn't linger forever as "Booked".
 */
class CloseStaleAppointments extends Command
{
    protected $signature = 'appointments:close-stale';

    protected $description = 'Mark past-dated, still-booked appointments as no-show';

    public function handle(): int
    {
        $count = Appointment::where('status', AppointmentStatus::Booked->value)
            ->where('scheduled_at', '<', now()->startOfDay())
            ->update(['status' => AppointmentStatus::NoShow->value]);

        $this->info("Closed {$count} stale appointment(s) as no-show.");

        return self::SUCCESS;
    }
}

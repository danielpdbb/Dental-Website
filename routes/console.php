<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Lapse stale reward points once a day (requires the system scheduler/cron to be
// running in production: `php artisan schedule:work` or a real cron entry).
Schedule::command('rewards:expire')->dailyAt('02:00');

// Close past-dated, still-booked appointments as no-show each morning.
Schedule::command('appointments:close-stale')->dailyAt('01:00');

// Remind patients about tomorrow's appointments (bell + email).
Schedule::command('appointments:send-reminders')->dailyAt('08:00');

// Retrain the predictive-scheduling Decision Tree monthly on the latest history.
Schedule::command('ml:scheduling:train')->monthlyOn(1, '03:00');

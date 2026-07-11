<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // One definition of "what makes a strong password", used by every form.
        Password::defaults(function () {
            return Password::min(8)
                ->mixedCase()   // at least one upper- and one lower-case letter
                ->numbers()     // at least one digit
                ->symbols()     // at least one special character
                ->uncompromised(); // reject passwords found in known data breaches
        });

        $this->overlayClinicSettings();
    }

    /**
     * Overlay UI-managed store settings (clinic_settings table) onto the
     * config/clinic.php defaults, so opening days/hours edited by management apply
     * everywhere config('clinic.*') is read — calendars, slot grids, validation.
     * Silently skipped when the DB/table isn't there yet (fresh install,
     * mid-migration, artisan without a DB).
     */
    private function overlayClinicSettings(): void
    {
        try {
            $settings = \App\Models\ClinicSetting::all_();
        } catch (\Throwable) {
            return;
        }

        foreach (['open_days', 'open_time', 'close_time'] as $key) {
            if (array_key_exists($key, $settings) && $settings[$key] !== null && $settings[$key] !== []) {
                config(["clinic.$key" => $settings[$key]]);
            }
        }
    }
}

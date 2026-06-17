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
    }
}

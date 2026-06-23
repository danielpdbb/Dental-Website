<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\RewardService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Show the patient sign-up form.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Register a new patient account.
     */
    public function store(RegisterRequest $request, RewardService $rewards): RedirectResponse
    {
        $name = $request->validated('name');

        $user = User::create([
            'name' => $name,
            'username' => $request->validated('username'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
            // Public sign-ups are always patients, and start unverified.
            'role' => UserRole::Patient,
            'is_active' => true,
            // Stamp the moment they accepted the Data Privacy Act consent.
            'data_privacy_consent_at' => now(),
        ]);

        // Create their patient record with the details collected at sign-up.
        $user->patient()->create([
            'first_name' => Str::before($name, ' ') ?: $name,
            'last_name' => Str::contains($name, ' ') ? Str::after($name, ' ') : '',
            'phone' => $request->validated('mobile'),
            'gender' => $request->validated('gender'),
            'date_of_birth' => $request->validated('date_of_birth'),
            'address' => $request->validated('address'),
        ]);

        // Link them to whoever referred them (if a valid code was entered) and
        // give them their own shareable code straight away.
        $rewards->attachReferrer($user, $request->validated('referral_code'));
        $rewards->codeFor($user);

        // Fires the listener that emails the verification link.
        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('verification.notice');
    }
}

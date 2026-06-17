<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
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
    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = User::create([
            'name' => $request->validated('name'),
            'username' => $request->validated('username'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
            // Public sign-ups are always patients, and start unverified.
            'role' => UserRole::Patient,
            'is_active' => true,
        ]);

        // Fires the listener that emails the verification link.
        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('verification.notice');
    }
}

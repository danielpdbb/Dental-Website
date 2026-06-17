<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the patient/staff login form.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle a login attempt and redirect the user to their role's home.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        // Reject accounts an admin has suspended.
        if (! $request->user()->is_active) {
            Auth::logout();
            $request->session()->invalidate();

            return back()->withErrors([
                'login' => 'This account has been deactivated. Please contact the clinic.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route($request->user()->role->homeRoute()));
    }

    /**
     * Log the user out and invalidate the session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}

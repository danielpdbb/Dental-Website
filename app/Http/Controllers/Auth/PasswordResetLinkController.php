<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Show the "forgot password" form (enter your email).
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Email a password-reset link to the address, if it exists.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        // Always responds the same way whether or not the account exists, so we
        // never leak which emails are registered.
        Password::sendResetLink($request->only('email'));

        return back()->with('status', 'If an account matches that email, a reset link is on its way.');
    }
}

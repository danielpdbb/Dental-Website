<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Show the dedicated admin login screen.
     */
    public function create(): View
    {
        return view('admin.login');
    }

    /**
     * Authenticate and ensure the user is Management before entering /admin.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = $request->user();

        // Only Management may enter the admin area.
        if (! $user->canManageUsers() || ! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();

            return back()->withErrors([
                'login' => 'These credentials do not have admin access.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    /**
     * Log out of the admin area.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}

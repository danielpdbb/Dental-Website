<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restrict a route to one or more roles.
 *
 * Usage:  ->middleware('role:management')
 *         ->middleware('role:management,dentist')
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // Not logged in → send to login (defensive; the auth middleware usually handles this).
        if ($user === null) {
            return redirect()->guest(route('login'));
        }

        $allowed = array_map(
            fn (string $role) => UserRole::from($role),
            $roles,
        );

        // Wrong role → bounce them back to their OWN dashboard with a clear message,
        // rather than showing a bare 403 or dumping them on the public home page.
        if (! in_array($user->role, $allowed, true)) {
            return redirect()
                ->route($user->role->homeRoute())
                ->with('error', 'You do not have permission to access that area.');
        }

        return $next($request);
    }
}

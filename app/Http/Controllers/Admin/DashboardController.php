<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Admin overview with high-level user counts.
     */
    public function index(): View
    {
        return view('admin.dashboard', [
            'totalUsers' => User::count(),
            'verifiedUsers' => User::whereNotNull('email_verified_at')->count(),
            'inactiveUsers' => User::where('is_active', false)->count(),
            'roleCounts' => collect(UserRole::cases())->mapWithKeys(fn (UserRole $role) => [
                $role->label() => User::where('role', $role->value)->count(),
            ]),
        ]);
    }
}

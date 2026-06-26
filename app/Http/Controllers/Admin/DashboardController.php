<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Management overview — clinic operations + people at a glance.
     */
    public function index(): View
    {
        $today = Carbon::today();
        $monthStart = Carbon::now()->startOfMonth();

        $billed = Appointment::where('status', AppointmentStatus::Billed->value)->with('payments')->get();
        $outstanding = (float) $billed->sum(fn (Appointment $a) => $a->balance());

        // 6-month collected-revenue mini trend.
        $trend = collect(range(5, 0))->map(function ($i) {
            $m = Carbon::now()->startOfMonth()->subMonths($i);
            return [
                'label' => $m->format('M'),
                'value' => (float) Payment::where('status', PaymentStatus::Paid->value)
                    ->whereBetween('paid_at', [$m->copy()->startOfMonth(), $m->copy()->endOfMonth()])
                    ->sum('amount'),
            ];
        });

        return view('admin.dashboard', [
            'revenueMonth' => (float) Payment::where('status', PaymentStatus::Paid->value)
                ->where('paid_at', '>=', $monthStart)->sum('amount'),
            'apptsToday' => Appointment::whereDate('scheduled_at', $today)->count(),
            'apptsTodayDone' => Appointment::whereDate('scheduled_at', $today)->where('status', AppointmentStatus::Completed->value)->count(),
            'outstanding' => $outstanding,
            'forBilling' => Appointment::where('status', AppointmentStatus::ForBilling->value)->count(),
            'billedUnpaid' => $billed->filter(fn (Appointment $a) => $a->balance() > 0)->count(),
            'totalPatients' => Patient::count(),
            'noShowMonth' => Appointment::where('status', AppointmentStatus::NoShow->value)->where('scheduled_at', '>=', $monthStart)->count(),
            'recent' => Appointment::with(['patient', 'dentist', 'service'])->latest('scheduled_at')->take(6)->get(),
            'trend' => $trend,
            'totalUsers' => User::count(),
            'verifiedUsers' => User::whereNotNull('email_verified_at')->count(),
            'inactiveUsers' => User::where('is_active', false)->count(),
            'roleCounts' => collect(UserRole::cases())->mapWithKeys(fn (UserRole $role) => [
                $role->label() => User::where('role', $role->value)->count(),
            ]),
        ]);
    }
}

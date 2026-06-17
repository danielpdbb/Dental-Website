<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Payment;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function index(): View
    {
        $total = Appointment::count();
        $cancelled = Appointment::where('status', AppointmentStatus::Cancelled->value)->count();
        $noShow = Appointment::where('status', AppointmentStatus::NoShow->value)->count();

        // Last 6 months: appointment volume + collected revenue.
        $months = collect(range(5, 0))->map(function (int $i) {
            $month = now()->startOfMonth()->subMonths($i);

            return [
                'label' => $month->format('M Y'),
                'appointments' => Appointment::whereYear('scheduled_at', $month->year)
                    ->whereMonth('scheduled_at', $month->month)->count(),
                'revenue' => Payment::where('status', PaymentStatus::Paid->value)
                    ->whereYear('paid_at', $month->year)
                    ->whereMonth('paid_at', $month->month)->sum('amount'),
            ];
        });

        return view('admin.analytics.index', [
            'totalAppointments' => $total,
            'totalRevenue' => Payment::where('status', PaymentStatus::Paid->value)->sum('amount'),
            'cancellationRate' => $total > 0 ? round($cancelled / $total * 100, 1) : 0,
            'noShowRate' => $total > 0 ? round($noShow / $total * 100, 1) : 0,
            'statusCounts' => collect(AppointmentStatus::cases())->mapWithKeys(fn ($s) => [
                $s->label() => Appointment::where('status', $s->value)->count(),
            ]),
            'months' => $months,
        ]);
    }
}

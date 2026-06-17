<?php

namespace App\Http\Controllers\Clinic;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    /**
     * Record (or update) the payment for an appointment. One payment per appointment.
     */
    public function store(Request $request, Appointment $appointment): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, [UserRole::Receptionist, UserRole::Management], true), 403);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'status' => ['required', Rule::enum(PaymentStatus::class)],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $appointment->payment()->updateOrCreate([], [
            ...$data,
            'paid_at' => $data['status'] === PaymentStatus::Paid->value ? now() : null,
            'recorded_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Payment saved.');
    }
}

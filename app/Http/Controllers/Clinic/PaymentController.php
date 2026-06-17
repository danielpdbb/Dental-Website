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
     * Record a payment against an appointment. Partial payments are allowed —
     * each call adds a new payment row; the balance is charge − total paid.
     */
    public function store(Request $request, Appointment $appointment): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, [UserRole::Receptionist, UserRole::Management], true), 403);

        $appointment->load('payments');

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:1000000'],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'status' => ['required', Rule::enum(PaymentStatus::class)],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        // Don't let a "paid" entry exceed what's still owed.
        if ($data['status'] === PaymentStatus::Paid->value && $data['amount'] > $appointment->balance() + 0.001) {
            return back()->withErrors([
                'amount' => 'Amount exceeds the remaining balance of ₱'.number_format($appointment->balance(), 2).'.',
            ]);
        }

        $appointment->payments()->create([
            ...$data,
            'gateway' => 'manual',
            'paid_at' => $data['status'] === PaymentStatus::Paid->value ? now() : null,
            'recorded_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Payment recorded.');
    }
}

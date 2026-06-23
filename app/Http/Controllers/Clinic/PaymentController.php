<?php

namespace App\Http\Controllers\Clinic;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Services\RewardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    /**
     * Record a payment against an appointment. Partial payments are allowed —
     * each call adds a new payment row; the balance is charge − total paid.
     */
    public function store(Request $request, Appointment $appointment, RewardService $rewards): RedirectResponse
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

        // In strict mode a referral only qualifies once the visit is paid.
        if ($data['status'] === PaymentStatus::Paid->value) {
            $appointment->settleIfPaid(); // billed + fully paid → Completed (→ history)
            $rewards->checkQualification($appointment->patient?->user);
        }

        return back()->with('status', 'Payment recorded.');
    }

    /**
     * Apply a patient's rewards credit to their bill from the front desk.
     */
    public function redeemRewards(Request $request, Appointment $appointment, RewardService $rewards): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, [UserRole::Receptionist, UserRole::Management], true), 403);

        $patientUser = $appointment->patient?->user;
        if (! $patientUser) {
            return back()->with('error', 'This patient has no portal account, so there are no rewards to apply.');
        }

        $max = $rewards->maxRedeemablePeso($patientUser, $appointment);
        if ($max <= 0) {
            return back()->with('error', 'No rewards credit is available to apply here.');
        }

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
        ]);
        $amount = min((float) $data['amount'], $max);

        try {
            $payment = $rewards->redeem($patientUser, $appointment, $amount, $request->user());
        } catch (\Throwable $e) {
            return back()->with('error', 'Could not apply rewards credit.');
        }

        return back()->with('status', '₱'.number_format($payment->amount, 2).' rewards credit applied.');
    }
}

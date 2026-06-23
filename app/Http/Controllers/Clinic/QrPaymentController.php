<?php

namespace App\Http\Controllers\Clinic;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Payment;
use App\Services\PayMongoService;
use App\Services\RewardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * In-store "scan to pay" via PayMongo QR Ph. The receptionist generates a QR for
 * the bill; the patient scans it with GCash (or any QR Ph app). Confirmed by
 * polling the PaymentIntent. With TEST keys no real money can move.
 */
class QrPaymentController extends Controller
{
    /**
     * Generate a QR Ph PaymentIntent for the appointment's balance.
     */
    public function generate(Request $request, Appointment $appointment, PayMongoService $paymongo): RedirectResponse
    {
        $this->guardStaff($request);

        if (! $paymongo->isConfigured()) {
            return back()->with('error', 'Online payment is not configured.');
        }
        // Safety: never allow live QR charges unless explicitly enabled.
        if (! $paymongo->isTestMode() && ! config('services.paymongo.allow_live_qr')) {
            return back()->with('error', 'QR payments are restricted to test mode on this environment.');
        }
        if (! $appointment->isPayable()) {
            return back()->with('error', 'This appointment is not ready for payment.');
        }

        $balance = $appointment->balance();

        // Drop any earlier abandoned QR attempts so they don't pile up.
        $appointment->payments()
            ->where('gateway', 'paymongo_qr')
            ->where('status', PaymentStatus::Pending->value)
            ->delete();

        $payment = $appointment->payments()->create([
            'amount' => $balance,
            'method' => PaymentMethod::Gcash,
            'status' => PaymentStatus::Pending,
            'gateway' => 'paymongo_qr',
            'recorded_by' => $request->user()->id,
        ]);

        try {
            $intent = $paymongo->createQrPhPayment(
                (int) round($balance * 100),
                'Appointment #'.$appointment->id.' payment',
                ['appointment_id' => $appointment->id, 'payment_id' => $payment->id],
            );
        } catch (\Throwable $e) {
            report($e); // e.g. QR Ph not enabled on the account
            $payment->delete();

            return back()->with('error', 'Could not generate the QR. Make sure QR Ph is enabled on your PayMongo account.');
        }

        $payment->update(['reference' => $intent['id']]);

        return redirect()->route('clinic.appointments.qr.show', [$appointment, $payment]);
    }

    /**
     * Display the QR for scanning (auto-checks status).
     */
    public function show(Request $request, Appointment $appointment, Payment $payment, PayMongoService $paymongo): View|RedirectResponse
    {
        $this->guardStaff($request);
        abort_unless($payment->appointment_id === $appointment->id && $payment->gateway === 'paymongo_qr', 404);

        if ($payment->status === PaymentStatus::Paid) {
            return redirect()->route('clinic.appointments.show', $appointment)->with('status', 'Payment already recorded.');
        }

        try {
            $intent = $paymongo->retrievePaymentIntent($payment->reference);
        } catch (\Throwable $e) {
            return redirect()->route('clinic.appointments.show', $appointment)->with('error', 'Could not load the QR.');
        }

        return view('clinic.appointments.qr', [
            'appointment' => $appointment,
            'payment' => $payment,
            'qrUrl' => $paymongo->qrImageUrl($intent),
            'testUrl' => $paymongo->isTestMode() ? $paymongo->qrTestUrl($intent) : null,
            'status' => $paymongo->intentStatus($intent),
        ]);
    }

    /**
     * Re-check the PaymentIntent; mark paid when PayMongo confirms.
     */
    public function check(Request $request, Appointment $appointment, Payment $payment, PayMongoService $paymongo, RewardService $rewards): RedirectResponse
    {
        $this->guardStaff($request);
        abort_unless($payment->appointment_id === $appointment->id && $payment->gateway === 'paymongo_qr', 404);

        if ($payment->status === PaymentStatus::Paid) {
            return redirect()->route('clinic.appointments.show', $appointment)->with('status', 'Payment recorded.');
        }

        try {
            $intent = $paymongo->retrievePaymentIntent($payment->reference);
        } catch (\Throwable $e) {
            return redirect()->route('clinic.appointments.qr.show', [$appointment, $payment]);
        }

        if ($paymongo->intentStatus($intent) === 'succeeded') {
            $payment->update([
                'status' => PaymentStatus::Paid,
                'paid_at' => now(),
                'transaction_id' => $intent['id'],
            ]);

            // Clear any other abandoned pending attempts for this appointment.
            $appointment->payments()
                ->where('id', '!=', $payment->id)
                ->where('status', PaymentStatus::Pending->value)
                ->delete();

            $appointment->settleIfPaid();
            $rewards->checkQualification($appointment->patient?->user);

            return redirect()->route('clinic.appointments.show', $appointment)
                ->with('status', 'GCash payment of ₱'.number_format($payment->amount, 2).' received.');
        }

        // Still waiting — go back to the QR page (which keeps polling).
        return redirect()->route('clinic.appointments.qr.show', [$appointment, $payment]);
    }

    public function cancel(Request $request, Appointment $appointment, Payment $payment): RedirectResponse
    {
        $this->guardStaff($request);
        abort_unless($payment->appointment_id === $appointment->id && $payment->gateway === 'paymongo_qr', 404);

        if ($payment->status === PaymentStatus::Pending) {
            $payment->delete();
        }

        return redirect()->route('clinic.appointments.show', $appointment)->with('status', 'QR payment cancelled.');
    }

    private function guardStaff(Request $request): void
    {
        abort_unless(in_array($request->user()->role, [UserRole::Receptionist, UserRole::Management], true), 403);
    }
}

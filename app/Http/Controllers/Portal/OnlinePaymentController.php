<?php

namespace App\Http\Controllers\Portal;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Services\PayMongoService;
use App\Services\RewardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OnlinePaymentController extends Controller
{
    /**
     * Start a PayMongo checkout for (part of) an appointment's balance.
     */
    public function checkout(Request $request, Appointment $appointment, PayMongoService $paymongo): RedirectResponse
    {
        $this->authorize('view', $appointment); // patient must own this appointment

        if (! $paymongo->isConfigured()) {
            return back()->with('error', 'Online payment is not configured yet.');
        }

        $appointment->load('payments');
        $balance = $appointment->balance();
        abort_if($balance <= 0, 400);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1', 'max:'.$balance],
        ]);
        $amount = round((float) $data['amount'], 2);

        // Pending payment row — only flips to "paid" (and counts) once confirmed.
        $payment = $appointment->payments()->create([
            'amount' => $amount,
            'method' => PaymentMethod::Gcash,
            'status' => PaymentStatus::Pending,
            'gateway' => 'paymongo',
            'recorded_by' => $request->user()->id,
        ]);

        try {
            $session = $paymongo->createCheckoutSession([
                'line_items' => [[
                    'name' => ($appointment->service?->name ?? 'Dental appointment').' payment',
                    'amount' => (int) round($amount * 100), // centavos
                    'currency' => 'PHP',
                    'quantity' => 1,
                ]],
                'payment_method_types' => config('services.paymongo.methods', ['card', 'gcash']),
                'description' => 'Payment for appointment #'.$appointment->id,
                'reference_number' => 'APPT-'.$appointment->id.'-'.$payment->id,
                'success_url' => route('portal.appointments.pay.success', $appointment).'?pid='.$payment->id,
                'cancel_url' => route('portal.appointments.pay.cancel', $appointment).'?pid='.$payment->id,
            ]);
        } catch (\Throwable $e) {
            report($e); // log the real reason (SSL/auth/validation) for diagnosis
            $payment->delete();

            return back()->with('error', 'Could not start the online payment. Please try again.');
        }

        $payment->update(['reference' => $session['id']]);

        return redirect()->away($session['attributes']['checkout_url']);
    }

    /**
     * PayMongo redirects here after the patient authorizes — verify and record.
     */
    public function success(Request $request, Appointment $appointment, PayMongoService $paymongo, RewardService $rewards): RedirectResponse
    {
        $payment = $appointment->payments()
            ->where('id', $request->integer('pid'))
            ->where('gateway', 'paymongo')
            ->first();

        if (! $payment || ! $payment->reference) {
            return redirect()->route('portal.appointments.index')->with('error', 'Payment record not found.');
        }
        if ($payment->status === PaymentStatus::Paid) {
            return redirect()->route('portal.appointments.index')->with('status', 'Payment already recorded. Thank you!');
        }

        try {
            $session = $paymongo->retrieveCheckoutSession($payment->reference);
        } catch (\Throwable $e) {
            return redirect()->route('portal.appointments.index')
                ->with('error', 'We could not verify the payment yet. It will update automatically once confirmed.');
        }

        if ($paid = $paymongo->paidPayment($session)) {
            $payment->update([
                'status' => PaymentStatus::Paid,
                'paid_at' => now(),
                'transaction_id' => $paid['id'] ?? null,
                'method' => $this->mapMethod($paid['attributes']['source']['type'] ?? null),
            ]);

            // Settle the visit if this clears the balance, then check referral reward.
            $appointment->settleIfPaid();
            $rewards->checkQualification($appointment->patient?->user);

            return redirect()->route('portal.appointments.index')
                ->with('status', 'Payment of ₱'.number_format($payment->amount, 2).' received. Thank you!');
        }

        return redirect()->route('portal.appointments.index')->with('error', 'Payment was not completed.');
    }

    /**
     * Patient backed out on PayMongo — drop the pending row.
     */
    public function cancel(Request $request, Appointment $appointment): RedirectResponse
    {
        $appointment->payments()
            ->where('id', $request->integer('pid'))
            ->where('status', PaymentStatus::Pending->value)
            ->delete();

        return redirect()->route('portal.appointments.index')->with('error', 'Online payment cancelled.');
    }

    private function mapMethod(?string $type): PaymentMethod
    {
        return match ($type) {
            'card' => PaymentMethod::Card,
            'gcash' => PaymentMethod::Gcash,
            default => PaymentMethod::Gcash,
        };
    }
}

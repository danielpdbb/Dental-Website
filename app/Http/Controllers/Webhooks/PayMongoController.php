<?php

namespace App\Http\Controllers\Webhooks;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Receives PayMongo webhook events (the reliable, async confirmation).
 * Marks the matching pending payment as paid. Signature is verified when a
 * webhook secret is configured (set PAYMONGO_WEBHOOK_SECRET after creating the
 * webhook in the PayMongo dashboard).
 */
class PayMongoController extends Controller
{
    public function handle(Request $request): Response
    {
        $secret = config('services.paymongo.webhook_secret');

        if ($secret && ! $this->signatureValid($request, $secret)) {
            return response('Invalid signature', 401);
        }

        $type = $request->input('data.attributes.type');

        if (in_array($type, ['checkout_session.payment.paid', 'payment.paid'], true)) {
            // The embedded resource is the checkout session (or payment) object.
            $resourceId = $request->input('data.attributes.data.id');

            if ($resourceId) {
                Payment::where('gateway', 'paymongo')
                    ->where('reference', $resourceId)
                    ->where('status', '!=', PaymentStatus::Paid->value)
                    ->each(function (Payment $payment) {
                        $payment->update(['status' => PaymentStatus::Paid, 'paid_at' => now()]);
                    });
            }
        }

        return response('ok', 200);
    }

    /**
     * Verify the Paymongo-Signature header: HMAC-SHA256 of "{t}.{rawBody}".
     */
    private function signatureValid(Request $request, string $secret): bool
    {
        $header = $request->header('Paymongo-Signature');
        if (! $header) {
            return false;
        }

        $parts = collect(explode(',', $header))
            ->mapWithKeys(function ($part) {
                [$k, $v] = array_pad(explode('=', $part, 2), 2, null);

                return [$k => $v];
            });

        $timestamp = $parts['t'] ?? null;
        $signature = $parts['te'] ?? $parts['li'] ?? null; // te = test mode, li = live
        if (! $timestamp || ! $signature) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $secret);

        return hash_equals($expected, $signature);
    }
}

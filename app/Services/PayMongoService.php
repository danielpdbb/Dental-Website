<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Thin wrapper around the PayMongo Checkout Sessions API.
 * Auth uses the secret key as the HTTP Basic username (blank password).
 */
class PayMongoService
{
    private string $base = 'https://api.paymongo.com/v1';

    public function __construct(private ?string $secret = null)
    {
        $this->secret = $secret ?? config('services.paymongo.secret_key');
    }

    public function isConfigured(): bool
    {
        return ! empty($this->secret);
    }

    /**
     * True when using TEST keys — in test mode no real money can ever move,
     * which is the safety guarantee for scanning the QR during development/demos.
     */
    public function isTestMode(): bool
    {
        return str_starts_with((string) $this->secret, 'sk_test_');
    }

    private function client(): PendingRequest
    {
        return Http::withBasicAuth($this->secret, '')->acceptJson()->asJson();
    }

    /**
     * Create a hosted checkout session. Returns the PayMongo "data" object
     * (id + attributes.checkout_url).
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function createCheckoutSession(array $attributes): array
    {
        return $this->client()
            ->post("{$this->base}/checkout_sessions", ['data' => ['attributes' => $attributes]])
            ->throw()
            ->json('data');
    }

    /**
     * Retrieve a checkout session to confirm whether it was paid.
     *
     * @return array<string, mixed>
     */
    public function retrieveCheckoutSession(string $id): array
    {
        return $this->client()
            ->get("{$this->base}/checkout_sessions/{$id}")
            ->throw()
            ->json('data');
    }

    /**
     * The first successful payment on a checkout-session payload, or null.
     *
     * @param  array<string, mixed>  $session
     * @return array<string, mixed>|null
     */
    public function paidPayment(array $session): ?array
    {
        return collect($session['attributes']['payments'] ?? [])
            ->first(fn ($p) => ($p['attributes']['status'] ?? null) === 'paid');
    }

    /*
    |--------------------------------------------------------------------------
    | Dynamic QR Ph (scan-to-pay)
    |--------------------------------------------------------------------------
    | Creates a PaymentIntent → a qrph PaymentMethod → attaches them, which
    | returns a QR the patient scans (e.g. with GCash). Confirmed by polling the
    | intent (or via webhook). Note: QR Ph must be enabled on the PayMongo account.
    */

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>  the attached PaymentIntent "data" object
     */
    public function createQrPhPayment(int $amountCentavos, string $description, array $metadata = []): array
    {
        $attributes = [
            'amount' => $amountCentavos,
            'payment_method_allowed' => ['qrph'],
            'currency' => 'PHP',
            'capture_type' => 'automatic',
            'description' => $description,
        ];

        // PayMongo metadata must be a flat map of STRING values.
        if (! empty($metadata)) {
            $attributes['metadata'] = array_map(fn ($v) => (string) $v, $metadata);
        }

        $intent = $this->client()
            ->post("{$this->base}/payment_intents", ['data' => ['attributes' => $attributes]])
            ->throw()
            ->json('data');

        $method = $this->client()
            ->post("{$this->base}/payment_methods", ['data' => ['attributes' => ['type' => 'qrph']]])
            ->throw()
            ->json('data');

        return $this->client()
            ->post("{$this->base}/payment_intents/{$intent['id']}/attach", ['data' => ['attributes' => [
                'payment_method' => $method['id'],
            ]]])
            ->throw()
            ->json('data');
    }

    /**
     * @return array<string, mixed>
     */
    public function retrievePaymentIntent(string $id): array
    {
        return $this->client()
            ->get("{$this->base}/payment_intents/{$id}")
            ->throw()
            ->json('data');
    }

    /** The scannable QR image URL inside an attached PaymentIntent, if any. */
    public function qrImageUrl(array $intent): ?string
    {
        return $intent['attributes']['next_action']['code']['image_url']
            ?? $intent['attributes']['next_action']['redirect']['url']
            ?? null;
    }

    /**
     * TEST-mode simulation URL. In test mode QR Ph generates a real QR — do NOT
     * scan it (that processes a real transaction). Open this URL instead to
     * simulate paying it. (Null in live mode.)
     */
    public function qrTestUrl(array $intent): ?string
    {
        return $intent['attributes']['next_action']['code']['test_url'] ?? null;
    }

    /** PaymentIntent status: 'awaiting_next_action' → 'succeeded' once paid. */
    public function intentStatus(array $intent): ?string
    {
        return $intent['attributes']['status'] ?? null;
    }
}

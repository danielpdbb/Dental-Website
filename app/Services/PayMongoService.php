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
}

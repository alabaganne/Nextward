<?php

namespace App\Billing\Gateways;

use App\Billing\Gateway;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

// Lemon Squeezy: international cards and PayPal in USD, handles sales tax. https://docs.lemonsqueezy.com
class LemonSqueezyGateway implements Gateway
{
    public function configured(): bool
    {
        return filled(config('billing.gateways.lemonsqueezy.api_key')) && filled(config('billing.gateways.lemonsqueezy.store_id'))
            && filled(config('billing.gateways.lemonsqueezy.signing_secret'));
    }

    public function checkout(Payment $payment): string
    {
        $variant = config("billing.packs.{$payment->pack}.lemonsqueezy_variant");
        $response = Http::withToken(config('billing.gateways.lemonsqueezy.api_key'))
            ->withHeaders(['Accept' => 'application/vnd.api+json', 'Content-Type' => 'application/vnd.api+json'])
            ->timeout(20)
            ->post('https://api.lemonsqueezy.com/v1/checkouts', ['data' => [
                'type' => 'checkouts',
                'attributes' => [
                    'custom_price' => $payment->amount,
                    'product_options' => ['redirect_url' => route('billing.index')],
                    'checkout_data' => ['email' => $payment->user->email, 'name' => $payment->user->name, 'custom' => ['payment_id' => (string) $payment->id]],
                    'test_mode' => (bool) config('billing.gateways.lemonsqueezy.test_mode'),
                ],
                'relationships' => [
                    'store' => ['data' => ['type' => 'stores', 'id' => (string) config('billing.gateways.lemonsqueezy.store_id')]],
                    'variant' => ['data' => ['type' => 'variants', 'id' => (string) $variant]],
                ],
            ]])->throw();

        return $response->json('data.attributes.url');
    }

    // Only signed order_created webhooks for paid orders count.
    public function confirm(Request $request): ?Payment
    {
        $signature = hash_hmac('sha256', $request->getContent(), (string) config('billing.gateways.lemonsqueezy.signing_secret'));
        if (! hash_equals($signature, (string) $request->header('X-Signature')) || $request->input('meta.event_name') !== 'order_created') {
            return null;
        }
        $payment = Payment::where('gateway', 'lemonsqueezy')->whereKey((int) $request->input('meta.custom_data.payment_id'))->first();
        $order = $request->input('data.attributes', []);
        if (! $payment || ($order['status'] ?? null) !== 'paid' || strtoupper((string) ($order['currency'] ?? '')) !== $payment->currency
            || (int) ($order['total'] ?? 0) < $payment->amount) {
            return null;
        }
        $payment->update(['provider_ref' => (string) $request->input('data.id'), 'payload' => ['order' => $order['identifier'] ?? null, 'test_mode' => $order['test_mode'] ?? null]]);

        return $payment;
    }
}

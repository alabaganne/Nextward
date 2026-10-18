<?php

namespace App\Billing\Gateways;

use App\Billing\Gateway;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

// Konnect (Tunisia): cards, e-DINAR and wallet, in millimes. https://docs.konnect.network
class KonnectGateway implements Gateway
{
    public function configured(): bool
    {
        return filled(config('billing.gateways.konnect.api_key')) && filled(config('billing.gateways.konnect.wallet_id'));
    }

    public function checkout(Payment $payment): string
    {
        $user = $payment->user;
        $response = $this->http()->post('payments/init-payment', [
            'receiverWalletId' => config('billing.gateways.konnect.wallet_id'),
            'token' => 'TND',
            'amount' => $payment->amount,
            'type' => 'immediate',
            'description' => "Nextward: {$payment->credits} credits",
            'lifespan' => 30,
            'checkoutForm' => false,
            'addPaymentFeesToAmount' => false,
            'firstName' => Str::before((string) $user->name, ' '),
            'lastName' => Str::after((string) $user->name, ' '),
            'email' => $user->email,
            'orderId' => (string) $payment->id,
            'webhook' => route('billing.webhook', 'konnect'),
        ])->throw()->json();
        $payment->update(['provider_ref' => $response['paymentRef']]);

        return $response['payUrl'];
    }

    // The webhook only carries payment_ref, so the payment is always read back from Konnect.
    public function confirm(Request $request): ?Payment
    {
        $payment = Payment::where('gateway', 'konnect')->where('provider_ref', (string) $request->query('payment_ref'))->first();
        if (! $payment) { return null; }
        $details = $this->http()->get('payments/'.$payment->provider_ref)->throw()->json('payment', []);
        $paid = ($details['status'] ?? null) === 'completed' && ($details['token'] ?? 'TND') === 'TND'
            && (int) ($details['reachedAmount'] ?? $details['amount'] ?? 0) >= $payment->amount;

        return $paid ? $payment : null;
    }

    private function http()
    {
        return Http::baseUrl(rtrim(config('billing.gateways.konnect.base_url'), '/').'/')
            ->withHeaders(['x-api-key' => config('billing.gateways.konnect.api_key')])->acceptJson()->timeout(20);
    }
}

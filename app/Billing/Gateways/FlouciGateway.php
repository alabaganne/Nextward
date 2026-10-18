<?php

namespace App\Billing\Gateways;

use App\Billing\Gateway;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

// Flouci (Tunisia), API v2: wallet and cards, in millimes. https://docs.flouci.com
class FlouciGateway implements Gateway
{
    public function configured(): bool
    {
        return filled(config('billing.gateways.flouci.public_key')) && filled(config('billing.gateways.flouci.private_key'));
    }

    public function checkout(Payment $payment): string
    {
        $return = fn (string $result) => route('billing.return', ['gateway' => 'flouci', 'payment' => $payment->id, 'result' => $result]);
        $response = $this->http()->post('generate_payment', [
            'amount' => (string) $payment->amount,
            'accept_card' => true,
            'session_timeout_secs' => 1200,
            'success_link' => $return('success'),
            'fail_link' => $return('fail'),
            'developer_tracking_id' => (string) $payment->id,
            'webhook' => route('billing.webhook', 'flouci'),
        ])->throw()->json('result', []);
        $payment->update(['provider_ref' => $response['payment_id']]);

        return $response['link'];
    }

    // Works for the buyer coming back and for the webhook: both are checked again with Flouci.
    public function confirm(Request $request): ?Payment
    {
        $payment = $request->filled('payment')
            ? Payment::where('gateway', 'flouci')->whereKey((int) $request->input('payment'))->first()
            : Payment::where('gateway', 'flouci')->where('provider_ref', (string) $request->input('payment_id'))->first();
        if (! $payment?->provider_ref) { return null; }
        $details = $this->http()->get('verify_payment/'.$payment->provider_ref)->throw()->json();
        $paid = ($details['success'] ?? false) === true && ($details['result']['status'] ?? null) === 'SUCCESS'
            && (int) ($details['result']['amount'] ?? 0) >= $payment->amount;

        return $paid ? $payment : null;
    }

    private function http()
    {
        return Http::baseUrl(rtrim(config('billing.gateways.flouci.base_url'), '/').'/')
            ->withToken(config('billing.gateways.flouci.public_key').':'.config('billing.gateways.flouci.private_key'))
            ->acceptJson()->timeout(20);
    }
}

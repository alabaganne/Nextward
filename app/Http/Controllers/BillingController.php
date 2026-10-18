<?php

namespace App\Http\Controllers;

use App\Billing\Credits;
use App\Billing\Gateways;
use App\Models\CreditTransaction;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;

// Buying credit packs. Every route here is closed while billing is off.
class BillingController extends Controller
{
    public function index(Request $request, Credits $credits, Gateways $gateways)
    {
        abort_unless(config('billing.enabled'), 404);
        $user = $request->user();
        abort_unless($user->isCandidate(), 403);

        return Inertia::render('Billing/Index', [
            'balance' => $credits->balance($user),
            'drafts_per_credit' => $credits->draftsPerCredit(),
            'gateways' => $gateways->available(),
            'packs' => collect(config('billing.packs'))->map(fn ($pack, $key) => [
                'key' => $key,
                'credits' => $pack['credits'],
                'prices' => array_filter(['TND' => $pack['price_tnd'] ? (int) $pack['price_tnd'] : null, 'USD' => $pack['price_usd'] ? (int) $pack['price_usd'] : null]),
            ])->filter(fn ($pack) => $pack['prices'])->values(),
            'history' => CreditTransaction::where('user_id', $user->id)->latest('id')->take(30)->get(['amount', 'reason', 'note', 'created_at']),
        ]);
    }

    public function checkout(Request $request, Gateways $gateways)
    {
        abort_unless(config('billing.enabled'), 404);
        $user = $request->user();
        abort_unless($user->isCandidate(), 403);
        $data = $request->validate(['pack' => 'required|in:'.implode(',', array_keys(config('billing.packs'))), 'gateway' => 'required|in:'.implode(',', $gateways->available())]);
        $pack = config('billing.packs.'.$data['pack']);
        $currency = config("billing.gateways.{$data['gateway']}.currency");
        $amount = (int) ($currency === 'USD' ? $pack['price_usd'] : $pack['price_tnd']);
        abort_if($amount < 1 || ($data['gateway'] === 'lemonsqueezy' && ! $pack['lemonsqueezy_variant']), 422, 'This pack is not on sale with this payment method yet.');

        $payment = Payment::create(['user_id' => $user->id, 'gateway' => $data['gateway'], 'pack' => $data['pack'], 'credits' => $pack['credits'], 'amount' => $amount, 'currency' => $currency]);
        try {
            return Inertia::location($gateways->get($data['gateway'])->checkout($payment));
        } catch (\Throwable $e) {
            Log::warning('Checkout failed.', ['payment_id' => $payment->id, 'error' => Str::limit($e->getMessage(), 300)]);
            $payment->update(['status' => 'failed']);
            return back()->with('toast', ['type' => 'destroy', 'message' => 'We could not open the payment page. Please try again in a minute.']);
        }
    }

    // Where a buyer lands after paying; the payment is checked with the gateway again.
    public function return(Request $request, string $gateway, Gateways $gateways, Credits $credits)
    {
        abort_unless(config('billing.enabled'), 404);
        $paid = $request->input('result') !== 'fail' && ($payment = $gateways->get($gateway)?->confirm($request)) && $credits->addPurchase($payment);

        return redirect()->route('billing.index')->with('toast', $paid || ($payment ?? null)?->fresh()->status === 'paid'
            ? ['type' => 'store', 'message' => 'Payment received. Your credits are ready.']
            : ['type' => 'update', 'message' => 'We have not received this payment yet. If you paid, your credits appear here in a few minutes.']);
    }
}

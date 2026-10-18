<?php

namespace App\Http\Controllers;

use App\Billing\Credits;
use App\Billing\Gateways;
use Illuminate\Http\Request;

// Calls from the payment gateways. Each one is checked before credits are added, and repeats are safe.
class BillingWebhookController extends Controller
{
    public function __invoke(Request $request, string $gateway, Gateways $gateways, Credits $credits)
    {
        abort_unless(config('billing.enabled') && ($driver = $gateways->get($gateway)), 404);
        if ($payment = $driver->confirm($request)) {
            $credits->addPurchase($payment);
        }
        // Konnect sends the buyer's browser to the webhook too, so send people back to their credits.
        if ($request->isMethod('get') && $request->acceptsHtml() && ! $request->expectsJson()) {
            return redirect()->route('billing.index');
        }

        return response()->json(['ok' => true]);
    }
}

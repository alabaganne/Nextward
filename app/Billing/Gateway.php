<?php

namespace App\Billing;

use App\Models\Payment;
use Illuminate\Http\Request;

interface Gateway
{
    public function configured(): bool;

    // Starts a hosted checkout for the payment and returns the URL to send the buyer to.
    public function checkout(Payment $payment): string;

    // Reads a return or webhook call, checks it with the gateway, and returns the paid payment or null.
    public function confirm(Request $request): ?Payment;
}

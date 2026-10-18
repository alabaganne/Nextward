<?php

namespace App\Billing;

use App\Billing\Gateways\FlouciGateway;
use App\Billing\Gateways\KonnectGateway;
use App\Billing\Gateways\LemonSqueezyGateway;

class Gateways
{
    public const ALL = ['konnect' => KonnectGateway::class, 'flouci' => FlouciGateway::class, 'lemonsqueezy' => LemonSqueezyGateway::class];

    public function get(string $name): ?Gateway
    {
        return isset(self::ALL[$name]) ? app(self::ALL[$name]) : null;
    }

    // Gateways with keys set, ready to take payments.
    public function available(): array
    {
        return array_values(array_filter(array_keys(self::ALL), fn ($name) => $this->get($name)->configured()));
    }
}

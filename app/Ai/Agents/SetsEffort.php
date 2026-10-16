<?php

namespace App\Ai\Agents;

use Laravel\Ai\Enums\Lab;

trait SetsEffort
{
    private function effort(Lab|string $provider, ?string $effort): array
    {
        if (! $effort) { return []; }
        return match ($provider instanceof Lab ? $provider->value : $provider) {
            'openai' => ['reasoning' => ['effort' => $effort]],
            'anthropic' => ['output_config' => ['effort' => $effort]],
            default => [],
        };
    }
}

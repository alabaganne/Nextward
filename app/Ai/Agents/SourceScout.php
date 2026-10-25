<?php

namespace App\Ai\Agents;

use App\Ai\Tools\SuggestSource;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasProviderOptions;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Laravel\Ai\Providers\Tools\WebSearch;

// Searches the web for new job boards and hands each find to suggest_source.
#[MaxSteps(30)]
#[MaxTokens(16000)]
#[Timeout(300)]
class SourceScout implements Agent, HasProviderOptions, HasTools
{
    use Promptable, SetsEffort;

    public function __construct(private SuggestSource $suggest) {}

    public function instructions(): string
    {
        return 'You find new places where Nextward, an IT job board, can read software, web, mobile and data and AI jobs abroad, mostly in Europe and the USA.';
    }

    public function tools(): iterable
    {
        return [(new WebSearch)->max(15), $this->suggest];
    }

    public function providerOptions(Lab|string $provider): array
    {
        return $this->effort($provider, config('importer.ai.discovery_effort'));
    }
}

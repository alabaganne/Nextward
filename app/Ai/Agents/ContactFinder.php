<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasProviderOptions;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Laravel\Ai\Providers\Tools\WebSearch;

// Searches public pages for one person at a company the owner could reach out to about a role.
#[Strict]
#[MaxTokens(4000)]
#[Timeout(180)]
class ContactFinder implements Agent, HasProviderOptions, HasStructuredOutput, HasTools
{
    use Promptable, SetsEffort;

    public function instructions(): string
    {
        return <<<'PROMPT'
You help a software engineer find one person to reach out to about a job at a company. Use web search on public pages: the company's own team, about or blog pages, engineering blog posts, conference talks, GitHub and public profiles that show up in search results. Pick the engineering manager or team lead closest to the role, else a senior engineer on that team, else a technical recruiter. The person must work at that company now, as far as the pages show. Never invent a person, a title or a link; only use what you saw.

- found: false when you are not sure about anyone.
- name and title: as the page shows them.
- profile_url: a public profile link you saw for this person (LinkedIn, GitHub, personal site), or empty.
- source_url: the page that shows they work there.
- why: one short sentence on why this person fits the role.
PROMPT;
    }

    public function tools(): iterable
    {
        return [(new WebSearch)->max(5)];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'found' => $schema->boolean()->required(),
            'name' => $schema->string()->required(),
            'title' => $schema->string()->required(),
            'profile_url' => $schema->string()->required(),
            'source_url' => $schema->string()->required(),
            'why' => $schema->string()->required(),
        ];
    }

    public function providerOptions(Lab|string $provider): array
    {
        return $this->effort($provider, config('importer.ai.effort'));
    }
}

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

// Searches the web for a hiring company's own website and public facts about it.
#[Strict]
#[MaxTokens(6000)]
#[Timeout(180)]
class CompanyResearcher implements Agent, HasProviderOptions, HasStructuredOutput, HasTools
{
    use Promptable, SetsEffort;

    public function instructions(): string
    {
        return <<<'PROMPT'
You check hiring companies for Nextward, an IT job board. Use web search to find the company's own official website and public facts about it. Make sure it is the same company that posts the jobs you are given: match the name, the job board links and what it does. Answer only from what you find; leave a text empty and a number 0 when you are not sure. Never guess.

- found: true only when you are sure which website is the company's own.
- website: the home page of the company's own site, starting with https. Not LinkedIn, a social network, a job board, an ATS page, a directory or a news site. Empty when not found.
- about: two or three plain sentences on what the company does, based on its own site. No praise, no marketing words, no numbers you did not see.
- size: the number of employees as one of the listed ranges, or empty.
- founded_year: the year it was founded, or 0.
- headquarters: the city and country of its head office, like "Berlin, Germany", or empty.
- country_code: the two-letter code of that country, or empty.
- stage: "startup" for a young company still growing fast (often venture-funded), "established" for a mature company, or empty.
- linkedin_url: its company page on linkedin.com/company/, only when you saw it in the results, or empty.
- careers_url: the page where it lists its jobs, or empty.
- sources: the pages you used, at most five.
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
            'website' => $schema->string()->required(),
            'about' => $schema->string()->required(),
            'size' => $schema->string()->enum(['', '1-10', '11-50', '51-200', '201-500', '501-1000', '1001-5000', '5001+'])->required(),
            'founded_year' => $schema->integer()->required(),
            'headquarters' => $schema->string()->required(),
            'country_code' => $schema->string()->required(),
            'stage' => $schema->string()->enum(['', 'startup', 'established'])->required(),
            'linkedin_url' => $schema->string()->required(),
            'careers_url' => $schema->string()->required(),
            'sources' => $schema->array()->items($schema->string())->required(),
        ];
    }

    public function providerOptions(Lab|string $provider): array
    {
        return $this->effort($provider, config('importer.ai.effort'));
    }
}

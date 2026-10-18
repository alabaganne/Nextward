<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasProviderOptions;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

// Translates one job listing into English or French and says which language the original was in.
#[Strict]
#[MaxTokens(24000)]
#[Timeout(180)]
class ListingTranslator implements Agent, HasProviderOptions, HasStructuredOutput
{
    use Promptable, SetsEffort;

    public function __construct(private string $target) {}

    public function instructions(): string
    {
        $language = $this->target === 'fr' ? 'French' : 'English';
        return <<<PROMPT
You translate job listings for Nextward, a job board, into {$language}.

- Translate everything and keep the exact meaning. Do not add, drop, shorten or sum up anything.
- Keep the plain-text layout: the same paragraphs, blank lines and "• " bullets. No markdown, no HTML.
- Keep names of companies, products, tools and programming languages, job codes, emails, links, numbers, dates and money amounts as they are.
- Use the wording people expect in {$language} job ads. Keep the title short, the way {$language} job boards write it.
- source_language: the two-letter ISO 639-1 code of the original listing, like "en", "fr", "ar" or "de".
- If the listing is already in {$language}, copy it as it is.
PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'source_language' => $schema->string()->required(),
            'title' => $schema->string()->required(),
            'description' => $schema->string()->required(),
        ];
    }

    public function providerOptions(Lab|string $provider): array
    {
        return $this->effort($provider, config('importer.ai.effort'));
    }
}

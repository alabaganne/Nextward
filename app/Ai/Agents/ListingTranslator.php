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

// Translates a batch of job listings into English and/or French and says which language each original was in.
#[Strict]
#[MaxTokens(32000)]
#[Timeout(300)]
class ListingTranslator implements Agent, HasProviderOptions, HasStructuredOutput
{
    use Promptable, SetsEffort;

    public function instructions(): string
    {
        return <<<'PROMPT'
You translate job listings for Nextward, a job board. You get several listings, each starting with "Listing <id>" and the languages to translate it into ("en" is English, "fr" is French). Answer once for every listing, with the same id, and give one translation for each language asked.

- Translate everything and keep the exact meaning. Do not add, drop, shorten or sum up anything.
- Keep the plain-text layout: the same paragraphs, blank lines and "• " bullets. No markdown, no HTML.
- Keep names of companies, products, tools and programming languages, job codes, emails, links, numbers, dates and money amounts as they are.
- Use the wording people expect in job ads in that language. Keep the title short, the way job boards in that language write it.
- source_language: the two-letter ISO 639-1 code of the original listing, like "en", "fr", "ar" or "de".
- If the listing is already in a language asked for, copy it as it is.
PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'listings' => $schema->array()->items($schema->object([
                'id' => $schema->integer()->required(),
                'source_language' => $schema->string()->required(),
                'translations' => $schema->array()->items($schema->object([
                    'locale' => $schema->string()->enum(['en', 'fr'])->required(),
                    'title' => $schema->string()->required(),
                    'description' => $schema->string()->required(),
                ]))->required(),
            ]))->required(),
        ];
    }

    public function providerOptions(Lab|string $provider): array
    {
        return $this->effort($provider, config('importer.ai.effort'));
    }
}

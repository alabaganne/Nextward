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

// Reads a resume PDF into the fields, skills and levels of the roles imports should look for.
#[Strict]
#[MaxTokens(4000)]
#[Timeout(120)]
class SearchProfileReader implements Agent, HasProviderOptions, HasStructuredOutput
{
    use Promptable, SetsEffort;

    public function __construct(private array $fields, private array $skills) {}

    public function instructions(): string
    {
        $fields = implode(', ', $this->fields);
        $skills = implode(', ', $this->skills);
        return <<<PROMPT
You read a person's resume (attached PDF) for Nextward, an IT job board, and say which listings it should find for them.

- fields: the fields of the roles this person fits or asks for, only from this list: {$fields}.
- skills: up to 30 skills the person has, only names from this list, written exactly as listed: {$skills}.
- kinds: "job", and "internship" too when they are a student or graduated in the last two years.
- seniority: every level they could apply to, from entry, graduate, mid, senior, lead, executive: their own level from their years of work, the one below and the one above, plus entry when kinds has internship.
- country_code: the two-letter ISO code of the country they live in, or "".
- summary: one short line on what they look for.
Add what the person says they also want, when given.
PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        $list = fn () => $schema->array()->items($schema->string())->required();
        return [
            'fields' => $list(), 'skills' => $list(), 'kinds' => $list(), 'seniority' => $list(),
            'country_code' => $schema->string()->required(), 'summary' => $schema->string()->required(),
        ];
    }

    public function providerOptions(Lab|string $provider): array
    {
        return $this->effort($provider, config('importer.ai.effort'));
    }
}

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

// Reads a resume PDF and returns profile fields for the candidate to review before saving.
#[Strict]
#[MaxTokens(12000)]
#[Timeout(120)]
class ResumeReader implements Agent, HasProviderOptions, HasStructuredOutput
{
    use Promptable, SetsEffort;

    public function instructions(): string
    {
        return <<<'PROMPT'
You read a candidate's resume (attached PDF) for Nextward, a job board, and fill in their profile.

- Copy only what the resume says. Never invent a job, date, skill, degree, number or link. Use an empty string, 0 or an empty list when the resume does not say.
- Keep the resume's language for free text.
- headline: their role or target in a few words, like "Full-stack developer" or "Marketing student".
- about: two to four short sentences that sum up the person, in the first person, using only facts from the resume.
- experience_years: whole years of paid work, counted from the dates. 0 for students with no jobs.
- location: their city and country as written. country_code: the two-letter ISO code of that country, or "".
- languages: spoken languages, in English, like "French" or "Arabic".
- skills: up to 25 tools and skills, as short names.
- experience, education, projects: newest first. Dates as "YYYY-MM" or "YYYY"; end is "" when current is true. summary: one to three short lines of what they did.
- website and linkedin_profile_url: full https links, or "".
- language: the two-letter code of the language the resume is written in, like "en" or "fr".
PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        $text = fn () => $schema->string()->required();
        $list = fn (array $properties) => $schema->array()->items($schema->object($properties))->required();
        return [
            'language' => $text(),
            'headline' => $text(),
            'about' => $text(),
            'experience_years' => $schema->integer()->required(),
            'location' => $text(),
            'country_code' => $text(),
            'phone_number' => $text(),
            'website' => $text(),
            'linkedin_profile_url' => $text(),
            'languages' => $schema->array()->items($schema->string())->required(),
            'skills' => $schema->array()->items($schema->string())->required(),
            'experience' => $list([
                'title' => $text(), 'company' => $text(), 'location' => $text(), 'start' => $text(), 'end' => $text(),
                'current' => $schema->boolean()->required(), 'summary' => $text(),
            ]),
            'education' => $list(['school' => $text(), 'degree' => $text(), 'field' => $text(), 'start' => $text(), 'end' => $text()]),
            'projects' => $list(['name' => $text(), 'summary' => $text(), 'url' => $text()]),
        ];
    }

    public function providerOptions(Lab|string $provider): array
    {
        return $this->effort($provider, config('importer.ai.effort'));
    }
}

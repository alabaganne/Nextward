<?php

namespace App\Ai\Agents;

use App\Support\LanguageGuess;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasProviderOptions;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

// Translates a candidate's resume (a PDF they uploaded, or one made on Nextward) into another language, fact for fact.
#[Strict]
#[MaxTokens(16000)]
#[Timeout(180)]
class ResumeTranslator implements Agent, HasProviderOptions, HasStructuredOutput
{
    use CvSchema, Promptable, SetsEffort;

    public function __construct(private string $locale) {}

    public function instructions(): string
    {
        $language = LanguageGuess::name($this->locale) ?? 'English';
        return <<<PROMPT
You translate a candidate's resume into {$language} for Nextward, a job board for IT people. You get the resume as a PDF or as JSON.

- Translate every text into {$language}: headline, summary, highlights, job titles, degrees, fields of study, locations, project summaries and languages.
- Keep every fact exactly as it is: the same jobs, companies, schools, dates, numbers, skills and links, in the same order. Never add, drop, merge, shorten or improve anything.
- Keep names of people, companies, schools, products, tools and technologies as they are. Skills that are tool or technology names stay as written.
- Dates as "YYYY-MM" or "YYYY", as in the source. For a job they still hold, current is true and end is "".
- From a PDF, read every part into the fields: name; contact (email, phone, location and links, one per line); headline (their role under the name); summary (the profile paragraph); highlights (the bullet lines of each job); and so on. Leave a field empty or a list empty when the resume has nothing for it.
- source_language: the two-letter code of the language the resume is written in, like "en" or "fr".
PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'cv' => $this->cvSchema($schema, withContact: true),
            'source_language' => $schema->string()->required(),
        ];
    }

    public function providerOptions(Lab|string $provider): array
    {
        return $this->effort($provider, config('importer.ai.effort'));
    }
}

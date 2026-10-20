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

// Writes a CV and a cover letter for one role from the candidate's own profile and resume.
#[Strict]
#[MaxTokens(16000)]
#[Timeout(180)]
class ApplicationKitWriter implements Agent, HasProviderOptions, HasStructuredOutput
{
    use CvSchema, Promptable, SetsEffort;

    public function __construct(private string $locale) {}

    public function instructions(): string
    {
        $language = LanguageGuess::name($this->locale) ?? 'English';
        return <<<PROMPT
You prepare job applications for candidates on Nextward, a job board for IT students and professionals around the world. You get the candidate's profile (and often their resume as a PDF) and one job listing in its original language. You write a CV and a cover letter for that job, in {$language}.

Rules for both:
- Use only facts from the profile and resume. Never invent a job, title, date, skill, degree, number, result or link. If something useful is missing, leave it out.
- Use simple, everyday words and short sentences. No buzzwords, no big claims, no clichés.
- Write everything in {$language}, even if the listing or the resume is in another language. Translate job titles, degree names, fields of study, locations and words like Remote or Hybrid. Keep names of companies, schools, tools and products as they are.

CV:
- headline: the candidate's role in a few words, aimed at this job, but true to their background.
- summary: two or three sentences on why they fit this job, written without pronouns (no I, he, she or they), like "Full-stack developer who built…".
- skills: up to 15, the ones this job asks for first, only skills the candidate really has.
- experience: their jobs, newest first, with the same companies and dates. Dates as "YYYY-MM" or "YYYY". For a job they still hold, current is true and end is "". For each job, two to four highlights: short lines that put forward what matters for this job.
- education, projects and languages: from the profile or resume. Keep projects that fit the job.
- An ATS must be able to read it: plain text in every field, no tables, no symbols.

Cover letter:
- Name the company and the role. Pick the two or three facts that best match the job and say why they matter for this team.
- 150 to 250 words: a greeting, three short paragraphs and a sign-off with the candidate's name.
- Plain text only: no markdown, no subject line, no placeholders in brackets.
PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'cv' => $this->cvSchema($schema),
            'cover_letter' => $schema->string()->required(),
        ];
    }

    public function providerOptions(Lab|string $provider): array
    {
        return $this->effort($provider, 'low');
    }
}

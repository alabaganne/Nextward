<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasProviderOptions;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

// Drafts a cover letter for one role from the candidate's own profile and resume.
#[MaxTokens(6000)]
#[Timeout(120)]
class CoverLetterWriter implements Agent, HasProviderOptions
{
    use Promptable, SetsEffort;

    public function instructions(): string
    {
        return <<<'PROMPT'
You write cover letters for candidates on Nextward, a job board for people in Tunisia.

- Use only facts from the candidate profile and resume. Never invent a job, skill, degree, number or link. If something useful is missing, leave it out.
- Pick the two or three facts that best match the role and say why they matter for this team. Name the company and the role.
- Use simple, everyday words and short sentences. No buzzwords, no big claims, no clichés like "I am writing to express my interest".
- Write in the language of the job listing (French listing, French letter). Use English if unsure.
- Keep it between 150 and 250 words: a greeting, three short paragraphs, and a sign-off with the candidate's name.
- Plain text only: no markdown, no subject line, no placeholders in brackets.
PROMPT;
    }

    public function providerOptions(Lab|string $provider): array
    {
        return $this->effort($provider, 'low');
    }
}

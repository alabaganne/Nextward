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

// Reads the subject, sender and first lines of a few emails and says which ones are about the owner's job applications.
#[Strict]
#[MaxTokens(4000)]
#[Timeout(120)]
class ApplicationEmailReader implements Agent, HasProviderOptions, HasStructuredOutput
{
    use Promptable, SetsEffort;

    public function instructions(): string
    {
        return <<<'PROMPT'
You read emails from a software engineer's inbox. Each one starts with "Email <id>" and has a subject, a sender and its first lines. Answer once for every email, with the same id.

- kind: "applied" when it confirms an application the person sent, "rejected" when the company turns the application down, "interviewing" when it invites them to an interview or a test, "other" for anything else (job alerts, newsletters, recruiter outreach, offers to apply).
- company: the hiring company, as the email shows it. Not the job board or the email service (Greenhouse, Lever, Ashby, Workable, LinkedIn, Indeed).
- role: the job title, as the email shows it.
Leave company or role empty when the email does not show them. Never guess.
PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'emails' => $schema->array()->items($schema->object([
                'id' => $schema->string()->required(),
                'kind' => $schema->string()->enum(['applied', 'rejected', 'interviewing', 'other'])->required(),
                'company' => $schema->string()->required(),
                'role' => $schema->string()->required(),
            ]))->required(),
        ];
    }

    public function providerOptions(Lab|string $provider): array
    {
        return $this->effort($provider, config('importer.ai.effort'));
    }
}

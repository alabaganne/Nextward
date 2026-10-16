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

// Reads one job listing and answers with its field, kind, seniority and Tunisia eligibility.
#[Strict]
#[MaxTokens(4000)]
#[Timeout(120)]
class ListingReviewer implements Agent, HasProviderOptions, HasStructuredOutput
{
    use Promptable, SetsEffort;

    public function __construct(private array $fields) {}

    public function instructions(): string
    {
        return <<<'PROMPT'
You sort job listings for Nextward, a job board for people in Tunisia. Answer only from what the listing says; do not guess.

- is_software_role: true when the main work is building software (software engineering, web or mobile development), including internships. Sales, support, training, design-only, data-entry and IT-support roles are false.
- field: the closest field from the list. Use "Other" when none fits.
- kind: "internship" for internships, PFE, summer internships, apprenticeships, co-op and working-student roles; "job" for everything else.
- seniority: from the title and the experience asked for; "unspecified" when the listing does not say.
- remote_from_tunisia: only for remote roles. "eligible" when the listing says hiring is open worldwide, in Africa, MENA, EMEA or Tunisia; "restricted" when the role itself limits candidates to other countries or regions (for example "Remote US" or "must live in Canada"), even if the company says it hires globally elsewhere; "unknown" otherwise, and always "unknown" for on-site or hybrid roles.
- relocation and visa_sponsorship: "yes" or "no" only when the listing says so, otherwise "unknown".
- evidence: a short quote from the listing behind your remote, relocation or visa answer, or an empty string.
PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        $choice = fn (array $values) => $schema->string()->enum($values)->required();
        return [
            'is_software_role' => $schema->boolean()->required(),
            'field' => $choice($this->fields),
            'kind' => $choice(['internship', 'job']),
            'seniority' => $choice(['entry', 'graduate', 'mid', 'senior', 'lead', 'executive', 'unspecified']),
            'remote_from_tunisia' => $choice(['eligible', 'restricted', 'unknown']),
            'relocation' => $choice(['yes', 'no', 'unknown']),
            'visa_sponsorship' => $choice(['yes', 'no', 'unknown']),
            'evidence' => $schema->string()->required(),
        ];
    }

    public function providerOptions(Lab|string $provider): array
    {
        return $this->effort($provider, config('importer.ai.effort'));
    }
}

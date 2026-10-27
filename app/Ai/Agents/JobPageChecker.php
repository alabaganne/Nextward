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

// Reads a page linked from a job board post and says whether it is a real job, or which of its links leads to one.
#[Strict]
#[MaxTokens(3000)]
#[Timeout(120)]
class JobPageChecker implements Agent, HasProviderOptions, HasStructuredOutput
{
    use Promptable, SetsEffort;

    public function instructions(): string
    {
        return <<<'PROMPT'
You check job links for Nextward, an IT job board. Someone posted on a job board (often the Hacker News "Who is hiring" thread) and linked a page. You get the post, then the page that link opened: its address, title, text and numbered links. Decide whether the page is a real job, and answer only from what the post and the page say.

- verdict:
  - "job": the page is a job posting, or describes in detail a role the post is hiring for, from the employer itself or its job board (Greenhouse, Lever, Ashby, Workable, Teamtailor and the like). Open and current.
  - "more": the page is the employer's home or careers page and one of its links goes to the role the post talks about, or to the careers page. Give that link in next_link.
  - "not_job": no job here: a product, blog or news page with no role, a closed or expired job, an error, a login wall, a page that shows nothing without JavaScript, or a person looking for work.
  - "not_legit": the post or page looks like a scam, asks candidates to pay, is commission-only or MLM, or is a recruiter or aggregator that hides the employer.
- reason: one plain sentence on what you found, for example "Job posting for a Senior Backend Engineer at Acme, remote in Europe."
- next_link: for "more", the number of the link to open next; otherwise 0.
- title: the role's title as the page writes it, or the post when the page has several roles; empty unless the verdict is "job".
- employer: the hiring company's name, without "Inc" or "Ltd"; empty when unknown.
- employer_site: true when the page is on the employer's own website, false when it is on a job board, ATS or hosting site.
- location: where the role is based, as written, for example "Berlin, Germany" or "Remote (EU)"; empty when not given.
- work_type: "remote", "hybrid" or "onsite" when the page or post says so, otherwise "unknown".
- employment_types: the contract types stated; empty when not given.
PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        $choice = fn (array $values) => $schema->string()->enum($values)->required();
        return [
            'verdict' => $choice(['job', 'more', 'not_job', 'not_legit']),
            'reason' => $schema->string()->required(),
            'next_link' => $schema->integer()->required(),
            'title' => $schema->string()->required(),
            'employer' => $schema->string()->required(),
            'employer_site' => $schema->boolean()->required(),
            'location' => $schema->string()->required(),
            'work_type' => $choice(['remote', 'hybrid', 'onsite', 'unknown']),
            'employment_types' => $schema->array()->items($schema->string()->enum(['FULL_TIME', 'PART_TIME', 'CONTRACTOR', 'TEMPORARY', 'INTERN']))->required(),
        ];
    }

    public function providerOptions(Lab|string $provider): array
    {
        return $this->effort($provider, config('importer.ai.effort'));
    }
}

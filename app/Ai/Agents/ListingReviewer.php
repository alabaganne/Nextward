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

// Reads a batch of job listings, imported or posted by employers, and answers for each one: quality, field, filters, skills and who can apply.
#[Strict]
#[MaxTokens(16000)]
#[Timeout(240)]
class ListingReviewer implements Agent, HasProviderOptions, HasStructuredOutput
{
    use Promptable, SetsEffort;

    public function __construct(private array $fields, private array $skills = []) {}

    public function instructions(): string
    {
        return <<<'PROMPT'
You sort job listings for Nextward, a job board for IT students and professionals around the world. Many of its users live in Tunisia and North Africa. Listings come from other job boards and from employers who post on Nextward; treat both the same way. Your answers become the labels and filters people search by: Field, Skills, Type (internship or job), Experience, Work mode, contract type, and "Open worldwide" (roles people in any country can apply to, Tunisia included).

You get several listings, each starting with "Listing <id>". Answer once for every listing, with the same id. Answer only from what each listing says; do not guess.

- in_scope: true when the main work is an IT role, including internships: software engineering, web or mobile development, data and AI, DevOps and cloud, cybersecurity, networks and systems, IT support, QA and testing, UI/UX design for digital products, embedded systems and business intelligence. Sales, marketing, recruiting, HR, finance, data-entry, mechanical, civil, electrical-power, administrative and other non-IT roles are false, even at a tech company.
- quality: "reject" for scams, roles that ask candidates to pay, commission-only or MLM roles, listings with no real details about the work, recruiter or aggregator spam that hides the employer, and text that says the role is closed. "weak" for a real role with thin details. "good" otherwise.
- quality_reason: a few words on why, or an empty string when quality is "good".
- field: the closest field from the list. Use "Other" when none fits.
- kind: "internship" for internships, PFE, summer internships, apprenticeships, co-op and working-student roles; "job" for everything else.
- seniority: from the title and the experience asked for; "unspecified" when the listing does not say.
- work_type: "remote", "hybrid" or "onsite" when the listing says so, otherwise "unknown".
- employment_types: the contract types the listing states; empty when it does not say.
- program_type: "pfe" for final-year (PFE) internships, "summer" for summer internships, "apprenticeship" for apprenticeships or work-study, "graduate" for graduate programmes, otherwise "none".
- skills: every language, framework, tool, platform, method or certification from the skills list that the role asks for or will clearly use, most important first, at most 15. Pick the most specific name (React Native for a mobile React role rather than React; Embedded C rather than C when the listing says embedded C), and include basics the listing names, such as HTML, CSS, Git, SQL or REST. Write each name exactly as in the list and never invent one; leave out anything the list does not have.
- applicant_scope: who can apply.
  - "anywhere": people in any country can apply. Either the role is remote and the listing says it is open worldwide or has no location limit, or the employer offers visa sponsorship or relocation without limiting nationalities. A short list of excluded countries (for example sanctioned countries) still counts as "anywhere"; list them in excluded_countries.
  - "listed": only people in some countries or regions can apply (for example "Remote, EMEA" or "Remote US or Canada"); list those countries in eligible_countries.
  - "local": the applicant must already be allowed to work in the job's country (for example "must be authorized to work in the US", "no visa sponsorship"), or the role is on site or hybrid with no visa or relocation help.
  - "unknown": the listing does not say.
- eligible_countries and excluded_countries: ISO 3166-1 alpha-2 codes the listing names. For a region, list its countries only when the listing names them; otherwise leave the list empty.
- tunisia: can someone who lives in Tunisia apply?
  - "yes": the role is based in Tunisia, the listing names Tunisia, the role is open to anyone in any country (applicant_scope "anywhere" with Tunisia not excluded), or it is open to a region that includes Tunisia (Africa, North Africa, the Maghreb, MENA, EMEA or francophone countries).
  - "no": Tunisia is excluded, or the role is limited to places that do not include it (for example "US only", "EU work permit required", or on site abroad with no visa or relocation help).
  - "unknown": the listing does not say.
- relocation and visa_sponsorship: "yes" or "no" only when the listing says so, otherwise "unknown".
- evidence: a short quote from the listing behind your applicant_scope, relocation or visa answer, or an empty string.
PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        $choice = fn (array $values) => $schema->string()->enum($values)->required();
        $countries = fn () => $schema->array()->items($schema->string()->pattern('^[A-Z]{2}$'))->required();
        return [
            'listings' => $schema->array()->items($schema->object([
                'id' => $schema->integer()->required(),
                'in_scope' => $schema->boolean()->required(),
                'quality' => $choice(['good', 'weak', 'reject']),
                'quality_reason' => $schema->string()->required(),
                'field' => $choice($this->fields),
                'kind' => $choice(['internship', 'job']),
                'seniority' => $choice(['entry', 'graduate', 'mid', 'senior', 'lead', 'executive', 'unspecified']),
                'work_type' => $choice(['remote', 'hybrid', 'onsite', 'unknown']),
                'employment_types' => $schema->array()->items($schema->string()->enum(['FULL_TIME', 'PART_TIME', 'CONTRACTOR', 'TEMPORARY', 'INTERN']))->required(),
                'program_type' => $choice(['pfe', 'summer', 'apprenticeship', 'graduate', 'none']),
                'skills' => $schema->array()->items($this->skills ? $schema->string()->enum($this->skills) : $schema->string())->required(),
                'applicant_scope' => $choice(['anywhere', 'listed', 'local', 'unknown']),
                'eligible_countries' => $countries(),
                'excluded_countries' => $countries(),
                'tunisia' => $choice(['yes', 'no', 'unknown']),
                'relocation' => $choice(['yes', 'no', 'unknown']),
                'visa_sponsorship' => $choice(['yes', 'no', 'unknown']),
                'evidence' => $schema->string()->required(),
            ]))->required(),
        ];
    }

    public function providerOptions(Lab|string $provider): array
    {
        return $this->effort($provider, config('importer.ai.effort'));
    }
}

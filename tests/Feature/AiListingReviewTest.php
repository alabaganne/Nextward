<?php

namespace Tests\Feature;

use App\Ai\OpportunityClassifier;
use App\Importing\OpportunityImporter;
use App\Models\Field;
use App\Models\Opportunity;
use App\Models\Skill;
use App\Models\User;
use Database\Seeders\SkillSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiListingReviewTest extends TestCase
{
    use RefreshDatabase;

    private object $ai;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Tests\Fixtures\DemoSeeder::class);
        $this->seed(SkillSeeder::class);
        Field::firstOrCreate(['name' => 'Mobile Development']);
        $this->ai = new class extends OpportunityClassifier {
            public array $calls = [];
            public array $answer = [];

            public function enabled(): bool { return true; }

            public function classifyMany(array $listings, array $fields, array $skills = []): array
            {
                $this->calls[] = ['listings' => $listings, 'skills' => $skills];
                return array_map(fn () => [...$this->answer, 'model' => 'test'], $listings);
            }
        };
        $this->ai->answer = $this->review();
        $this->app->instance(OpportunityClassifier::class, $this->ai);
    }

    private function review(array $changes = []): array
    {
        return [
            'in_scope' => true, 'quality' => 'good', 'quality_reason' => '', 'field' => 'Mobile Development', 'kind' => 'job',
            'seniority' => 'mid', 'work_type' => 'remote', 'employment_types' => ['FULL_TIME'], 'program_type' => 'none',
            'skills' => ['React Native', 'TypeScript', 'Not A Skill'], 'applicant_scope' => 'anywhere', 'eligible_countries' => [],
            'excluded_countries' => [], 'tunisia' => 'yes', 'relocation' => 'unknown', 'visa_sponsorship' => 'no', 'evidence' => 'Work from anywhere.',
            ...$changes,
        ];
    }

    private function company(): User
    {
        return User::where('email', 'company@example.com')->firstOrFail();
    }

    private function postRole(array $data = []): Opportunity
    {
        $this->actingAs($this->company())->post(route('opportunities.store'), [
            'title' => 'Mobile developer', 'description' => 'Build our React Native app with TypeScript. Work from anywhere.',
            'kind' => 'job', 'work_type' => 'remote', 'applicant_scope' => 'unknown', 'relocation' => 'unknown', 'visa_sponsorship' => 'unknown', ...$data,
        ])->assertSessionHasNoErrors();

        return Opportunity::where('title', $data['title'] ?? 'Mobile developer')->latest('id')->firstOrFail();
    }

    public function test_a_company_post_gets_skills_and_the_details_left_empty_without_losing_what_the_company_chose()
    {
        $git = Skill::where('name', 'Git')->firstOrFail();
        $role = $this->postRole(['seniority' => 'senior', 'skills' => [$git->id]]);

        $this->assertCount(1, $this->ai->calls);
        $this->assertContains('React Native', $this->ai->calls[0]['skills']);
        $this->assertSame('Build our React Native app with TypeScript. Work from anywhere.', $this->ai->calls[0]['listings'][$role->id]['description']);
        $this->assertEqualsCanonicalizing(['Git', 'React Native', 'TypeScript'], $role->skills()->pluck('name')->all());
        $this->assertSame('senior', $role->seniority);
        $this->assertSame(['Mobile Development', ['FULL_TIME'], 'anywhere', 'eligible', 'no', 'unknown'],
            [$role->field->name, $role->employment_types, $role->applicant_scope, $role->remote_eligibility, $role->visa_sponsorship, $role->relocation]);

        $this->actingAs($this->company())->post(route('opportunities.update', $role), [
            '_method' => 'put', 'title' => $role->title, 'description' => $role->description, 'kind' => 'job', 'work_type' => 'remote',
            'applicant_scope' => 'local', 'skills' => [$git->id],
        ])->assertSessionHasNoErrors();
        $this->assertCount(1, $this->ai->calls, 'Saving the same text does not ask the AI again.');
        $this->assertSame(['Git'], $role->skills()->pluck('name')->all());

        $this->actingAs($this->company())->post(route('opportunities.update', $role), [
            '_method' => 'put', 'title' => $role->title, 'description' => $role->description.' Expo experience is a plus.', 'kind' => 'job', 'work_type' => 'remote',
            'applicant_scope' => 'local', 'skills' => [$git->id],
        ])->assertSessionHasNoErrors();
        $this->assertCount(2, $this->ai->calls);
        $this->assertSame('local', $role->fresh()->applicant_scope, 'Who can apply stays as the company set it.');
    }

    public function test_open_to_everyone_but_not_tunisia_never_counts_as_open_worldwide()
    {
        $this->ai->answer = $this->review(['tunisia' => 'no', 'excluded_countries' => ['IR']]);
        $role = $this->postRole();

        $this->assertSame(['anywhere', ['IR', 'TN'], 'restricted'], [$role->applicant_scope, $role->excluded_countries, $role->remote_eligibility]);
        $this->assertFalse(Opportunity::searchFilters(['worldwide' => 1])->whereKey($role->id)->exists());
    }

    public function test_nothing_changes_when_the_ai_is_off()
    {
        $this->app->instance(OpportunityClassifier::class, new OpportunityClassifier());
        $role = $this->postRole();

        $this->assertSame(['unknown', 0], [$role->applicant_scope, $role->skills()->count()]);
    }

    public function test_imported_listings_open_to_a_region_with_tunisia_count_as_reachable_from_tunisia()
    {
        $importer = app(OpportunityImporter::class);
        $data = ['work_type' => 'remote', 'remote_eligibility' => 'unknown', 'eligible_countries' => [], 'excluded_countries' => []];

        $emea = $importer->withReview($data, $this->review(['applicant_scope' => 'listed', 'tunisia' => 'yes', 'skills' => []]));
        $this->assertSame(['listed', [], 'eligible'], [$emea['applicant_scope'], $emea['eligible_countries'], $emea['remote_eligibility']]);

        $usOnly = $importer->withReview($data, $this->review(['applicant_scope' => 'listed', 'eligible_countries' => ['US'], 'tunisia' => 'no', 'skills' => []]));
        $this->assertSame(['restricted', []], [$usOnly['remote_eligibility'], $usOnly['excluded_countries']]);

        // Answers saved before the tunisia question keep working.
        $old = $this->review();
        unset($old['tunisia']);
        $this->assertSame('eligible', $importer->withReview($data, $old)['remote_eligibility']);
    }

    public function test_the_ai_knows_every_skill_in_the_system_including_ones_admins_add()
    {
        Skill::create(['name' => 'Odoo Studio']);

        $names = app(OpportunityImporter::class)->skillNames();

        $this->assertContains('Odoo Studio', $names);
        $this->assertContains('React Native', $names);
        $this->assertSame(count($names), count(array_unique(array_map('mb_strtolower', $names))));
        $this->assertLessThanOrEqual(900, count($names));
    }
}

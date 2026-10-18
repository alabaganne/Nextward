<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Candidate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ApplicantProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Tests\Fixtures\DemoSeeder::class);
    }

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    public function test_company_can_view_a_candidate_who_applied_to_its_roles(): void
    {
        $company = $this->user('company@example.com');
        $candidate = $this->user('student@example.com');
        $this->assertTrue(Application::where('candidate_id', $candidate->userable_id)->where('company_id', $company->userable_id)->where('channel', 'internal')->exists());

        $this->actingAs($company)->get(route('candidates.show', $candidate->userable_id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Candidates/Show')
                ->where('candidate.id', $candidate->userable_id)
                ->where('candidate.name', $candidate->name));
    }

    public function test_company_cannot_view_a_candidate_who_did_not_apply(): void
    {
        $company = $this->user('company@example.com');
        $applicants = Application::where('company_id', $company->userable_id)->where('channel', 'internal')->pluck('candidate_id');
        $other = Candidate::has('user')->whereNotIn('id', $applicants)->firstOrFail();

        $this->actingAs($company)->get(route('candidates.show', $other))->assertForbidden();
        $this->actingAs($company)->get(route('candidates.edit', $this->user('student@example.com')->userable_id))->assertForbidden();
        $this->actingAs($company)->get(route('candidates.index'))->assertForbidden();
    }

    public function test_external_applications_do_not_open_the_profile(): void
    {
        $company = $this->user('stridelabs@example.com');
        $candidate = $this->user('professional@example.com');
        $opportunity = $company->userable->opportunities()->firstOrFail();

        $this->actingAs($company)->get(route('candidates.show', $candidate->userable_id))->assertForbidden();

        $application = Application::create(['candidate_id' => $candidate->userable_id, 'opportunity_id' => $opportunity->id, 'company_id' => $company->userable_id, 'channel' => 'external']);
        $this->actingAs($company)->get(route('candidates.show', $candidate->userable_id))->assertForbidden();

        $application->update(['channel' => 'internal']);
        $this->actingAs($company)->get(route('candidates.show', $candidate->userable_id))->assertOk();
    }

    public function test_candidates_cannot_view_each_other(): void
    {
        $this->actingAs($this->user('professional@example.com'))
            ->get(route('candidates.show', $this->user('student@example.com')->userable_id))
            ->assertForbidden();
    }
}

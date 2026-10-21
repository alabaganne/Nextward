<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplyFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Tests\Fixtures\DemoSeeder::class);
    }

    public function test_candidate_can_apply_to_an_opportunity(): void
    {
        $candidate = User::where('email', 'student@example.com')->firstOrFail();

        // An internship the demo student has not applied to yet.
        $opportunity = Opportunity::whereDoesntHave('applications', function ($query) use ($candidate) {
            $query->where('candidate_id', $candidate->userable->id);
        })->firstOrFail();

        $this->actingAs($candidate)
            ->post("/app/opportunities/{$opportunity->id}/apply", [
                'cover_letter' => str_repeat('I care about this role and can contribute from week one. ', 5),
                'message' => str_repeat('Available to start immediately and happy to talk. ', 3),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('applications', [
            'candidate_id' => $candidate->userable->id,
            'opportunity_id' => $opportunity->id,
            'status' => 'submitted',
        ]);
    }

    public function test_company_can_advance_an_application_to_offer(): void
    {
        $application = Application::where('status', 'submitted')->firstOrFail();
        $companyUser = $application->company->user;

        $this->actingAs($companyUser)
            ->post("/app/applications/{$application->id}", ['status' => 'offer'])
            ->assertRedirect();

        $this->assertSame('offer', $application->fresh()->status);
    }

    public function test_candidate_can_withdraw_a_pending_application(): void
    {
        $candidate = User::where('email', 'student@example.com')->firstOrFail();
        $application = Application::where('candidate_id', $candidate->userable->id)
            ->where('status', 'submitted')
            ->firstOrFail();

        $this->actingAs($candidate)
            ->delete("/app/applications/{$application->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('applications', ['id' => $application->id]);
    }

    public function test_candidates_cannot_submit_the_same_application_twice(): void
    {
        $application = Application::where('status', 'submitted')->firstOrFail();

        $this->actingAs($application->candidate->user)
            ->post(route('applications.store', $application->opportunity), [
                'cover_letter' => str_repeat('I would love to join your team. ', 10),
            ])
            ->assertForbidden();

        $this->assertSame(1, Application::where('candidate_id', $application->candidate_id)
            ->where('opportunity_id', $application->opportunity_id)->count());
    }

    public function test_only_the_owning_company_can_advance_an_application(): void
    {
        $application = Application::where('status', 'submitted')->firstOrFail();
        $otherCompany = User::where('userable_type', 'company')
            ->where('userable_id', '<>', $application->company_id)->firstOrFail();

        foreach ([$application->candidate->user, $otherCompany] as $user) {
            $this->actingAs($user)
                ->post(route('applications.reply', $application), ['status' => 'offer'])
                ->assertForbidden();
        }

        $this->assertSame('submitted', $application->fresh()->status);
    }

    public function test_candidates_can_edit_an_application_under_review(): void
    {
        $application = Application::where('status', 'review')->firstOrFail();
        $coverLetter = trim(str_repeat('Here is my updated experience for the role. ', 10));

        $this->actingAs($application->candidate->user)
            ->put(route('applications.update', $application), ['cover_letter' => $coverLetter])
            ->assertRedirect(route('applications.show', $application));

        $this->assertSame($coverLetter, $application->fresh()->cover_letter);
    }

    public function test_final_stage_applications_cannot_be_edited_or_withdrawn_by_candidates(): void
    {
        $application = Application::where('status', 'offer')->firstOrFail();

        foreach (['offer', 'closed'] as $status) {
            $application->update(['status' => $status]);

            $this->actingAs($application->candidate->user)
                ->put(route('applications.update', $application), [
                    'cover_letter' => str_repeat('Here is my updated experience for the role. ', 10),
                ])
                ->assertForbidden();
            $this->delete(route('applications.destroy', $application))->assertForbidden();
        }

        $this->assertDatabaseHas('applications', ['id' => $application->id, 'status' => 'closed']);
    }

    public function test_admins_can_apply_on_nextward_and_track_outside_applications(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $internal = Opportunity::where('application_mode', 'internal')->firstOrFail();
        $external = Opportunity::where('application_mode', 'external')->whereNotNull('application_url')->get()->first(fn ($opportunity) => $opportunity->isOpen());

        $this->actingAs($admin)->get(route('applications.create', $internal))->assertOk();
        $this->actingAs($admin)
            ->post(route('applications.store', $internal), [
                'cover_letter' => str_repeat('I care about this role and can contribute from week one. ', 5),
            ])
            ->assertRedirect(route('applications.index'));

        $admin->refresh();
        $this->assertTrue($admin->isAdmin() && $admin->isCandidate());
        $this->assertDatabaseHas('applications', ['candidate_id' => $admin->userable_id, 'opportunity_id' => $internal->id, 'channel' => 'internal']);

        $this->actingAs($admin)->post(route('applications.track', $external))->assertRedirect();
        $this->assertDatabaseHas('applications', ['candidate_id' => $admin->userable_id, 'opportunity_id' => $external->id, 'channel' => 'external']);
    }

    public function test_admins_cannot_move_their_own_application(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $candidate = $admin->ensureCandidateProfile();
        $opportunity = Opportunity::where('application_mode', 'internal')->firstOrFail();
        $application = Application::create([
            'candidate_id' => $candidate->id, 'opportunity_id' => $opportunity->id, 'company_id' => $opportunity->company_id,
            'cover_letter' => str_repeat('I would love to join your team. ', 10), 'channel' => 'internal', 'status' => 'submitted', 'applied_at' => today(),
        ]);

        $this->actingAs($admin)->post(route('applications.reply', $application), ['status' => 'offer'])->assertForbidden();
        $this->assertSame('submitted', $application->fresh()->status);
    }
}

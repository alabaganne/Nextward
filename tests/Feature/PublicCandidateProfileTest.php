<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\User;
use App\Notifications\PublicProfileReviewed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicCandidateProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $candidateUser;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        config(['inertia.pages.paths' => [resource_path('js/Pages')]]);
        $this->seed(\Tests\Fixtures\DemoSeeder::class);
        $this->candidateUser = User::where('email', 'student@example.com')->firstOrFail();
        $this->admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->candidateUser->userable->update(['headline' => 'Laravel developer', 'website' => 'https://ala.dev']);
    }

    private function candidate(): Candidate
    {
        return $this->candidateUser->userable->fresh();
    }

    public function test_profile_goes_public_only_after_admin_approval(): void
    {
        Notification::fake();
        $url = $this->candidate()->publicUrl();

        $this->actingAs($this->candidateUser->fresh())->put('/profile/public', ['public' => true])->assertRedirect();
        $this->assertSame('pending', $this->candidate()->public_status);

        auth()->logout();
        $this->get($url)->assertNotFound();
        $this->get('/candidates')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Candidates/Directory')->has('candidates.data', 0));

        $this->actingAs($this->admin)->post("/candidates/{$this->candidate()->id}/public-profile", ['decision' => 'approve'])->assertRedirect();
        $this->assertSame('approved', $this->candidate()->public_status);
        Notification::assertSentTo($this->candidateUser, PublicProfileReviewed::class);

        auth()->logout();
        $this->get('/candidates')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('candidates.data', 1)
            ->where('candidates.data.0.name', 'Ala Baganne')
            ->where('seo.robots', 'noindex, follow'));
        $this->get($url)->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Candidates/Public')
            ->where('candidate.headline', 'Laravel developer')
            ->where('candidate.website', 'https://ala.dev')
            ->missing('candidate.email')
            ->missing('candidate.phone_number')
            ->where('seo.robots', 'noindex, follow'));
        $this->get(str_replace('/candidates/', '/fr/candidates/', $url))->assertOk();
    }

    public function test_public_page_never_shows_contact_details_or_resume(): void
    {
        $this->candidate()->forceFill(['public_status' => 'approved', 'resume_name' => 'cv.pdf'])->save();

        $html = $this->get($this->candidate()->publicUrl())->assertOk()->getContent();

        $this->assertStringNotContainsString('student@example.com', $html);
        $this->assertStringNotContainsString('50101959', $html);
        $this->assertStringNotContainsString('cv.pdf', $html);
    }

    public function test_candidate_can_make_their_profile_private_again(): void
    {
        $this->candidate()->forceFill(['public_status' => 'approved'])->save();

        $this->actingAs($this->candidateUser->fresh())->put('/profile/public', ['public' => false])->assertRedirect();

        $this->assertNull($this->candidate()->public_status);
        auth()->logout();
        $this->get($this->candidate()->publicUrl())->assertNotFound();
    }

    public function test_declined_profiles_stay_private_but_the_owner_can_preview(): void
    {
        $this->candidate()->forceFill(['public_status' => 'pending'])->save();

        $this->actingAs($this->admin)->post("/candidates/{$this->candidate()->id}/public-profile", ['decision' => 'decline'])->assertRedirect();

        $this->assertSame('declined', $this->candidate()->public_status);
        $this->actingAs($this->candidateUser->fresh())->get($this->candidate()->publicUrl())->assertOk()->assertInertia(fn (Assert $page) => $page->where('status', 'declined'));
        auth()->logout();
        $this->get($this->candidate()->publicUrl())->assertNotFound();
    }

    public function test_only_admins_review_and_only_requested_profiles(): void
    {
        $candidate = $this->candidate();
        $company = User::where('email', 'company@example.com')->firstOrFail();

        $this->actingAs($this->admin)->post("/candidates/{$candidate->id}/public-profile", ['decision' => 'approve'])->assertSessionHasErrors('decision');
        $this->assertNull($this->candidate()->public_status);

        $candidate->forceFill(['public_status' => 'pending'])->save();
        $this->actingAs($this->candidateUser->fresh())->post("/candidates/{$candidate->id}/public-profile", ['decision' => 'approve'])->assertForbidden();
        $this->actingAs($company)->post("/candidates/{$candidate->id}/public-profile", ['decision' => 'approve'])->assertForbidden();
        $this->assertSame('pending', $this->candidate()->public_status);
    }

    public function test_unverified_candidates_cannot_ask(): void
    {
        $this->candidateUser->forceFill(['email_verified_at' => null])->save();

        $this->actingAs($this->candidateUser->fresh())->put('/profile/public', ['public' => true])->assertRedirect(route('verification.notice'));

        $this->assertNull($this->candidate()->public_status);
    }

    public function test_admin_can_filter_profiles_waiting_for_review(): void
    {
        $this->candidate()->forceFill(['public_status' => 'pending'])->save();

        $this->actingAs($this->admin)->get('/candidates/manage?public=pending')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Candidates/Index')
            ->where('pending_count', 1)
            ->has('candidates.data', 1)
            ->where('candidates.data.0.public_status', 'pending')
            ->where('badges.public_profiles', 1));
    }

    public function test_wrong_slug_redirects_to_the_right_one(): void
    {
        $candidate = $this->candidate();
        $candidate->forceFill(['public_status' => 'approved'])->save();

        $this->get("/candidates/{$candidate->id}-old-name")->assertStatus(301)->assertRedirect($candidate->publicUrl());
    }
}

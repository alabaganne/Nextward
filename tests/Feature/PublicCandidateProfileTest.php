<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\Resume;
use App\Models\User;
use App\Notifications\PublicProfileReviewed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
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

        $this->actingAs($this->candidateUser->fresh())->put('/app/profile/public', ['public' => true])->assertRedirect();
        $this->assertSame('pending', $this->candidate()->public_status);

        auth()->logout();
        $this->get($url)->assertNotFound();
        $this->get('/candidates')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Candidates/Directory')->has('candidates.data', 0));

        $this->actingAs($this->admin)->post("/app/candidates/{$this->candidate()->id}/public-profile", ['decision' => 'approve'])->assertRedirect();
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

    public function test_profile_page_shows_the_public_status_and_settings_do_not(): void
    {
        $this->candidate()->forceFill(['public_status' => 'pending', 'public_contact' => true])->save();

        $this->actingAs($this->candidateUser->fresh())->get('/app/profile')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Profile/Show')
            ->where('profile.public_status', 'pending')
            ->where('profile.public_contact', true)
            ->where('profile.public_url', parse_url($this->candidate()->publicUrl(), PHP_URL_PATH))
            ->where('profile.email_verified', true));
        $this->get('/app/settings')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Settings')
            ->missing('settings.public_status'));
    }

    public function test_public_page_shows_work_history_education_and_projects(): void
    {
        $this->candidate()->forceFill([
            'public_status' => 'approved',
            'experience' => [['title' => 'Backend developer', 'company' => 'Atlas', 'start' => '2022-01', 'current' => true]],
            'education' => [['school' => 'ISIM', 'degree' => 'Engineering']],
            'projects' => [['name' => 'Job board', 'url' => 'https://example.com']],
        ])->save();

        $this->get($this->candidate()->publicUrl())->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('candidate.experience.0.title', 'Backend developer')
            ->where('candidate.education.0.school', 'ISIM')
            ->where('candidate.projects.0.name', 'Job board'));
    }

    public function test_admins_and_companies_browse_talent_in_the_workspace(): void
    {
        $this->candidate()->forceFill(['public_status' => 'approved'])->save();
        $company = User::where('email', 'company@example.com')->firstOrFail();

        foreach ([$this->admin, $company] as $viewer) {
            $this->actingAs($viewer)->get('/app/talent?search=laravel')->assertOk()->assertInertia(fn (Assert $page) => $page
                ->component('Talent/Index')
                ->has('candidates.data', 1)
                ->where('candidates.data.0.name', 'Ala Baganne'));
        }
        $this->actingAs($this->admin)->get('/app/talent?search=nobody-matches-this')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('candidates.data', 0));
        $this->actingAs(User::where('email', 'professional@example.com')->firstOrFail())->get('/app/talent')->assertForbidden();
    }

    public function test_talent_cards_open_the_profile_inside_the_workspace(): void
    {
        $candidate = $this->candidate();
        $candidate->forceFill(['public_status' => 'approved'])->save();
        $slug = $candidate->publicSlug();
        $company = User::where('email', 'company@example.com')->firstOrFail();
        $this->get("/app/talent/{$slug}")->assertRedirect('/login');
        // The public directory links to the public page.
        $this->get('/candidates')->assertInertia(fn (Assert $page) => $page->where('candidates.data.0.url', "/candidates/{$slug}"));

        $this->actingAs($company)->get('/app/talent')->assertInertia(fn (Assert $page) => $page->where('candidates.data.0.url', "/app/talent/{$slug}"));
        $this->actingAs($company)->get("/app/talent/{$slug}")->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertInertia(fn (Assert $page) => $page->component('Candidates/Public')
                ->where('candidate.name', 'Ala Baganne')
                ->where('seo.robots', 'noindex, nofollow')
                ->where('translation', null));
        $this->actingAs($this->admin)->get("/app/talent/{$candidate->id}-old-name")->assertStatus(301)->assertRedirect(route('talent.show', $slug));
        $this->actingAs(User::where('email', 'professional@example.com')->firstOrFail())->get("/app/talent/{$slug}")->assertForbidden();
    }

    public function test_a_candidate_picks_a_username_that_becomes_their_profile_link(): void
    {
        $candidate = $this->candidate();
        $candidate->forceFill(['public_status' => 'approved'])->save();
        $oldLink = "/candidates/{$candidate->id}-ala-baganne";
        $save = fn (User $user, ?string $username) => $this->actingAs($user)->put('/app/profile', ['name' => $user->name, 'username' => $username]);

        $save($this->candidateUser, '  Ala-Dev ')->assertSessionHasNoErrors();
        $this->assertSame('ala-dev', $this->candidateUser->fresh()->username);
        $this->assertSame(url('/candidates/ala-dev'), $this->candidate()->publicUrl());
        $this->get('/candidates/ala-dev')->assertOk()->assertInertia(fn (Assert $page) => $page->where('candidate.name', 'Ala Baganne'));
        $this->get('/fr/candidates/ala-dev')->assertOk();
        $this->get($oldLink)->assertStatus(301)->assertRedirect(url('/candidates/ala-dev'));
        $this->actingAs($this->candidateUser)->get('/app/settings')->assertInertia(fn (Assert $page) => $page->where('settings.username', 'ala-dev'));

        foreach (['ab', '1ala', 'ala--dev', 'ala_dev', 'manage', 'talent', str_repeat('a', 31)] as $bad) {
            $save($this->candidateUser, $bad)->assertSessionHasErrors('username');
        }
        $this->assertSame('ala-dev', $this->candidateUser->fresh()->username);
        $admin = $this->admin;
        $save($admin, 'ala-dev')->assertSessionHasErrors(['username' => 'Someone already uses this username.']);
        $this->get('/candidates/someone-else')->assertNotFound();

        // Clearing it brings back the {id}-{name} link.
        $save($this->candidateUser, '')->assertSessionHasNoErrors();
        $this->assertNull($this->candidateUser->fresh()->username);
        $this->get($oldLink)->assertOk();
        $this->get('/candidates/ala-dev')->assertNotFound();
        // Companies have no candidate profile, so no username either.
        $company = User::where('email', 'company@example.com')->firstOrFail();
        $save($company, 'acme')->assertSessionHasErrors('username');
        // The old workspace link with "manage" still goes to the admin list.
        $this->actingAs($admin)->get('/candidates/manage')->assertStatus(301)->assertRedirect('/app/candidates/manage');
    }

    public function test_public_directory_can_be_searched(): void
    {
        $this->candidate()->forceFill(['public_status' => 'approved'])->save();

        $this->get('/candidates?search=Laravel')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('candidates.data', 1)
            ->where('filters.search', 'Laravel'));
        $this->get('/candidates?resume=1')->assertOk()->assertInertia(fn (Assert $page) => $page->has('candidates.data', 0));
    }

    public function test_public_page_hides_contact_details_and_resume_by_default(): void
    {
        $this->candidate()->forceFill(['public_status' => 'approved'])->save();
        Resume::create(['candidate_id' => $this->candidate()->id, 'name' => 'cv', 'source' => 'uploaded', 'status' => 'ready', 'path' => 'resumes/cv.pdf', 'file_name' => 'cv.pdf', 'is_main' => true]);

        $html = $this->get($this->candidate()->publicUrl())->assertOk()->getContent();

        $this->assertStringNotContainsString('student@example.com', $html);
        $this->assertStringNotContainsString('50101959', $html);
        $this->assertStringNotContainsString('cv.pdf', $html);
    }

    public function test_shared_resume_and_contact_show_to_every_visitor(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('resumes/cv.pdf', '%PDF-1.4');
        $this->candidate()->forceFill(['public_status' => 'approved'])->save();
        Resume::create(['candidate_id' => $this->candidate()->id, 'name' => 'cv', 'source' => 'uploaded', 'status' => 'ready', 'path' => 'resumes/cv.pdf', 'file_name' => 'cv.pdf', 'is_main' => true]);
        $url = $this->candidate()->publicUrl();

        $this->get("{$url}/resume")->assertNotFound();
        $this->actingAs($this->candidateUser->fresh())->put('/app/profile/public', ['contact' => true])->assertRedirect();
        $this->assertTrue($this->candidate()->public_contact);
        auth()->logout();

        $this->get($url)->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('candidate.contact.email', 'student@example.com')
            ->where('candidate.contact.resume_url', parse_url($url, PHP_URL_PATH).'/resume'));
        $this->get('/candidates')->assertInertia(fn (Assert $page) => $page->where('candidates.data.0.has_resume', true));
        $this->get("{$url}/resume")->assertOk()->assertHeader('X-Robots-Tag', 'noindex');

        $this->candidate()->forceFill(['public_status' => null])->save();
        $this->get("{$url}/resume")->assertNotFound();
    }

    public function test_candidate_can_make_their_profile_private_again(): void
    {
        $this->candidate()->forceFill(['public_status' => 'approved'])->save();

        $this->actingAs($this->candidateUser->fresh())->put('/app/profile/public', ['public' => false])->assertRedirect();

        $this->assertNull($this->candidate()->public_status);
        auth()->logout();
        $this->get($this->candidate()->publicUrl())->assertNotFound();
    }

    public function test_declined_profiles_stay_private_but_the_owner_can_preview(): void
    {
        $this->candidate()->forceFill(['public_status' => 'pending'])->save();

        $this->actingAs($this->admin)->post("/app/candidates/{$this->candidate()->id}/public-profile", ['decision' => 'decline'])->assertRedirect();

        $this->assertSame('declined', $this->candidate()->public_status);
        $this->actingAs($this->candidateUser->fresh())->get($this->candidate()->publicUrl())->assertOk()->assertInertia(fn (Assert $page) => $page->where('status', 'declined'));
        auth()->logout();
        $this->get($this->candidate()->publicUrl())->assertNotFound();
    }

    public function test_only_admins_review_and_only_requested_profiles(): void
    {
        $candidate = $this->candidate();
        $company = User::where('email', 'company@example.com')->firstOrFail();

        $this->actingAs($this->admin)->post("/app/candidates/{$candidate->id}/public-profile", ['decision' => 'approve'])->assertSessionHasErrors('decision');
        $this->assertNull($this->candidate()->public_status);

        $candidate->forceFill(['public_status' => 'pending'])->save();
        $this->actingAs($this->candidateUser->fresh())->post("/app/candidates/{$candidate->id}/public-profile", ['decision' => 'approve'])->assertForbidden();
        $this->actingAs($company)->post("/app/candidates/{$candidate->id}/public-profile", ['decision' => 'approve'])->assertForbidden();
        $this->assertSame('pending', $this->candidate()->public_status);
    }

    public function test_unverified_candidates_cannot_ask(): void
    {
        $this->candidateUser->forceFill(['email_verified_at' => null])->save();

        $this->actingAs($this->candidateUser->fresh())->put('/app/profile/public', ['public' => true])->assertRedirect(route('verification.notice'));

        $this->assertNull($this->candidate()->public_status);
    }

    public function test_admin_can_filter_profiles_waiting_for_review(): void
    {
        $this->candidate()->forceFill(['public_status' => 'pending'])->save();

        $this->actingAs($this->admin)->get('/app/candidates/manage?public=pending')->assertOk()->assertInertia(fn (Assert $page) => $page
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

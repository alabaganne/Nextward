<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\Company;
use App\Models\SavedSearch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SignupRefTest extends TestCase
{
    use RefreshDatabase;

    public function test_ref_is_kept_across_pages_and_saved_on_candidate_sign_up(): void
    {
        $this->get('/?ref=Reddit-Tunisia')->assertOk();
        $this->get('/about')->assertOk();
        $this->get('/register')->assertOk()->assertSessionHas('signup_ref', 'reddit-tunisia');

        $this->registerCandidate('sami@example.com');

        $this->assertSame('reddit-tunisia', User::where('email', 'sami@example.com')->value('signup_ref'));
        $this->assertFalse(session()->has('signup_ref'));
    }

    public function test_first_ref_wins(): void
    {
        $this->get('/?ref=reddit-tunisia');
        $this->get('/about?ref=facebook-pfe-group');

        $this->assertSame('reddit-tunisia', session('signup_ref'));
    }

    public function test_ref_is_saved_on_company_sign_up(): void
    {
        Storage::fake('public');
        $this->get('/?ref=linkedin-post-1');

        $this->post('/register', [
            'account_type' => 'company',
            'name' => 'Ref Works',
            'email' => 'ref-works@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'website' => 'https://ref-works.example.com',
            'about' => 'We make tools that help small teams plan their week.',
            'company_logo' => UploadedFile::fake()->image('logo.png', 60, 60),
            'terms' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertSame('linkedin-post-1', User::where('email', 'ref-works@example.com')->value('signup_ref'));
    }

    public function test_bad_values_are_ignored(): void
    {
        foreach (['/?ref=%3Cscript%3E', '/?ref=has%20space', '/?ref=under_score', '/?ref='.str_repeat('a', 61), '/?ref[]=reddit', '/?ref='] as $url) {
            $this->get($url)->assertOk();
            $this->assertFalse(session()->has('signup_ref'), $url);
        }

        $this->registerCandidate('no-ref@example.com');
        $this->assertNull(User::where('email', 'no-ref@example.com')->value('signup_ref'));
    }

    public function test_logged_in_visits_do_not_set_a_ref(): void
    {
        $this->actingAs(User::factory()->create())->get('/?ref=reddit-tunisia');

        $this->assertFalse(session()->has('signup_ref'));
    }

    public function test_only_admins_can_see_sign_up_sources(): void
    {
        $this->get('/admin/signups')->assertRedirect('/login');

        $candidate = Candidate::create()->user()->create(['name' => 'Cand', 'email' => 'cand@example.com', 'password' => 'secret-password']);
        $this->actingAs($candidate)->get('/admin/signups')->assertForbidden();
    }

    public function test_admin_sees_counts_per_ref(): void
    {
        $alertOn = $this->candidate('reddit-tunisia', now()->subDays(2));
        SavedSearch::create(['user_id' => $alertOn->id, 'name' => 'PFE', 'filters' => [], 'frequency' => 'daily']);
        $alertOff = $this->candidate('reddit-tunisia', now()->subDays(20));
        SavedSearch::create(['user_id' => $alertOff->id, 'name' => 'Jobs', 'filters' => [], 'frequency' => 'off']);
        Company::create(['name' => 'Acme'])->user()->make(['name' => 'Acme', 'email' => 'acme@example.com', 'password' => 'secret-password'])
            ->forceFill(['signup_ref' => 'reddit-tunisia', 'terms_accepted_at' => now()->subDays(40)])->save();
        $this->candidate('facebook-pfe-group', now()->subDays(3));
        $this->candidate(null, now()->subDay());

        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get('/admin/signups')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Signups/Index')
                ->has('rows', 3)
                ->where('rows.0', ['ref' => 'reddit-tunisia', 'last_7' => 1, 'last_30' => 2, 'total' => 3, 'candidates' => 2, 'with_alerts' => 1])
                ->where('rows.1', ['ref' => 'facebook-pfe-group', 'last_7' => 1, 'last_30' => 1, 'total' => 1, 'candidates' => 1, 'with_alerts' => 0])
                ->where('rows.2.ref', null)
                ->where('rows.2.total', 1));
    }

    private function registerCandidate(string $email): void
    {
        $this->post('/register', [
            'name' => 'Sami Candidate',
            'email' => $email,
            'password' => 'a-long-password',
            'password_confirmation' => 'a-long-password',
            'terms' => '1',
        ])->assertSessionHasNoErrors();
    }

    private function candidate(?string $ref, $signedUpAt): User
    {
        $user = Candidate::create()->user()->make(['name' => 'Cand', 'email' => uniqid().'@example.com', 'password' => 'secret-password']);
        $user->forceFill(['signup_ref' => $ref, 'terms_accepted_at' => $signedUpAt])->save();

        return $user;
    }
}

<?php

namespace Tests\Feature;

use App\Importing\CandidateProfiles;
use App\Models\Candidate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CandidateRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_sign_up_makes_one_kind_of_candidate(): void
    {
        $this->post('/register', [
            'account_type' => 'student',
            'name' => 'Sami Candidate',
            'email' => 'sami@example.com',
            'password' => 'a-long-password',
            'password_confirmation' => 'a-long-password',
            'career_stage' => 'professional',
            'terms' => true,
        ])->assertRedirect();

        $user = User::where('email', 'sami@example.com')->firstOrFail();
        $this->assertTrue($user->isCandidate());
        $this->assertArrayNotHasKey('career_stage', $user->userable->getAttributes());
    }

    public function test_admins_get_a_candidate_profile_they_can_fill_in(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get('/app/settings')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('settings.userable_type', 'candidate'));

        $this->actingAs($admin)->put('/app/profile', [
            'name' => $admin->name,
            'email' => $admin->email,
            'about' => 'Founder of Nextward and a software engineer.',
            'website' => 'https://example.com',
            'country_code' => 'tn',
            'location' => 'Monastir',
            'headline' => 'Full-stack engineer',
        ])->assertRedirect(route('profile.show'));

        $admin->refresh();
        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($admin->isCandidate());
        $this->assertSame(1, Candidate::count());
        $this->assertSame('Founder of Nextward and a software engineer.', $admin->userable->about);
        $this->assertSame('https://example.com', $admin->userable->website);
        $this->assertSame('TN', $admin->userable->country_code);
        $this->assertSame('Monastir', $admin->userable->location);
    }

    public function test_imports_follow_the_owner_profile_with_its_visa_preference(): void
    {
        // Factory emails can be on example.com, which imports leave out.
        $admin = User::factory()->create(['is_admin' => true, 'email' => 'owner@nextward.test']);
        $this->actingAs($admin)->get('/app/settings')->assertOk();
        $this->actingAs($admin)->put('/app/profile', [
            'name' => $admin->name,
            'email' => $admin->email,
            'preferences' => ['kinds' => ['job'], 'countries' => ['DE', 'US'], 'skills' => ['React', 'Laravel'], 'needs_visa' => true],
        ])->assertRedirect(route('profile.show'));
        $other = Candidate::factory()->create(['preferences' => ['kinds' => ['internship'], 'skills' => ['Java', 'Spring']]]);
        $other->user()->save(User::factory()->make(['email' => 'friend@nextward.test']));

        $profiles = CandidateProfiles::load()->profiles;
        $this->assertSame([$admin->name], array_column($profiles, 'name'));
        $this->assertTrue($profiles[0]['needs_visa']);
        $this->assertSame(['DE', 'US'], $profiles[0]['countries']);

        config(['importer.profiles.from' => 'everyone']);
        $this->assertCount(2, CandidateProfiles::load()->profiles);
    }
}

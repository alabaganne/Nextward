<?php

namespace Tests\Feature;

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

        $this->actingAs($admin)->get('/settings')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('settings.userable_type', 'candidate'));

        $this->actingAs($admin)->put('/profile', [
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
}

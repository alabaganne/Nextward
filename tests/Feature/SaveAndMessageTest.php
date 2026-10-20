<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SaveAndMessageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Tests\Fixtures\DemoSeeder::class);
    }

    public function test_candidate_can_toggle_a_saved_role(): void
    {
        $candidate = User::where('email', 'student@example.com')->firstOrFail();
        $opportunity = Opportunity::whereDoesntHave('likes', function ($query) use ($candidate) {
            $query->where('candidate_id', $candidate->userable->id);
        })->firstOrFail();

        $this->actingAs($candidate)->post("/app/like/{$opportunity->id}")->assertRedirect();
        $this->assertTrue($candidate->userable->fresh()->likes->contains($opportunity->id));

        $this->actingAs($candidate)->post("/app/like/{$opportunity->id}")->assertRedirect();
        $this->assertFalse($candidate->userable->fresh()->likes->contains($opportunity->id));
    }

    public function test_saved_roles_are_paged(): void
    {
        $candidate = User::where('email', 'student@example.com')->firstOrFail();
        $candidate->userable->likes()->sync(Opportunity::factory()->count(21)->create()->pluck('id'));

        $this->actingAs($candidate)->get('/app/likes')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Opportunities/Likes')
            ->has('likes.data', 20)
            ->where('likes.meta.total', 21)
            ->where('likes.meta.last_page', 2)
            ->where('likes.data.0.liked', true));
        $this->actingAs($candidate)->get('/app/likes?page=2')->assertInertia(fn (Assert $page) => $page->has('likes.data', 1));
        $this->actingAs($candidate)->get('/app/likes?page=3')->assertRedirect('/app/likes?page=2');
    }

    public function test_companies_cannot_save_roles(): void
    {
        $companyUser = Company::firstOrFail()->user;
        $opportunity = Opportunity::firstOrFail();

        $this->actingAs($companyUser)->post("/app/like/{$opportunity->id}")->assertForbidden();
    }

    public function test_candidate_can_message_a_company(): void
    {
        $candidate = User::where('email', 'student@example.com')->firstOrFail();
        $companyUser = Company::firstOrFail()->user;

        $this->actingAs($candidate)
            ->post('/app/messages', [
                'to_id' => $companyUser->id,
                'text' => 'Hello! I would love to learn more about your open roles.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('messages', [
            'from_id' => $candidate->id,
            'to_id' => $companyUser->id,
            'text' => 'Hello! I would love to learn more about your open roles.',
        ]);
    }

    public function test_messages_show_deep_links_into_the_inbox(): void
    {
        $candidate = User::where('email', 'student@example.com')->firstOrFail();
        $companyUser = Company::firstOrFail()->user;

        $this->actingAs($candidate)
            ->get("/app/messages/{$companyUser->id}")
            ->assertRedirect(route('messages.index', ['user_id' => $companyUser->id]));
    }
}

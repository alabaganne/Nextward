<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class MessagingRulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Tests\Fixtures\DemoSeeder::class);
    }

    private function send(User $from, User $to)
    {
        return $this->actingAs($from)->post('/messages', ['to_id' => $to->id, 'text' => 'Hello there.']);
    }

    private function companyWithNoLinkTo(User $candidateUser): User
    {
        return User::where('userable_type', 'company')
            ->whereNotIn('userable_id', Application::where('candidate_id', $candidateUser->userable_id)->select('company_id'))
            ->whereNotIn('id', Message::where('from_id', $candidateUser->id)->select('to_id'))
            ->firstOrFail();
    }

    public function test_nobody_can_message_an_admin(): void
    {
        $candidate = User::where('email', 'student@example.com')->firstOrFail();
        $company = User::where('email', 'company@example.com')->firstOrFail();
        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        $this->send($candidate, $admin)->assertForbidden();
        $this->send($company, $admin)->assertForbidden();
        $this->assertDatabaseMissing('messages', ['to_id' => $admin->id]);
    }

    public function test_admins_and_candidates_cannot_message_candidates(): void
    {
        $candidate = User::where('email', 'student@example.com')->firstOrFail();
        $professional = User::where('email', 'professional@example.com')->firstOrFail();
        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        $this->send($admin, $candidate)->assertForbidden();
        $this->send($professional, $candidate)->assertForbidden();
        $this->send($candidate, $candidate)->assertForbidden();
        $this->assertDatabaseMissing('messages', ['from_id' => $professional->id, 'to_id' => $candidate->id]);
    }

    public function test_a_company_cannot_cold_message_a_candidate(): void
    {
        $professional = User::where('email', 'professional@example.com')->firstOrFail();
        $company = $this->companyWithNoLinkTo($professional);

        $this->send($company, $professional)->assertForbidden();
        $this->assertDatabaseMissing('messages', ['from_id' => $company->id, 'to_id' => $professional->id]);
    }

    public function test_a_company_can_message_its_applicants(): void
    {
        $application = Application::where('channel', 'internal')->whereHas('candidate.user', fn ($query) => $query->where('email', 'professional@example.com'))->firstOrFail();
        $company = $application->company->user;
        $candidateUser = $application->candidate->user;

        $this->send($company, $candidateUser)->assertRedirect();
        $this->assertDatabaseHas('messages', ['from_id' => $company->id, 'to_id' => $candidateUser->id]);
    }

    public function test_a_candidate_can_start_a_chat_and_the_company_can_reply(): void
    {
        $professional = User::where('email', 'professional@example.com')->firstOrFail();
        $company = $this->companyWithNoLinkTo($professional);

        $this->send($professional, $company)->assertRedirect();
        $this->send($company, $professional)->assertRedirect();

        $this->assertDatabaseHas('messages', ['from_id' => $professional->id, 'to_id' => $company->id]);
        $this->assertDatabaseHas('messages', ['from_id' => $company->id, 'to_id' => $professional->id]);
    }

    public function test_the_inbox_does_not_open_a_thread_with_an_unrelated_user(): void
    {
        $candidate = User::where('email', 'student@example.com')->firstOrFail();
        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($candidate)->get(route('messages.index', ['user_id' => $admin->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('selectedContact', null));
    }

    public function test_message_sending_is_rate_limited(): void
    {
        $candidate = User::where('email', 'student@example.com')->firstOrFail();
        $company = User::where('email', 'company@example.com')->firstOrFail();

        for ($i = 0; $i < 30; $i++) {
            $this->send($candidate, $company)->assertRedirect();
        }

        $this->send($candidate, $company)->assertStatus(429);
    }

    public function test_the_broken_message_delete_route_is_gone(): void
    {
        $this->assertFalse(Route::has('messages.destroy'));

        $candidate = User::where('email', 'student@example.com')->firstOrFail();
        $message = Message::where('from_id', $candidate->id)->firstOrFail();

        $this->actingAs($candidate)->delete("/messages/{$message->id}")->assertStatus(405);
        $this->assertNotNull($message->fresh());
    }
}

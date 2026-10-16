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

    private function companyWithNoLinkTo(User $candidate): User
    {
        return User::where('userable_type', 'company')
            ->whereNotIn('userable_id', Application::where('student_id', $candidate->userable_id)->select('company_id'))
            ->whereNotIn('id', Message::where('from_id', $candidate->id)->select('to_id'))
            ->firstOrFail();
    }

    public function test_nobody_can_message_an_admin(): void
    {
        $student = User::where('email', 'student@example.com')->firstOrFail();
        $company = User::where('email', 'company@example.com')->firstOrFail();
        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        $this->send($student, $admin)->assertForbidden();
        $this->send($company, $admin)->assertForbidden();
        $this->assertDatabaseMissing('messages', ['to_id' => $admin->id]);
    }

    public function test_admins_and_candidates_cannot_message_candidates(): void
    {
        $student = User::where('email', 'student@example.com')->firstOrFail();
        $professional = User::where('email', 'professional@example.com')->firstOrFail();
        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        $this->send($admin, $student)->assertForbidden();
        $this->send($professional, $student)->assertForbidden();
        $this->send($student, $student)->assertForbidden();
        $this->assertDatabaseMissing('messages', ['from_id' => $professional->id, 'to_id' => $student->id]);
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
        $application = Application::where('channel', 'internal')->whereHas('student.user', fn ($query) => $query->where('email', 'professional@example.com'))->firstOrFail();
        $company = $application->company->user;
        $candidate = $application->student->user;

        $this->send($company, $candidate)->assertRedirect();
        $this->assertDatabaseHas('messages', ['from_id' => $company->id, 'to_id' => $candidate->id]);
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
        $student = User::where('email', 'student@example.com')->firstOrFail();
        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($student)->get(route('messages.index', ['user_id' => $admin->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('selectedContact', null));
    }

    public function test_message_sending_is_rate_limited(): void
    {
        $student = User::where('email', 'student@example.com')->firstOrFail();
        $company = User::where('email', 'company@example.com')->firstOrFail();

        for ($i = 0; $i < 30; $i++) {
            $this->send($student, $company)->assertRedirect();
        }

        $this->send($student, $company)->assertStatus(429);
    }

    public function test_the_broken_message_delete_route_is_gone(): void
    {
        $this->assertFalse(Route::has('messages.destroy'));

        $student = User::where('email', 'student@example.com')->firstOrFail();
        $message = Message::where('from_id', $student->id)->firstOrFail();

        $this->actingAs($student)->delete("/messages/{$message->id}")->assertStatus(405);
        $this->assertNotNull($message->fresh());
    }
}

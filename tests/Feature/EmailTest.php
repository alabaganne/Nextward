<?php

namespace Tests\Feature;

use App\Mail\ContactMessage;
use App\Mail\OpportunityDigest;
use App\Models\Application;
use App\Models\Internship;
use App\Models\Message;
use App\Models\SavedSearch;
use App\Models\User;
use App\Notifications\AccountCreated;
use App\Notifications\ApplicationReviewed;
use App\Notifications\ApplicationSubmitted;
use App\Notifications\MessageReceived;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class EmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Tests\Fixtures\DemoSeeder::class);
    }

    private function student(): User
    {
        return User::where('email', 'student@example.com')->firstOrFail();
    }

    private function assertBranded(string $html): void
    {
        $this->assertStringContainsString('/brand/nextward/symbol-192.png', $html);
        $this->assertStringContainsString('#2563eb', $html);
        $this->assertStringContainsString('Internships and jobs in Tunisia and worldwide', $html);
        $this->assertStringNotContainsString('Laravel', $html);
    }

    public function test_new_accounts_get_a_branded_verification_email(): void
    {
        Notification::fake();
        $user = $this->student();
        $user->forceFill(['email_verified_at' => null])->save();

        event(new Registered($user));

        Notification::assertSentTo($user, VerifyEmail::class, function ($notification) use ($user) {
            $mail = $notification->toMail($user);
            $this->assertSame('Confirm your email for Nextward', $mail->subject);
            $html = (string) $mail->render();
            $this->assertBranded($html);
            $this->assertStringContainsString('Confirm my email', $html);
            $this->assertStringContainsString('Hi Ala,', $html);

            return true;
        });
    }

    public function test_unverified_users_must_confirm_before_applying_messaging_or_saving_alerts(): void
    {
        $user = $this->student();
        $user->forceFill(['email_verified_at' => null])->save();
        $internship = Internship::where('application_mode', 'internal')->firstOrFail();

        $this->actingAs($user)->get("/internships/{$internship->id}/apply")->assertRedirect(route('verification.notice'));
        $this->actingAs($user)->post("/internship/{$internship->id}/apply", ['cover_letter' => 'Hello'])->assertRedirect(route('verification.notice'));
        $this->actingAs($user)->post('/messages', ['to_id' => $internship->company->user->id, 'text' => 'Hello'])->assertRedirect(route('verification.notice'));
        $this->actingAs($user)->post('/saved-searches', ['name' => 'Laravel'])->assertRedirect(route('verification.notice'));

        $this->assertSame(0, Message::where('from_id', $user->id)->where('text', 'Hello')->count());
        $this->actingAs($user)->get('/dashboard')->assertOk();
    }

    public function test_resetting_the_password_also_confirms_the_email(): void
    {
        $user = $this->student();
        $user->forceFill(['email_verified_at' => null])->save();
        $token = Password::broker()->createToken($user);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'a-new-password-123',
            'password_confirmation' => 'a-new-password-123',
        ])->assertSessionHasNoErrors();

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_password_reset_email_is_branded(): void
    {
        $user = $this->student();
        $mail = (new ResetPassword('token-123'))->toMail($user);

        $this->assertSame('Reset your Nextward password', $mail->subject);
        $html = (string) $mail->render();
        $this->assertBranded($html);
        $this->assertStringContainsString('Choose a new password', $html);
        $this->assertStringContainsString('reset-password/token-123', $html);
    }

    public function test_companies_get_an_email_for_each_new_application(): void
    {
        $application = Application::with(['company.user', 'student.user', 'internship'])->where('status', 'submitted')->firstOrFail();
        $companyUser = $application->company->user;
        $notification = new ApplicationSubmitted($application);

        $this->assertContains('mail', $notification->via($companyUser));
        $mail = $notification->toMail($companyUser);
        $this->assertSame('New application for '.$application->internship->title, $mail->subject);
        $html = (string) $mail->render();
        $this->assertBranded($html);
        $this->assertStringContainsString('Review the application', $html);
        $this->assertStringContainsString(route('applications.show', $application), $html);
    }

    public function test_candidates_get_an_email_when_the_status_changes(): void
    {
        $application = Application::with(['company.user', 'student.user', 'internship'])->where('status', 'submitted')->firstOrFail();
        $application->status = 'interview';
        $candidate = $application->student->user;

        $mail = (new ApplicationReviewed($application))->toMail($candidate);

        $this->assertSame($application->company->display_name.' would like to interview you', $mail->subject);
        $html = (string) $mail->render();
        $this->assertBranded($html);
        $this->assertStringContainsString('You have an interview request', $html);
    }

    public function test_message_emails_show_text_people_typed_as_plain_text(): void
    {
        $student = $this->student();
        $company = Internship::firstOrFail()->company->user;
        $message = Message::create(['from_id' => $company->id, 'to_id' => $student->id, 'text' => 'Click [here](https://evil.example) <b>now</b>']);

        $html = (string) (new MessageReceived($message))->toMail($student)->render();

        $this->assertBranded($html);
        $this->assertStringNotContainsString('href="https://evil.example"', $html);
        $this->assertStringNotContainsString('<b>now</b>', $html);
        $this->assertStringContainsString('Read and reply', $html);
    }

    public function test_message_emails_are_sent_at_most_once_per_conversation_in_a_while(): void
    {
        Cache::flush();
        $student = $this->student();
        $company = Internship::firstOrFail()->company->user;
        $message = Message::create(['from_id' => $company->id, 'to_id' => $student->id, 'text' => 'Hello there']);

        $this->assertTrue(MessageReceived::shouldEmail($message));
        $this->assertFalse(MessageReceived::shouldEmail($message));
    }

    public function test_sending_a_message_emails_the_recipient_once_in_a_while(): void
    {
        Notification::fake();
        Cache::flush();
        $application = Application::with(['company.user', 'student.user'])->firstOrFail();
        $company = $application->company->user;
        $candidate = $application->student->user;

        $this->actingAs($company)->post('/messages', ['to_id' => $candidate->id, 'text' => 'Are you free on Tuesday?'])->assertRedirect();
        $this->actingAs($company)->post('/messages', ['to_id' => $candidate->id, 'text' => 'Or Wednesday?'])->assertRedirect();

        Notification::assertSentToTimes($candidate, MessageReceived::class, 1);
    }

    public function test_invite_email_lets_the_person_choose_a_password(): void
    {
        $user = $this->student();
        $html = (string) (new AccountCreated)->toMail($user)->render();

        $this->assertBranded($html);
        $this->assertStringContainsString('Choose my password', $html);
        $this->assertStringContainsString('reset-password/', $html);
    }

    public function test_saved_search_digest_is_branded(): void
    {
        $search = SavedSearch::firstOrFail();
        $mail = new OpportunityDigest($search, Internship::query()->take(2)->get(), 'test-batch');

        $html = $mail->render();

        $this->assertBranded($html);
        $this->assertStringContainsString('See all matching roles', $html);
        $this->assertStringContainsString('Pause these alerts', $html);
    }

    public function test_contact_form_sends_the_message_to_the_team(): void
    {
        Mail::fake();
        config(['mail.contact_address' => 'team@example.com']);

        $this->post('/contact', [
            'name' => 'Sara Ben Ali',
            'email' => 'sara@example.com',
            'topic' => 'listing',
            'message' => 'The deadline on this listing looks wrong, please check it.',
            'page_url' => 'https://nextward.test/opportunities/1-role',
        ])->assertRedirect()->assertSessionHasNoErrors();

        Mail::assertSent(ContactMessage::class, function (ContactMessage $mail) {
            $this->assertTrue($mail->hasTo('team@example.com'));
            $this->assertTrue($mail->hasReplyTo('sara@example.com'));
            $this->assertBranded($mail->render());

            return true;
        });
    }

    public function test_contact_form_checks_input_and_ignores_bots(): void
    {
        Mail::fake();

        $this->post('/contact', ['name' => '', 'email' => 'not-an-email', 'topic' => 'other', 'message' => 'short'])
            ->assertSessionHasErrors(['name', 'email', 'topic', 'message']);
        $this->post('/contact', ['name' => 'Bot', 'email' => 'bot@example.com', 'topic' => 'general', 'message' => str_repeat('spam ', 10), 'website' => 'http://spam.example'])
            ->assertRedirect();

        Mail::assertNothingSent();
    }
}

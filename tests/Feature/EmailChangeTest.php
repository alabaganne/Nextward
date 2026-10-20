<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ConfirmEmailChange;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailChangeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Tests\Fixtures\DemoSeeder::class);
        Notification::fake();
        $this->user = User::where('email', 'student@example.com')->firstOrFail();
    }

    private function confirmUrl(User $user): string
    {
        $url = null;
        Notification::assertSentTo($user, ConfirmEmailChange::class, function ($notification) use ($user, &$url) {
            $mail = $notification->toMail($user);
            $url = $mail->viewData['url'];
            $html = (string) $mail->render();
            return str_contains($html, 'Confirm the change') && str_contains($html, e($user->pending_email ?? 'new@example.com'));
        });
        return $url;
    }

    public function test_verified_email_changes_only_after_the_old_address_confirms(): void
    {
        $this->actingAs($this->user)->post('/app/settings/email', ['email' => 'new@example.com', 'password' => 'password'])
            ->assertSessionHasNoErrors()->assertRedirect();

        $user = $this->user->fresh();
        $this->assertSame('student@example.com', $user->email);
        $this->assertSame('new@example.com', $user->pending_email);
        $this->assertNotNull($user->email_verified_at);
        $url = $this->confirmUrl($user);
        $this->assertSame('student@example.com', $user->routeNotificationFor('mail'));

        // The link is opened from the old inbox, often on another device.
        auth()->logout();
        $this->get($url)->assertRedirect(route('login'))->assertSessionHas('status');

        $user = $this->user->fresh();
        $this->assertSame('new@example.com', $user->email);
        $this->assertNull($user->pending_email);
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_unverified_email_is_corrected_right_away(): void
    {
        $this->user->forceFill(['email_verified_at' => null])->save();

        $this->actingAs($this->user->fresh())->post('/app/settings/email', ['email' => 'fixed@example.com', 'password' => 'password'])
            ->assertSessionHasNoErrors();

        $user = $this->user->fresh();
        $this->assertSame('fixed@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);
        Notification::assertNotSentTo($user, ConfirmEmailChange::class);
    }

    public function test_change_needs_the_password_and_a_free_new_address(): void
    {
        $this->actingAs($this->user)->post('/app/settings/email', ['email' => 'new@example.com', 'password' => 'wrong'])->assertSessionHasErrors('password');
        $this->actingAs($this->user)->post('/app/settings/email', ['email' => 'admin@example.com', 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'Another account already uses this email.']);
        $this->actingAs($this->user)->post('/app/settings/email', ['email' => 'Student@Example.com', 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'This is already your email.']);

        $this->assertNull($this->user->fresh()->pending_email);
        Notification::assertNothingSent();
    }

    public function test_cancelled_or_tampered_links_do_nothing(): void
    {
        $this->actingAs($this->user)->post('/app/settings/email', ['email' => 'new@example.com', 'password' => 'password']);
        $url = $this->confirmUrl($this->user->fresh());

        $this->get(str_replace(sha1('new@example.com'), sha1('evil@example.com'), $url))->assertForbidden();

        $this->actingAs($this->user)->delete('/app/settings/email')->assertRedirect();
        $this->assertNull($this->user->fresh()->pending_email);
        $this->actingAs($this->user)->get($url)->assertRedirect(route('settings'));
        $this->assertSame('student@example.com', $this->user->fresh()->email);
    }

    public function test_link_fails_if_the_new_address_was_taken_meanwhile(): void
    {
        $this->actingAs($this->user)->post('/app/settings/email', ['email' => 'new@example.com', 'password' => 'password']);
        $url = $this->confirmUrl($this->user->fresh());
        User::where('email', 'admin@example.com')->update(['email' => 'new@example.com']);

        $this->actingAs($this->user)->get($url)->assertRedirect(route('settings'));

        $this->assertSame('student@example.com', $this->user->fresh()->email);
        $this->assertNull($this->user->fresh()->pending_email);
    }

    public function test_expired_links_are_refused(): void
    {
        $this->actingAs($this->user)->post('/app/settings/email', ['email' => 'new@example.com', 'password' => 'password']);
        $expired = URL::temporarySignedRoute('email.change.confirm', now()->subMinute(), ['user' => $this->user->id, 'hash' => sha1('new@example.com')]);

        $this->get($expired)->assertForbidden();
        $this->assertSame('student@example.com', $this->user->fresh()->email);
    }
}

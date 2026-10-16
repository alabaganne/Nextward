<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\AccountCreated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AccountInviteTest extends TestCase
{
    use RefreshDatabase;

    public function test_accounts_created_by_an_admin_get_an_invite(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->post(route('students.store'), [
            'name' => 'Invited Candidate',
            'email' => 'invited-candidate@example.com',
        ])->assertRedirect(route('students.index'));
        $this->actingAs($admin)->post(route('companies.store'), [
            'name' => 'Invited Company',
            'email' => 'invited-company@example.com',
            'website' => 'https://invited.example.com',
        ])->assertRedirect(route('companies.index'));

        foreach (['invited-candidate@example.com', 'invited-company@example.com'] as $email) {
            $user = User::where('email', $email)->firstOrFail();
            Notification::assertSentTo($user, AccountCreated::class);
            $this->assertNull($user->email_verified_at);
        }
        Notification::assertNotSentTo($admin, AccountCreated::class);
    }

    public function test_no_invite_is_sent_when_the_form_is_not_valid(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->post(route('students.store'), ['name' => 'No Email'])->assertSessionHasErrors('email');

        Notification::assertNothingSent();
    }
}

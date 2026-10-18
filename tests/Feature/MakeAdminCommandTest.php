<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MakeAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_makes_an_existing_account_an_admin(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'ops@example.com']);
        $this->assertFalse($user->isAdmin());

        $this->artisan('nextward:make-admin', ['email' => 'ops@example.com'])->assertSuccessful();

        $user->refresh();
        $this->assertTrue($user->isAdmin());
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_it_keeps_the_candidate_profile_and_refuses_company_accounts(): void
    {
        $candidate = Candidate::create(['about' => 'Candidate profile']);
        $user = $candidate->user()->create(['name' => 'Candidate', 'email' => 'candidate@example.com', 'password' => Hash::make('password')]);

        $this->artisan('nextward:make-admin', ['email' => 'candidate@example.com'])->assertSuccessful();
        $user->refresh();
        $this->assertTrue($user->isAdmin());
        $this->assertTrue($user->isCandidate());
        $this->assertSame($candidate->id, $user->userable_id);

        $company = \App\Models\Company::create(['name' => 'Acme']);
        $owner = $company->user()->create(['name' => 'Acme', 'email' => 'acme@example.com', 'password' => Hash::make('password')]);
        $this->artisan('nextward:make-admin', ['email' => 'acme@example.com'])->assertFailed();
        $this->assertFalse((bool) $owner->fresh()->is_admin);
    }

    public function test_it_creates_a_new_admin_after_asking_for_a_name_and_password(): void
    {
        $this->artisan('nextward:make-admin', ['email' => 'new-admin@example.com'])
            ->expectsQuestion('Name', 'New Admin')
            ->expectsQuestion('Password (at least 8 characters)', 'a-long-secret')
            ->expectsQuestion('Type the password again', 'a-long-secret')
            ->assertSuccessful();

        $user = User::where('email', 'new-admin@example.com')->firstOrFail();
        $this->assertSame('New Admin', $user->name);
        $this->assertTrue($user->isAdmin());
        $this->assertTrue(Hash::check('a-long-secret', $user->password));
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame('candidate', $user->userable_type);
        $this->assertTrue($user->isCandidate());
    }

    public function test_it_stops_when_the_input_is_not_valid(): void
    {
        $this->artisan('nextward:make-admin', ['email' => 'new-admin@example.com'])
            ->expectsQuestion('Name', 'New Admin')
            ->expectsQuestion('Password (at least 8 characters)', 'a-long-secret')
            ->expectsQuestion('Type the password again', 'another-secret')
            ->assertFailed();

        $this->artisan('nextward:make-admin', ['email' => 'new-admin@example.com'])
            ->expectsQuestion('Name', 'New Admin')
            ->expectsQuestion('Password (at least 8 characters)', 'short')
            ->expectsQuestion('Type the password again', 'short')
            ->assertFailed();

        $this->artisan('nextward:make-admin', ['email' => 'not-an-email'])->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }
}

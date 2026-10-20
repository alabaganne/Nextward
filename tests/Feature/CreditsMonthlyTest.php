<?php

namespace Tests\Feature;

use App\Billing\Credits;
use App\Models\Candidate;
use App\Models\CreditTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditsMonthlyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['billing.enabled' => true, 'billing.signup_credits' => 0, 'billing.monthly_credits' => 5]);
    }

    public function test_verified_candidates_get_their_credits_once_a_month(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 1)->setTime(6, 0));
        $verified = $this->candidate('verified@example.com', true);
        $unverified = $this->candidate('unverified@example.com', false);
        $company = \App\Models\Company::create()->user()->create(['name' => 'Acme', 'email' => 'acme@example.com', 'password' => 'password']);
        $company->forceFill(['email_verified_at' => now()])->save();
        $credits = app(Credits::class);

        $this->artisan('credits:monthly')->expectsOutputToContain('Gave 5 credits to 1 candidate(s).')->assertSuccessful();
        $this->artisan('credits:monthly')->expectsOutputToContain('to 0 candidate(s)')->assertSuccessful();

        $this->assertSame(5, $credits->balance($verified));
        $this->assertSame(0, $credits->balance($unverified));
        $this->assertSame(0, $credits->balance($company));
        $this->assertSame('monthly:2026-11', CreditTransaction::where('user_id', $verified->id)->sole()->note);

        $this->travelTo(now()->setDate(2026, 12, 1));
        $this->artisan('credits:monthly')->assertSuccessful();
        $this->assertSame(10, $credits->balance($verified));
    }

    public function test_nothing_is_given_while_billing_is_off(): void
    {
        config(['billing.enabled' => false]);
        $verified = $this->candidate('verified@example.com', true);

        $this->artisan('credits:monthly')->expectsOutputToContain('Monthly credits are off.')->assertSuccessful();
        $this->assertSame(0, CreditTransaction::where('user_id', $verified->id)->count());
    }

    public function test_new_candidates_get_the_sign_up_credits(): void
    {
        config(['billing.signup_credits' => 20]);

        $this->assertSame(20, app(Credits::class)->balance($this->candidate('new@example.com', false)));
        $this->assertSame('welcome', CreditTransaction::sole()->reason);
    }

    public function test_the_monthly_credits_are_scheduled(): void
    {
        $this->artisan('schedule:list')->expectsOutputToContain('credits:monthly')->assertSuccessful();
    }

    private function candidate(string $email, bool $verified): User
    {
        $user = Candidate::create()->user()->create(['name' => 'Demo', 'email' => $email, 'password' => 'password']);
        $verified && $user->forceFill(['email_verified_at' => now()])->save();

        return $user;
    }
}

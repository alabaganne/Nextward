<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Company;
use App\Models\Opportunity;
use App\Models\Candidate;
use App\Models\User;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AccountSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->seed(\Tests\Fixtures\DemoSeeder::class);
    }

    public function test_deleting_a_candidate_account_removes_their_data_and_files(): void
    {
        $user = User::where('email', 'student@example.com')->firstOrFail();
        $candidateId = $user->userable_id;
        $withFile = Application::where('candidate_id', $candidateId)->get()->first(fn ($application) => $application->attachments);
        $path = $withFile->attachments[0]['path'];
        DB::table('opportunity_views')->insert(['candidate_id' => $candidateId, 'opportunity_id' => Opportunity::firstOrFail()->id, 'viewed_at' => now()]);

        Storage::disk('local')->assertExists($path);
        $this->assertGreaterThan(0, DB::table('saved_searches')->where('user_id', $user->id)->count());
        $this->assertGreaterThan(0, DB::table('likes')->where('candidate_id', $candidateId)->count());
        $this->assertGreaterThan(0, $user->notifications()->count());

        $this->actingAs($user)->delete('/profile', ['password' => 'password'])->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull(User::find($user->id));
        $this->assertNull(Candidate::find($candidateId));
        $this->assertSame(0, Application::where('candidate_id', $candidateId)->count());
        $this->assertSame(0, DB::table('saved_searches')->where('user_id', $user->id)->count());
        $this->assertSame(0, DB::table('likes')->where('candidate_id', $candidateId)->count());
        $this->assertSame(0, DB::table('opportunity_views')->where('candidate_id', $candidateId)->count());
        $this->assertSame(0, DB::table('skillables')->where('skillable_type', 'candidate')->where('skillable_id', $candidateId)->count());
        $this->assertSame(0, DB::table('notifications')->where('notifiable_id', $user->id)->count());
        $this->assertSame(0, DB::table('messages')->where('from_id', $user->id)->orWhere('to_id', $user->id)->count());
        Storage::disk('local')->assertMissing($path);
    }

    public function test_deleting_a_company_account_closes_its_roles_and_keeps_candidate_history(): void
    {
        $company = Company::whereHas('user')->whereHas('opportunities', fn ($query) => $query->available())->whereHas('applications')->firstOrFail();
        $user = $company->user;
        $name = $company->display_name;
        $applications = Application::where('company_id', $company->id)->count();

        $this->actingAs($user)->delete('/profile', ['password' => 'password'])->assertRedirect('/');

        $this->assertNull(User::find($user->id));
        $company = Company::findOrFail($company->id);
        $this->assertNull($company->user);
        $this->assertSame($name, $company->display_name);
        $this->assertSame(0, $company->opportunities()->available()->count());
        $this->assertSame(0, $company->opportunities()->where(fn ($query) => $query->where('status', 'open')->orWhere('publication_status', 'published'))->count());
        $this->assertSame($applications, Application::where('company_id', $company->id)->count());
    }

    public function test_account_deletion_needs_the_current_password(): void
    {
        $user = User::where('email', 'student@example.com')->firstOrFail();

        $this->actingAs($user)->delete('/profile', ['password' => 'wrong'])->assertSessionHasErrors('password');

        $this->assertNotNull(User::find($user->id));
        $this->assertNotNull(Candidate::find($user->userable_id));
    }

    public function test_profile_form_cannot_change_the_email(): void
    {
        $user = User::where('email', 'student@example.com')->firstOrFail();

        $this->actingAs($user)->put('/profile', ['name' => $user->name, 'email' => 'new-address@example.com'])->assertRedirect();

        $this->assertSame('student@example.com', $user->fresh()->email);
    }

    public function test_admin_and_profile_links_cannot_be_mass_assigned(): void
    {
        $user = new User(['name' => 'X', 'is_admin' => true, 'userable_id' => 1, 'userable_type' => 'company']);
        $this->assertNull($user->is_admin);
        $this->assertNull($user->userable_id);
        $this->assertNull($user->userable_type);

        $candidate = User::where('email', 'student@example.com')->firstOrFail();
        $this->actingAs($candidate)->put('/profile', ['name' => $candidate->name, 'email' => $candidate->email, 'is_admin' => true, 'userable_type' => 'company']);

        $this->assertFalse($candidate->fresh()->isAdmin());
        $this->assertSame('candidate', $candidate->fresh()->userable_type);
    }

    public function test_register_and_forgot_password_are_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/register', [])->assertRedirect();
            $this->post('/forgot-password', ['email' => 'nobody@example.com'])->assertRedirect();
        }

        $this->post('/register', [])->assertStatus(429);
        $this->post('/forgot-password', ['email' => 'nobody@example.com'])->assertStatus(429);
    }

    public function test_applying_is_rate_limited(): void
    {
        $candidate = User::where('email', 'student@example.com')->firstOrFail();
        $opportunity = Opportunity::firstOrFail();

        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($candidate)->post("/opportunities/{$opportunity->id}/apply", []);
        }

        $this->actingAs($candidate)->post("/opportunities/{$opportunity->id}/apply", [])->assertStatus(429);
    }

    public function test_a_failed_notification_does_not_break_applying_or_replying(): void
    {
        $this->mock(Dispatcher::class)->shouldReceive('send')->andThrow(new \RuntimeException('mail is down'));

        $candidate = User::where('email', 'student@example.com')->firstOrFail();
        $opportunity = Opportunity::where('application_mode', 'internal')->available()
            ->whereHas('company.user')
            ->whereDoesntHave('applications', fn ($query) => $query->where('candidate_id', $candidate->userable_id))
            ->firstOrFail();

        $this->actingAs($candidate)->post("/opportunities/{$opportunity->id}/apply", [
            'cover_letter' => str_repeat('I care about this role and can contribute from week one. ', 5),
        ])->assertRedirect(route('applications.index'));
        $application = Application::where('candidate_id', $candidate->userable_id)->where('opportunity_id', $opportunity->id)->firstOrFail();

        $this->actingAs($application->company->user)
            ->post("/applications/{$application->id}", ['status' => 'review'])
            ->assertRedirect();
        $this->assertSame('review', $application->fresh()->status);
    }
}

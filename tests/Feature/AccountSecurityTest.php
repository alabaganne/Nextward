<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Company;
use App\Models\Internship;
use App\Models\Student;
use App\Models\User;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
        $studentId = $user->userable_id;
        $withFile = Application::where('student_id', $studentId)->get()->first(fn ($application) => $application->attachments);
        $path = $withFile->attachments[0]['path'];
        DB::table('opportunity_views')->insert(['student_id' => $studentId, 'internship_id' => Internship::firstOrFail()->id, 'viewed_at' => now()]);

        Storage::disk('local')->assertExists($path);
        $this->assertGreaterThan(0, DB::table('saved_searches')->where('user_id', $user->id)->count());
        $this->assertGreaterThan(0, DB::table('likes')->where('student_id', $studentId)->count());
        $this->assertGreaterThan(0, $user->notifications()->count());

        $this->actingAs($user)->delete('/profile', ['password' => 'password'])->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull(User::find($user->id));
        $this->assertNull(Student::find($studentId));
        $this->assertSame(0, Application::where('student_id', $studentId)->count());
        $this->assertSame(0, DB::table('saved_searches')->where('user_id', $user->id)->count());
        $this->assertSame(0, DB::table('likes')->where('student_id', $studentId)->count());
        $this->assertSame(0, DB::table('opportunity_views')->where('student_id', $studentId)->count());
        $this->assertSame(0, DB::table('skillables')->where('skillable_type', 'student')->where('skillable_id', $studentId)->count());
        $this->assertSame(0, DB::table('notifications')->where('notifiable_id', $user->id)->count());
        $this->assertSame(0, DB::table('messages')->where('from_id', $user->id)->orWhere('to_id', $user->id)->count());
        Storage::disk('local')->assertMissing($path);
    }

    public function test_deleting_a_company_account_closes_its_roles_and_keeps_candidate_history(): void
    {
        $company = Company::whereHas('user')->whereHas('internships', fn ($query) => $query->available())->whereHas('applications')->firstOrFail();
        $user = $company->user;
        $name = $company->display_name;
        $applications = Application::where('company_id', $company->id)->count();

        $this->actingAs($user)->delete('/profile', ['password' => 'password'])->assertRedirect('/');

        $this->assertNull(User::find($user->id));
        $company = Company::findOrFail($company->id);
        $this->assertNull($company->user);
        $this->assertSame($name, $company->display_name);
        $this->assertSame(0, $company->internships()->available()->count());
        $this->assertSame(0, $company->internships()->where(fn ($query) => $query->where('status', 'open')->orWhere('publication_status', 'published'))->count());
        $this->assertSame($applications, Application::where('company_id', $company->id)->count());
    }

    public function test_account_deletion_needs_the_current_password(): void
    {
        $user = User::where('email', 'student@example.com')->firstOrFail();

        $this->actingAs($user)->delete('/profile', ['password' => 'wrong'])->assertSessionHasErrors('password');

        $this->assertNotNull(User::find($user->id));
        $this->assertNotNull(Student::find($user->userable_id));
    }

    public function test_changing_email_resets_verification(): void
    {
        $user = User::where('email', 'student@example.com')->firstOrFail();
        $user->forceFill(['email_verified_at' => now()])->save();

        $this->actingAs($user)->put('/profile', ['name' => $user->name, 'email' => $user->email])->assertRedirect();
        $this->assertNotNull($user->fresh()->email_verified_at);

        $this->actingAs($user)->put('/profile', ['name' => $user->name, 'email' => 'new-address@example.com'])->assertRedirect();
        $this->assertSame('new-address@example.com', $user->fresh()->email);
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_admin_and_profile_links_cannot_be_mass_assigned(): void
    {
        $user = new User(['name' => 'X', 'is_admin' => true, 'userable_id' => 1, 'userable_type' => 'company']);
        $this->assertNull($user->is_admin);
        $this->assertNull($user->userable_id);
        $this->assertNull($user->userable_type);

        $student = User::where('email', 'student@example.com')->firstOrFail();
        $this->actingAs($student)->put('/profile', ['name' => $student->name, 'email' => $student->email, 'is_admin' => true, 'userable_type' => 'company']);

        $this->assertFalse($student->fresh()->isAdmin());
        $this->assertSame('student', $student->fresh()->userable_type);
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
        $student = User::where('email', 'student@example.com')->firstOrFail();
        $internship = Internship::firstOrFail();

        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($student)->post("/internship/{$internship->id}/apply", []);
        }

        $this->actingAs($student)->post("/internship/{$internship->id}/apply", [])->assertStatus(429);
    }

    public function test_a_failed_notification_does_not_break_applying_or_replying(): void
    {
        $this->mock(Dispatcher::class)->shouldReceive('send')->andThrow(new \RuntimeException('mail is down'));

        $student = User::where('email', 'student@example.com')->firstOrFail();
        $internship = Internship::where('application_mode', 'internal')->available()
            ->whereHas('company.user')
            ->whereDoesntHave('applications', fn ($query) => $query->where('student_id', $student->userable_id))
            ->firstOrFail();

        $this->actingAs($student)->post("/internship/{$internship->id}/apply", [
            'cover_letter' => str_repeat('I care about this role and can contribute from week one. ', 5),
        ])->assertRedirect(route('applications.index'));
        $application = Application::where('student_id', $student->userable_id)->where('internship_id', $internship->id)->firstOrFail();

        $this->actingAs($application->company->user)
            ->post("/applications/{$application->id}", ['status' => 'review'])
            ->assertRedirect();
        $this->assertSame('review', $application->fresh()->status);
    }
}

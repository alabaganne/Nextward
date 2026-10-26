<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\CreditTransaction;
use App\Models\Opportunity;
use App\Models\User;
use Database\Seeders\DemoAccountsSeeder;
use Database\Seeders\FieldSeeder;
use Database\Seeders\SkillSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DemoAccountsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_the_demo_company_and_student_in_production_once_and_quietly(): void
    {
        $this->seed([FieldSeeder::class, SkillSeeder::class]);
        $this->app->detectEnvironment(fn () => 'production');
        Notification::fake();
        Queue::fake();

        $this->artisan('db:seed', ['--class' => DemoAccountsSeeder::class, '--force' => true])->assertSuccessful();
        $this->artisan('db:seed', ['--class' => DemoAccountsSeeder::class, '--force' => true])->assertSuccessful();

        $company = User::where('email', 'company@example.com')->sole();
        $student = User::where('email', 'student@example.com')->sole();
        foreach ([$company, $student] as $user) {
            $this->assertTrue(Hash::check('password', $user->password));
            $this->assertNotNull($user->email_verified_at);
        }
        $this->assertTrue($company->isCompany());
        $this->assertTrue($student->isCandidate());

        $listings = Opportunity::where('company_id', $company->userable_id)->get();
        $this->assertCount(5, $listings);
        $this->assertCount(4, $listings->where('publication_status', 'published')->filter->isOpen());
        $this->assertTrue($listings->every(fn ($listing) => $listing->field_id && $listing->skills()->exists()));
        $this->assertCount(4, $listings->whereNotNull('added_at'));

        $applications = Application::where('candidate_id', $student->userable_id)->get();
        $this->assertCount(3, $applications);
        $this->assertTrue($applications->every(fn ($application) => $application->company_id === $company->userable_id && $application->channel === 'internal'));
        $this->assertSame(1, CreditTransaction::where('user_id', $student->id)->count());

        Notification::assertNothingSent();
        Queue::assertNothingPushed();
        $this->assertDatabaseCount('indexing_notifications', 0);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Internship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplyFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Tests\Fixtures\DemoSeeder::class);
    }

    public function test_student_can_apply_to_an_internship(): void
    {
        $student = User::where('email', 'student@example.com')->firstOrFail();

        // An internship the demo student has not applied to yet.
        $internship = Internship::whereDoesntHave('applications', function ($query) use ($student) {
            $query->where('student_id', $student->userable->id);
        })->firstOrFail();

        $this->actingAs($student)
            ->post("/internship/{$internship->id}/apply", [
                'cover_letter' => str_repeat('I care about this role and can contribute from week one. ', 5),
                'message' => str_repeat('Available to start immediately and happy to talk. ', 3),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('applications', [
            'student_id' => $student->userable->id,
            'internship_id' => $internship->id,
            'status' => 'submitted',
        ]);
    }

    public function test_company_can_advance_an_application_to_offer(): void
    {
        $application = Application::where('status', 'submitted')->firstOrFail();
        $companyUser = $application->company->user;

        $this->actingAs($companyUser)
            ->post("/applications/{$application->id}", ['status' => 'offer'])
            ->assertRedirect();

        $this->assertSame('offer', $application->fresh()->status);
    }

    public function test_student_can_withdraw_a_pending_application(): void
    {
        $student = User::where('email', 'student@example.com')->firstOrFail();
        $application = Application::where('student_id', $student->userable->id)
            ->where('status', 'submitted')
            ->firstOrFail();

        $this->actingAs($student)
            ->delete("/applications/{$application->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('applications', ['id' => $application->id]);
    }

    public function test_students_cannot_submit_the_same_application_twice(): void
    {
        $application = Application::where('status', 'submitted')->firstOrFail();

        $this->actingAs($application->student->user)
            ->post(route('applications.store', $application->internship), [
                'cover_letter' => str_repeat('I would love to join your team. ', 10),
            ])
            ->assertForbidden();

        $this->assertSame(1, Application::where('student_id', $application->student_id)
            ->where('internship_id', $application->internship_id)->count());
    }

    public function test_only_the_owning_company_can_advance_an_application(): void
    {
        $application = Application::where('status', 'submitted')->firstOrFail();
        $otherCompany = User::where('userable_type', 'company')
            ->where('userable_id', '<>', $application->company_id)->firstOrFail();

        foreach ([$application->student->user, $otherCompany] as $user) {
            $this->actingAs($user)
                ->post(route('applications.reply', $application), ['status' => 'offer'])
                ->assertForbidden();
        }

        $this->assertSame('submitted', $application->fresh()->status);
    }

    public function test_students_can_edit_an_application_under_review(): void
    {
        $application = Application::where('status', 'review')->firstOrFail();
        $coverLetter = trim(str_repeat('Here is my updated experience for the role. ', 10));

        $this->actingAs($application->student->user)
            ->put(route('applications.update', $application), ['cover_letter' => $coverLetter])
            ->assertRedirect(route('applications.show', $application));

        $this->assertSame($coverLetter, $application->fresh()->cover_letter);
    }

    public function test_final_stage_applications_cannot_be_edited_or_withdrawn_by_students(): void
    {
        $application = Application::where('status', 'offer')->firstOrFail();

        foreach (['offer', 'closed'] as $status) {
            $application->update(['status' => $status]);

            $this->actingAs($application->student->user)
                ->put(route('applications.update', $application), [
                    'cover_letter' => str_repeat('Here is my updated experience for the role. ', 10),
                ])
                ->assertForbidden();
            $this->delete(route('applications.destroy', $application))->assertForbidden();
        }

        $this->assertDatabaseHas('applications', ['id' => $application->id, 'status' => 'closed']);
    }
}

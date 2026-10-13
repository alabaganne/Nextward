<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Internship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaveAndMessageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_student_can_toggle_a_saved_role(): void
    {
        $student = User::where('email', 'student@example.com')->firstOrFail();
        $internship = Internship::whereDoesntHave('likes', function ($query) use ($student) {
            $query->where('student_id', $student->userable->id);
        })->firstOrFail();

        $this->actingAs($student)->post("/like/{$internship->id}")->assertRedirect();
        $this->assertTrue($student->userable->fresh()->likes->contains($internship->id));

        $this->actingAs($student)->post("/like/{$internship->id}")->assertRedirect();
        $this->assertFalse($student->userable->fresh()->likes->contains($internship->id));
    }

    public function test_companies_cannot_save_roles(): void
    {
        $companyUser = Company::firstOrFail()->user;
        $internship = Internship::firstOrFail();

        $this->actingAs($companyUser)->post("/like/{$internship->id}")->assertForbidden();
    }

    public function test_student_can_message_a_company(): void
    {
        $student = User::where('email', 'student@example.com')->firstOrFail();
        $companyUser = Company::firstOrFail()->user;

        $this->actingAs($student)
            ->post('/messages', [
                'to_id' => $companyUser->id,
                'text' => 'Hello! I would love to learn more about your open roles.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('messages', [
            'from_id' => $student->id,
            'to_id' => $companyUser->id,
            'text' => 'Hello! I would love to learn more about your open roles.',
        ]);
    }

    public function test_messages_show_deep_links_into_the_inbox(): void
    {
        $student = User::where('email', 'student@example.com')->firstOrFail();
        $companyUser = Company::firstOrFail()->user;

        $this->actingAs($student)
            ->get("/messages/{$companyUser->id}")
            ->assertRedirect(route('messages.index', ['user_id' => $companyUser->id]));
    }
}

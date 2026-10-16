<?php

namespace Tests\Feature;

use App\Models\Field;
use App\Models\Internship;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SkillAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Tests\Fixtures\DemoSeeder::class);
    }

    private function admin(): User
    {
        return User::where('email', 'admin@example.com')->firstOrFail();
    }

    public function test_admin_can_list_create_update_and_delete_skills(): void
    {
        $this->actingAs($this->admin());

        $this->get(route('skills.index'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Skills/Index')
                ->has('skills.data.0.internships_count')
                ->has('skills.data.0.students_count'));
        $this->get(route('skills.create'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Skills/Edit'));

        $this->post(route('skills.store'), ['name' => '  Rust  '])->assertRedirect(route('skills.index'));
        $skill = Skill::where('name', 'Rust')->firstOrFail();

        $this->get(route('skills.edit', $skill))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Skills/Edit')->where('skill.id', $skill->id));
        $this->get(route('skills.show', $skill))->assertRedirect(route('skills.edit', $skill));

        $this->put(route('skills.update', $skill), ['name' => 'Rust lang'])->assertRedirect(route('skills.index'));
        $this->assertSame('Rust lang', $skill->fresh()->name);

        $this->put(route('skills.update', $skill), ['name' => 'Laravel'])->assertSessionHasErrors('name');
        $this->post(route('skills.store'), ['name' => ''])->assertSessionHasErrors('name');
    }

    public function test_deleting_a_skill_removes_its_tags(): void
    {
        $skill = Skill::where('name', 'Laravel')->firstOrFail();
        $this->assertTrue(DB::table('skillables')->where('skill_id', $skill->id)->exists());

        $this->actingAs($this->admin())->delete(route('skills.destroy', $skill))->assertRedirect(route('skills.index'));

        $this->assertNull(Skill::find($skill->id));
        $this->assertFalse(DB::table('skillables')->where('skill_id', $skill->id)->exists());
        $this->assertGreaterThan(0, Internship::count());
    }

    public function test_only_admins_can_manage_skills(): void
    {
        $skill = Skill::firstOrFail();

        foreach (['student@example.com', 'company@example.com'] as $email) {
            $user = User::where('email', $email)->firstOrFail();
            $this->actingAs($user)->get(route('skills.index'))->assertForbidden();
            $this->actingAs($user)->get(route('skills.create'))->assertForbidden();
            $this->actingAs($user)->post(route('skills.store'), ['name' => 'Go'])->assertForbidden();
            $this->actingAs($user)->put(route('skills.update', $skill), ['name' => 'Go'])->assertForbidden();
            $this->actingAs($user)->delete(route('skills.destroy', $skill))->assertForbidden();
        }

        $this->assertDatabaseMissing('skills', ['name' => 'Go']);
        $this->assertNotNull($skill->fresh());
    }

    public function test_field_show_page_redirects_to_the_edit_form(): void
    {
        $field = Field::firstOrFail();

        $this->actingAs($this->admin())->get(route('fields.show', $field))->assertRedirect(route('fields.edit', $field));
    }
}

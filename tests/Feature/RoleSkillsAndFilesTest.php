<?php

namespace Tests\Feature;

use App\Models\Internship;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RoleSkillsAndFilesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Tests\Fixtures\DemoSeeder::class);
        Storage::fake('public');
    }

    private function company(): User
    {
        return User::where('email', 'company@example.com')->firstOrFail();
    }

    private function role(array $data = []): array
    {
        return [
            'title' => 'Platform engineering intern',
            'description' => 'Help the platform team ship tools that other engineers use every day.',
            'kind' => 'internship',
            'publication_status' => 'draft',
            ...$data,
        ];
    }

    private function update(Internship $internship, array $data)
    {
        return $this->actingAs($this->company())->post(route('internships.update', $internship), ['_method' => 'put', ...$this->role($data)]);
    }

    private function storedPath(array $attachment): string
    {
        return substr(parse_url($attachment['url'], PHP_URL_PATH), strlen('/storage/'));
    }

    public function test_company_can_tag_skills_on_a_role(): void
    {
        $skills = Skill::orderBy('name')->take(3)->pluck('id')->all();

        $this->actingAs($this->company())->get(route('internships.create'))
            ->assertInertia(fn (Assert $page) => $page->has('skills.0.name'));

        $this->actingAs($this->company())->post(route('internships.store'), $this->role(['skills' => $skills]))->assertSessionHasNoErrors();
        $internship = Internship::where('title', 'Platform engineering intern')->firstOrFail();
        $this->assertEqualsCanonicalizing($skills, $internship->skills()->pluck('skills.id')->all());

        $this->get(route('internships.edit', $internship))
            ->assertInertia(fn (Assert $page) => $page->has('internship.skills', 3)->has('skills.0.id'));

        $this->update($internship, ['skills' => [$skills[0]]])->assertSessionHasNoErrors();
        $this->assertSame([$skills[0]], $internship->skills()->pluck('skills.id')->all());

        $this->update($internship, ['skills' => ''])->assertSessionHasNoErrors();
        $this->assertSame(0, $internship->skills()->count());
    }

    public function test_role_skills_are_validated(): void
    {
        $internship = Internship::where('company_id', $this->company()->userable_id)->firstOrFail();
        $before = $internship->skills()->pluck('skills.id')->all();

        $this->update($internship, ['skills' => Skill::take(13)->pluck('id')->all()])->assertSessionHasErrors('skills');
        $this->update($internship, ['skills' => [999999]])->assertSessionHasErrors('skills.0');

        $this->assertEqualsCanonicalizing($before, $internship->skills()->pluck('skills.id')->all());
    }

    public function test_replaced_and_removed_role_files_are_deleted(): void
    {
        $this->actingAs($this->company())->post(route('internships.store'), $this->role([
            'attachment_files' => [UploadedFile::fake()->create('brief.pdf', 20, 'application/pdf')],
        ]))->assertSessionHasNoErrors();
        $internship = Internship::where('title', 'Platform engineering intern')->firstOrFail();
        $first = $this->storedPath($internship->attachments[0]);
        Storage::disk('public')->assertExists($first);

        $this->update($internship, ['description' => 'Same role with a clearer description of the team.'])->assertSessionHasNoErrors();
        Storage::disk('public')->assertExists($first);

        $this->update($internship, ['attachment_files' => [UploadedFile::fake()->create('brief-v2.pdf', 20, 'application/pdf')]])->assertSessionHasNoErrors();
        $internship->refresh();
        $second = $this->storedPath($internship->attachments[0]);
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);

        $this->update($internship, ['remove_attachments' => '1'])->assertSessionHasNoErrors();
        $this->assertSame([], $internship->fresh()->attachments);
        Storage::disk('public')->assertMissing($second);
    }
}

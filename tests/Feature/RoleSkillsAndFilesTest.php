<?php

namespace Tests\Feature;

use App\Models\Opportunity;
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

    private function update(Opportunity $opportunity, array $data)
    {
        return $this->actingAs($this->company())->post(route('opportunities.update', $opportunity), ['_method' => 'put', ...$this->role($data)]);
    }

    private function storedPath(array $attachment): string
    {
        return substr(parse_url($attachment['url'], PHP_URL_PATH), strlen('/storage/'));
    }

    public function test_company_can_tag_skills_on_a_role(): void
    {
        $skills = Skill::orderBy('name')->take(3)->pluck('id')->all();

        $this->actingAs($this->company())->get(route('opportunities.create'))
            ->assertInertia(fn (Assert $page) => $page->has('skills.0.name'));

        $this->actingAs($this->company())->post(route('opportunities.store'), $this->role(['skills' => $skills]))->assertSessionHasNoErrors();
        $opportunity = Opportunity::where('title', 'Platform engineering intern')->firstOrFail();
        $this->assertEqualsCanonicalizing($skills, $opportunity->skills()->pluck('skills.id')->all());

        $this->get(route('opportunities.edit', $opportunity))
            ->assertInertia(fn (Assert $page) => $page->has('opportunity.skills', 3)->has('skills.0.id'));

        $this->update($opportunity, ['skills' => [$skills[0]]])->assertSessionHasNoErrors();
        $this->assertSame([$skills[0]], $opportunity->skills()->pluck('skills.id')->all());

        $this->update($opportunity, ['skills' => ''])->assertSessionHasNoErrors();
        $this->assertSame(0, $opportunity->skills()->count());
    }

    public function test_role_skills_are_validated(): void
    {
        $opportunity = Opportunity::where('company_id', $this->company()->userable_id)->firstOrFail();
        $before = $opportunity->skills()->pluck('skills.id')->all();

        $this->update($opportunity, ['skills' => Skill::take(13)->pluck('id')->all()])->assertSessionHasErrors('skills');
        $this->update($opportunity, ['skills' => [999999]])->assertSessionHasErrors('skills.0');

        $this->assertEqualsCanonicalizing($before, $opportunity->skills()->pluck('skills.id')->all());
    }

    public function test_replaced_and_removed_role_files_are_deleted(): void
    {
        $this->actingAs($this->company())->post(route('opportunities.store'), $this->role([
            'attachment_files' => [UploadedFile::fake()->create('brief.pdf', 20, 'application/pdf')],
        ]))->assertSessionHasNoErrors();
        $opportunity = Opportunity::where('title', 'Platform engineering intern')->firstOrFail();
        $first = $this->storedPath($opportunity->attachments[0]);
        Storage::disk('public')->assertExists($first);

        $this->update($opportunity, ['description' => 'Same role with a clearer description of the team.'])->assertSessionHasNoErrors();
        Storage::disk('public')->assertExists($first);

        $this->update($opportunity, ['attachment_files' => [UploadedFile::fake()->create('brief-v2.pdf', 20, 'application/pdf')]])->assertSessionHasNoErrors();
        $opportunity->refresh();
        $second = $this->storedPath($opportunity->attachments[0]);
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);

        $this->update($opportunity, ['remove_attachments' => '1'])->assertSessionHasNoErrors();
        $this->assertSame([], $opportunity->fresh()->attachments);
        Storage::disk('public')->assertMissing($second);
    }
}

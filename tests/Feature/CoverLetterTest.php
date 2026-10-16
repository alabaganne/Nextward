<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Internship;
use App\Models\User;
use Database\Seeders\OwnerProfileSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CoverLetterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Tests\Fixtures\DemoSeeder::class);
        Storage::fake('local');
        config(['importer.ai.enabled' => true, 'importer.ai.provider' => 'openai', 'ai.providers.openai.key' => 'test-key']);
    }

    public function test_a_candidate_gets_a_draft_from_their_profile_and_resume(): void
    {
        $student = User::where('email', 'student@example.com')->firstOrFail();
        $role = Internship::where('publication_status', 'published')->firstOrFail();
        Http::fake(['api.openai.com/v1/responses' => Http::response($this->reply("Dear team,\n\nI would like to join you.\n\nAla Baganne"))]);

        $this->actingAs($student)->post('/profile/resume', ['resume' => UploadedFile::fake()->create('cv.pdf', 40, 'application/pdf')])->assertRedirect();
        $this->actingAs($student)->postJson("/opportunities/{$role->id}/cover-letter")
            ->assertOk()->assertJson(['letter' => "Dear team,\n\nI would like to join you.\n\nAla Baganne", 'has_resume' => true]);

        Http::assertSent(function ($request) use ($role, $student) {
            $content = collect($request['input'])->flatMap(fn ($message) => is_array($message['content'] ?? null) ? $message['content'] : []);
            $prompt = $content->where('type', 'input_text')->pluck('text')->implode("\n");
            return $request['model'] === 'gpt-5.5' && str_contains($prompt, 'Title: '.$role->title)
                && str_contains($prompt, 'Name: '.$student->name) && $content->contains('type', 'input_file');
        });
    }

    public function test_only_candidates_can_ask_and_ai_must_be_on(): void
    {
        $role = Internship::where('publication_status', 'published')->firstOrFail();
        $this->actingAs(Company::firstOrFail()->user)->postJson("/opportunities/{$role->id}/cover-letter")->assertForbidden();

        config(['ai.providers.openai.key' => null]);
        $this->actingAs(User::where('email', 'student@example.com')->firstOrFail())
            ->postJson("/opportunities/{$role->id}/cover-letter")->assertStatus(503);
    }

    public function test_a_failed_ai_call_returns_a_friendly_error(): void
    {
        $role = Internship::where('publication_status', 'published')->firstOrFail();
        Http::fake(['api.openai.com/v1/responses' => Http::response(['error' => ['message' => 'Server error']], 500)]);

        $this->actingAs(User::where('email', 'student@example.com')->firstOrFail())
            ->postJson("/opportunities/{$role->id}/cover-letter")->assertStatus(502)->assertJsonPath('message', 'We could not write a draft right now. Please try again in a minute.');
    }

    public function test_the_resume_must_be_a_pdf_and_can_be_replaced_or_removed(): void
    {
        $student = User::where('email', 'student@example.com')->firstOrFail();

        $this->actingAs($student)->post('/profile/resume', ['resume' => UploadedFile::fake()->create('cv.docx', 40)])->assertSessionHasErrors('resume');
        $this->actingAs($student)->post('/profile/resume', ['resume' => UploadedFile::fake()->create('first.pdf', 40, 'application/pdf')]);
        $first = $student->userable->fresh()->resume_path;
        $this->actingAs($student)->post('/profile/resume', ['resume' => UploadedFile::fake()->create('second.pdf', 40, 'application/pdf')]);

        Storage::disk('local')->assertMissing($first);
        $this->assertSame('second.pdf', $student->userable->fresh()->resume_name);
        $this->actingAs($student)->get('/profile/resume')->assertOk()->assertDownload('second.pdf');

        $this->actingAs($student)->delete('/profile/resume')->assertRedirect();
        $this->assertNull($student->userable->fresh()->resume_path);
        $this->actingAs($student)->get('/profile/resume')->assertNotFound();
    }

    public function test_the_owner_profile_seeder_can_run_twice(): void
    {
        $this->seed(OwnerProfileSeeder::class);
        $this->seed(OwnerProfileSeeder::class);

        $owner = User::where('email', 'alabaganne9@gmail.com')->sole();
        $this->assertTrue($owner->isCandidate());
        $this->assertTrue(Hash::check('Pa$$w0rd', $owner->password));
        $this->assertSame('https://www.alabaganne.com', $owner->userable->website);
        Storage::disk('local')->assertExists($owner->userable->resume_path);
    }

    private function reply(string $text): array
    {
        return ['id' => 'resp_1', 'object' => 'response', 'status' => 'completed', 'model' => 'gpt-5.5',
            'output' => [['type' => 'message', 'id' => 'msg_1', 'role' => 'assistant', 'status' => 'completed',
                'content' => [['type' => 'output_text', 'text' => $text, 'annotations' => []]]]],
            'usage' => ['input_tokens' => 100, 'output_tokens' => 20, 'total_tokens' => 120]];
    }
}

<?php

namespace Tests\Feature;

use App\Models\ApplicationKit;
use App\Models\Company;
use App\Models\CreditTransaction;
use App\Models\Internship;
use App\Models\User;
use Database\Seeders\OwnerProfileSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApplicationKitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Tests\Fixtures\DemoSeeder::class);
        Storage::fake('local');
        config(['importer.ai.enabled' => true, 'importer.ai.provider' => 'openai', 'ai.providers.openai.key' => 'test-key']);
    }

    public function test_a_candidate_gets_a_tailored_cv_and_letter_in_the_chosen_language(): void
    {
        $student = $this->student();
        $role = $this->role();
        Http::fake(['api.openai.com/v1/responses' => Http::response($this->reply())]);

        $this->actingAs($student)->post('/profile/resume', ['resume' => UploadedFile::fake()->create('cv.pdf', 40, 'application/pdf')]);
        $url = $this->actingAs($student)->postJson("/opportunities/{$role->id}/kit", ['locale' => 'fr'])->assertOk()->json('url');

        $kit = ApplicationKit::sole();
        $this->assertSame(route('kits.show', $kit), $url);
        $this->assertSame('ready', $kit->status);
        $this->assertSame('fr', $kit->locale);
        $this->assertSame($student->name, $kit->cv['name']);
        $this->assertContains($student->email, $kit->cv['contact']);
        $this->assertSame('Développeur Laravel', $kit->cv['headline']);
        $this->assertSame(['Créer des API'], $kit->cv['experience'][0]['highlights']);
        $this->assertStringContainsString('Bonjour', $kit->cover_letter);
        $this->assertSame(100, $kit->input_tokens);
        Http::assertSent(function ($request) use ($role, $student) {
            $content = collect($request['input'])->flatMap(fn ($message) => is_array($message['content'] ?? null) ? $message['content'] : []);
            $prompt = $content->where('type', 'input_text')->pluck('text')->implode("\n");
            return $request['model'] === 'gpt-5.5' && str_contains($prompt, 'in French') && str_contains($prompt, 'Name: '.$student->name)
                && str_contains($prompt, 'Company: '.$role->company->display_name) && $content->contains('type', 'input_file');
        });

        // Asking again opens the same kit without a new call.
        $this->actingAs($student)->postJson("/opportunities/{$role->id}/kit", ['locale' => 'fr'])->assertOk();
        Http::assertSentCount(1);
        $this->assertSame(0, CreditTransaction::where('reason', 'kit')->count());
    }

    public function test_the_candidate_can_edit_download_and_delete_their_kit(): void
    {
        $student = $this->student();
        Http::fake(['api.openai.com/v1/responses' => Http::response($this->reply())]);
        $this->actingAs($student)->postJson("/opportunities/{$this->role()->id}/kit", ['locale' => 'en']);
        $kit = ApplicationKit::sole();

        $this->actingAs($student)->getJson(route('kits.show', $kit))->assertOk()->assertJsonPath('kit.status', 'ready');
        $this->actingAs($student)->put(route('kits.update', $kit), [
            'cover_letter' => 'Dear team, edited.',
            'cv' => ['name' => 'Sami', 'contact' => ['sami@example.com'], 'headline' => 'Backend developer', 'skills' => ['Laravel'], 'experience' => [['title' => 'Developer', 'highlights' => ['Built APIs']]]],
        ])->assertSessionHasNoErrors();
        $kit->refresh();
        $this->assertSame('Dear team, edited.', $kit->cover_letter);
        $this->assertSame('Backend developer', $kit->cv['headline']);
        $this->assertSame('Developer', $kit->cv['experience'][0]['title']);

        $this->actingAs($student)->get(route('kits.cv', $kit))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs($student)->get(route('kits.letter', $kit))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs($student)->get('/kits')->assertOk();

        $other = User::where('userable_type', 'student')->where('id', '!=', $student->id)->firstOrFail();
        $this->actingAs($other)->getJson(route('kits.show', $kit))->assertNotFound();
        $this->actingAs($other)->get(route('kits.cv', $kit))->assertNotFound();

        $this->actingAs($student)->delete(route('kits.destroy', $kit))->assertRedirect(route('kits.index'));
        $this->assertSame(0, ApplicationKit::count());
    }

    public function test_companies_cannot_ask_and_ai_must_be_on(): void
    {
        $role = $this->role();
        $this->actingAs(Company::firstOrFail()->user)->postJson("/opportunities/{$role->id}/kit", ['locale' => 'en'])->assertForbidden();

        config(['ai.providers.openai.key' => null]);
        $this->actingAs($this->student())->postJson("/opportunities/{$role->id}/kit", ['locale' => 'en'])->assertStatus(503);
    }

    public function test_a_failed_ai_call_marks_the_kit_failed_and_it_can_be_tried_again(): void
    {
        $student = $this->student();
        $role = $this->role();
        Http::fake(['api.openai.com/v1/responses' => Http::sequence()->push(['error' => ['message' => 'Server error']], 500)->push($this->reply())]);

        $this->actingAs($student)->postJson("/opportunities/{$role->id}/kit", ['locale' => 'en'])->assertOk();
        $kit = ApplicationKit::sole();
        $this->assertSame('failed', $kit->status);
        $this->assertSame(0, $kit->generations);

        $this->actingAs($student)->postJson("/opportunities/{$role->id}/kit", ['locale' => 'en'])->assertOk();
        $this->assertSame('ready', $kit->fresh()->status);
    }

    public function test_the_resume_must_be_a_pdf_and_can_be_replaced_or_removed(): void
    {
        $student = $this->student();

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

    private function student(): User
    {
        return User::where('email', 'student@example.com')->firstOrFail();
    }

    private function role(): Internship
    {
        return Internship::available()->firstOrFail();
    }

    private function reply(): array
    {
        $data = ['cv' => [
            'headline' => 'Développeur Laravel', 'summary' => 'Je crée des applications web.', 'skills' => ['Laravel', 'Vue'],
            'experience' => [['title' => 'Développeur', 'company' => 'Acme', 'location' => 'Tunis', 'start' => '2023', 'end' => '', 'highlights' => ['Créer des API']]],
            'education' => [['school' => 'ISSAT Sousse', 'degree' => 'Ingénieur', 'field' => 'Logiciel', 'start' => '2018', 'end' => '2023']],
            'projects' => [], 'languages' => ['Français', 'Anglais'],
        ], 'cover_letter' => "Bonjour,\n\nJe souhaite rejoindre votre équipe.\n\nSami"];
        return ['id' => 'resp_1', 'object' => 'response', 'status' => 'completed', 'model' => 'gpt-5.5',
            'output' => [['type' => 'message', 'id' => 'msg_1', 'role' => 'assistant', 'status' => 'completed',
                'content' => [['type' => 'output_text', 'text' => json_encode($data), 'annotations' => []]]]],
            'usage' => ['input_tokens' => 100, 'output_tokens' => 20, 'total_tokens' => 120]];
    }
}

<?php

namespace Tests\Feature;

use App\Ai\ApplicationKits;
use App\Models\ApplicationKit;
use App\Models\Company;
use App\Models\CreditTransaction;
use App\Models\Opportunity;
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
        $candidate = $this->candidate();
        $role = $this->role();
        Http::fake(['api.openai.com/v1/responses' => Http::response($this->reply())]);

        $this->actingAs($candidate)->post('/resumes', ['resume' => UploadedFile::fake()->create('cv.pdf', 40, 'application/pdf')]);
        $url = $this->actingAs($candidate)->postJson("/opportunities/{$role->id}/kit", ['locale' => 'fr'])->assertOk()->json('url');

        $kit = ApplicationKit::sole();
        $this->assertSame(route('kits.show', $kit), $url);
        $this->assertSame('ready', $kit->status);
        $this->assertSame('fr', $kit->locale);
        $this->assertSame($candidate->name, $kit->cv['name']);
        $this->assertContains($candidate->email, $kit->cv['contact']);
        $this->assertSame('Développeur Laravel', $kit->cv['headline']);
        $this->assertSame(['Créer des API'], $kit->cv['experience'][0]['highlights']);
        $this->assertStringContainsString('Bonjour', $kit->cover_letter);
        $this->assertSame(100, $kit->input_tokens);
        Http::assertSent(function ($request) use ($role, $candidate) {
            $content = collect($request['input'])->flatMap(fn ($message) => is_array($message['content'] ?? null) ? $message['content'] : []);
            $prompt = $content->where('type', 'input_text')->pluck('text')->implode("\n");
            return $request['model'] === 'gpt-5.5' && str_contains($prompt, 'in French') && str_contains($prompt, 'Name: '.$candidate->name)
                && str_contains($prompt, 'Company: '.$role->company->display_name) && $content->contains('type', 'input_file');
        });

        // Asking again opens the same kit without a new call.
        $this->actingAs($candidate)->postJson("/opportunities/{$role->id}/kit", ['locale' => 'fr'])->assertOk();
        Http::assertSentCount(1);
        $this->assertSame(0, CreditTransaction::where('reason', 'kit')->count());
    }

    public function test_the_candidate_can_edit_download_and_delete_their_kit(): void
    {
        $candidate = $this->candidate();
        Http::fake(['api.openai.com/v1/responses' => Http::response($this->reply())]);
        $this->actingAs($candidate)->postJson("/opportunities/{$this->role()->id}/kit", ['locale' => 'en']);
        $kit = ApplicationKit::sole();

        $this->actingAs($candidate)->getJson(route('kits.show', $kit))->assertOk()->assertJsonPath('kit.status', 'ready');
        $this->actingAs($candidate)->put(route('kits.update', $kit), [
            'cover_letter' => 'Dear team, edited.',
            'cv' => ['name' => 'Sami', 'contact' => ['sami@example.com'], 'headline' => 'Backend developer', 'skills' => ['Laravel'], 'experience' => [['title' => 'Developer', 'highlights' => ['Built APIs']]]],
        ])->assertSessionHasNoErrors();
        $kit->refresh();
        $this->assertSame('Dear team, edited.', $kit->cover_letter);
        $this->assertSame('Backend developer', $kit->cv['headline']);
        $this->assertSame('Developer', $kit->cv['experience'][0]['title']);

        // The listing page saves the letter alone and keeps the CV.
        $this->actingAs($candidate)->putJson(route('kits.update', $kit), ['cover_letter' => 'Dear team, edited again.'])->assertOk()->assertJson(['saved' => true]);
        $kit->refresh();
        $this->assertSame('Dear team, edited again.', $kit->cover_letter);
        $this->assertSame('Backend developer', $kit->cv['headline']);

        $this->actingAs($candidate)->get(route('kits.cv', $kit))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs($candidate)->get(route('kits.letter', $kit))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs($candidate)->get('/kits')->assertOk();

        $other = User::where('userable_type', 'candidate')->where('id', '!=', $candidate->id)->firstOrFail();
        $this->actingAs($other)->getJson(route('kits.show', $kit))->assertNotFound();
        $this->actingAs($other)->get(route('kits.cv', $kit))->assertNotFound();

        $this->actingAs($candidate)->delete(route('kits.destroy', $kit))->assertRedirect(route('kits.index'));
        $this->assertSame(0, ApplicationKit::count());
    }

    public function test_companies_cannot_ask_and_ai_must_be_on(): void
    {
        $role = $this->role();
        $this->actingAs(Company::firstOrFail()->user)->postJson("/opportunities/{$role->id}/kit", ['locale' => 'en'])->assertForbidden();

        config(['ai.providers.openai.key' => null]);
        $this->actingAs($this->candidate())->postJson("/opportunities/{$role->id}/kit", ['locale' => 'en'])->assertStatus(503);
    }

    public function test_a_failed_ai_call_marks_the_kit_failed_and_it_can_be_tried_again(): void
    {
        $candidate = $this->candidate();
        $role = $this->role();
        Http::fake(['api.openai.com/v1/responses' => Http::sequence()->push(['error' => ['message' => 'Server error']], 500)->push($this->reply())]);

        $this->actingAs($candidate)->postJson("/opportunities/{$role->id}/kit", ['locale' => 'en'])->assertOk();
        $kit = ApplicationKit::sole();
        $this->assertSame('failed', $kit->status);
        $this->assertSame(0, $kit->generations);

        $this->actingAs($candidate)->postJson("/opportunities/{$role->id}/kit", ['locale' => 'en'])->assertOk();
        $this->assertSame('ready', $kit->fresh()->status);
    }

    public function test_the_owner_profile_seeder_can_run_twice(): void
    {
        $this->seed(OwnerProfileSeeder::class);
        $this->seed(OwnerProfileSeeder::class);

        $owner = User::where('email', 'alabaganne9@gmail.com')->sole();
        $this->assertTrue($owner->isCandidate());
        $this->assertTrue(Hash::check('Pa$$w0rd', $owner->password));
        $this->assertSame('https://www.alabaganne.com', $owner->userable->website);
        $this->assertSame(1, $owner->userable->resumes()->count());
        Storage::disk('local')->assertExists($owner->userable->mainResume->path);
    }

    public function test_a_german_listing_offers_a_german_kit_with_the_original_text_in_the_prompt(): void
    {
        $candidate = $this->candidate();
        $role = $this->role();
        $role->update(['language' => 'de', 'title' => 'Backend-Entwickler (m/w/d)', 'description' => 'Wir suchen eine Laravel-Entwicklerin für unser Team in Berlin.']);
        $this->assertSame(['de', 'en', 'fr'], ApplicationKits::locales($role->fresh()));
        Http::fake(['api.openai.com/v1/responses' => Http::response($this->reply())]);

        $this->actingAs($candidate)->postJson("/opportunities/{$role->id}/kit", ['locale' => 'de'])->assertOk();

        $this->assertSame('de', ApplicationKit::sole()->locale);
        Http::assertSent(function ($request) {
            $prompt = collect($request['input'])->flatMap(fn ($message) => is_array($message['content'] ?? null) ? $message['content'] : [])
                ->where('type', 'input_text')->pluck('text')->implode("\n");
            return str_contains($prompt, 'in German') && str_contains($prompt, 'Listing language: German')
                && str_contains($prompt, 'Title: Backend-Entwickler (m/w/d)') && str_contains($prompt, 'Wir suchen eine Laravel-Entwicklerin');
        });
    }

    public function test_an_arabic_listing_offers_an_arabic_kit_with_a_right_to_left_cv(): void
    {
        $role = $this->role();
        $role->update(['language' => 'ar', 'title' => 'مطور Laravel', 'description' => 'نبحث عن مطور Laravel للانضمام إلى فريقنا في تونس.']);
        $this->assertSame(['ar', 'en', 'fr'], ApplicationKits::locales($role->fresh()));
        Http::fake(['api.openai.com/v1/responses' => Http::response($this->reply())]);

        $this->actingAs($this->candidate())->postJson("/opportunities/{$role->id}/kit", ['locale' => 'de'])->assertStatus(422);
        $this->actingAs($this->candidate())->postJson("/opportunities/{$role->id}/kit", ['locale' => 'ar'])->assertOk();

        $kit = ApplicationKit::sole();
        $this->assertSame('ar', $kit->locale);
        Http::assertSent(function ($request) {
            $prompt = collect($request['input'])->flatMap(fn ($message) => is_array($message['content'] ?? null) ? $message['content'] : [])
                ->where('type', 'input_text')->pluck('text')->implode("\n");
            return str_contains($prompt, 'in Arabic') && str_contains($prompt, 'Listing language: Arabic') && str_contains($prompt, 'نبحث عن مطور Laravel');
        });
        $this->assertStringContainsString('الخبرة المهنية', $this->actingAs($this->candidate())->get(route('kits.cv', $kit))->assertOk()->getContent());
        $this->assertStringContainsString('Cover_letter', $this->actingAs($this->candidate())->get(route('kits.letter', $kit))->assertOk()->headers->get('Content-Disposition'));
    }

    public function test_a_kit_for_a_closed_listing_cannot_be_written_again(): void
    {
        $candidate = $this->candidate();
        $role = $this->role();
        Http::fake(['api.openai.com/v1/responses' => Http::response($this->reply())]);
        $this->actingAs($candidate)->postJson("/opportunities/{$role->id}/kit", ['locale' => 'en']);
        $kit = ApplicationKit::sole();

        $role->update(['status' => 'closed']);
        $this->actingAs($candidate)->postJson(route('kits.regenerate', $kit))->assertStatus(409);
        $this->actingAs($candidate)->postJson("/opportunities/{$role->id}/kit", ['locale' => 'fr'])->assertNotFound();
        Http::assertSentCount(1);

        // The saved kit stays readable.
        $this->actingAs($candidate)->get(route('kits.cv', $kit))->assertOk();
    }

    public function test_the_kit_theme_is_saved_and_new_kits_start_from_the_main_resume_theme(): void
    {
        $candidate = $this->candidate();
        $candidate->userable->resumes()->create(['name' => 'Main', 'source' => 'profile', 'status' => 'ready', 'is_main' => true, 'style' => ['template' => 'verona', 'accent' => 'claret']]);
        Http::fake(['api.openai.com/v1/responses' => Http::response($this->reply())]);

        $this->actingAs($candidate)->postJson("/opportunities/{$this->role()->id}/kit", ['locale' => 'en']);
        $kit = ApplicationKit::sole();
        $this->assertSame(['template' => 'verona', 'accent' => 'claret', 'font' => 'sans', 'paper' => 'a4'], $kit->style);

        $this->actingAs($candidate)->putJson(route('kits.update', $kit), ['style' => ['template' => 'nope']])->assertStatus(422)->assertJsonValidationErrors('style.template');
        $this->actingAs($candidate)->putJson(route('kits.update', $kit), ['style' => ['font' => 'serif', 'paper' => 'letter']])->assertOk();
        $this->assertSame(['template' => 'verona', 'accent' => 'claret', 'font' => 'serif', 'paper' => 'letter'], $kit->fresh()->style);
        $this->actingAs($candidate)->getJson(route('kits.show', $kit))->assertJsonPath('kit.style.font', 'serif')->assertJsonPath('kit.labels.cv', 'Resume');
    }

    private function candidate(): User
    {
        return User::where('email', 'student@example.com')->firstOrFail();
    }

    private function role(): Opportunity
    {
        return Opportunity::available()->firstOrFail();
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

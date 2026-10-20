<?php

namespace Tests\Feature;

use App\Billing\Credits;
use App\Models\Candidate;
use App\Models\CreditTransaction;
use App\Models\Resume;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ResumeTranslationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Tests\Fixtures\DemoSeeder::class);
        Storage::fake('local');
        config(['importer.ai.enabled' => true, 'importer.ai.provider' => 'openai', 'ai.providers.openai.key' => 'test-key']);
        $this->user = User::where('email', 'student@example.com')->firstOrFail();
    }

    public function test_an_uploaded_pdf_is_sent_as_a_file_and_comes_back_as_a_draft(): void
    {
        $this->actingAs($this->user)->post('/app/resumes', ['resume' => UploadedFile::fake()->create('My CV.pdf', 40, 'application/pdf')]);
        $source = Resume::sole();
        Http::fake(['api.openai.com/v1/responses' => Http::response($this->reply('en'))]);

        $url = $this->actingAs($this->user)->postJson(route('resumes.translate', $source), ['locale' => 'fr'])->assertOk()->json('url');

        $draft = Resume::where('translated_from_id', $source->id)->sole();
        $this->assertSame(route('resumes.show', $draft), $url);
        $this->assertSame(['My CV (French)', 'fr', 'translation', 'draft', 1], [$draft->name, $draft->locale, $draft->source, $draft->status, $draft->generations]);
        $this->assertNull($draft->path);
        $this->assertFalse($draft->is_main);
        $this->assertSame('Développeur Laravel', $draft->content['headline']);
        $this->assertSame('Sami Ben Ali', $draft->content['name']);
        $this->assertSame(['sami@example.com'], $draft->content['contact']);
        $this->assertSame(100, $draft->input_tokens);
        // The source language is learned from the AI when it was not known.
        $this->assertSame('en', $source->fresh()->locale);
        Http::assertSent(function ($request) {
            $content = collect($request['input'])->flatMap(fn ($message) => is_array($message['content'] ?? null) ? $message['content'] : []);
            return $content->contains('type', 'input_file') && str_contains($content->where('type', 'input_text')->pluck('text')->implode("\n"), 'into French');
        });
    }

    public function test_a_resume_made_here_is_sent_as_text_and_keeps_its_name_and_contact(): void
    {
        $this->actingAs($this->user)->post('/app/resumes/from-profile', ['locale' => 'en', 'content' => [
            'name' => 'Ala Baganne', 'contact' => ['student@example.com'], 'headline' => 'Laravel developer', 'skills' => ['Laravel'],
        ]]);
        $source = Resume::sole();
        Http::fake(['api.openai.com/v1/responses' => Http::response($this->reply('en'))]);

        $this->actingAs($this->user->fresh())->postJson(route('resumes.translate', $source), ['locale' => 'fr'])->assertOk();

        $draft = Resume::where('translated_from_id', $source->id)->sole();
        $this->assertSame('Ala Baganne', $draft->content['name']);
        $this->assertSame(['student@example.com'], $draft->content['contact']);
        $this->assertSame('Développeur Laravel', $draft->content['headline']);
        Http::assertSent(function ($request) {
            $content = collect($request['input'])->flatMap(fn ($message) => is_array($message['content'] ?? null) ? $message['content'] : []);
            $prompt = $content->where('type', 'input_text')->pluck('text')->implode("\n");
            return ! $content->contains('type', 'input_file') && str_contains($prompt, '"headline":"Laravel developer"') && ! str_contains($prompt, 'student@example.com');
        });
    }

    public function test_the_draft_is_saved_after_the_candidate_checks_it(): void
    {
        $source = $this->uploaded('en');
        Http::fake(['api.openai.com/v1/responses' => Http::response($this->reply('en'))]);
        $this->actingAs($this->user)->postJson(route('resumes.translate', $source), ['locale' => 'fr']);
        $draft = Resume::where('translated_from_id', $source->id)->sole();

        $this->actingAs($this->user)->post(route('resumes.main', $draft))->assertStatus(409);
        $this->actingAs($this->user)->putJson(route('resumes.update', $draft), [
            'content' => [...$draft->content, 'headline' => 'Développeuse Laravel'],
            'locale' => 'en',
            'main' => true,
        ])->assertOk()->assertJsonPath('resume.status', 'ready');

        $draft->refresh();
        $this->assertSame('ready', $draft->status);
        $this->assertSame('Développeuse Laravel', $draft->content['headline']);
        // A translation keeps its language.
        $this->assertSame('fr', $draft->locale);
        $this->assertTrue($draft->is_main);
        $this->assertFalse($source->fresh()->is_main);
        $this->assertSame('Sami_Ben_Ali_CV.pdf', $draft->file_name);
        Storage::disk('local')->assertExists($draft->path);
    }

    public function test_asking_for_the_same_language_twice_makes_one_call(): void
    {
        $source = $this->uploaded('en');
        Http::fake(['api.openai.com/v1/responses' => Http::response($this->reply('en'))]);

        $first = $this->actingAs($this->user)->postJson(route('resumes.translate', $source), ['locale' => 'fr'])->assertOk()->json('url');
        $second = $this->actingAs($this->user)->postJson(route('resumes.translate', $source), ['locale' => 'fr'])->assertOk()->json('url');

        $this->assertSame($first, $second);
        Http::assertSentCount(1);
        $this->assertSame(1, Resume::where('translated_from_id', $source->id)->count());
    }

    public function test_bad_targets_and_ai_being_off_are_refused(): void
    {
        $source = $this->uploaded('en');
        Http::fake();

        $this->actingAs($this->user)->postJson(route('resumes.translate', $source), ['locale' => 'en'])->assertStatus(422);
        $this->actingAs($this->user)->postJson(route('resumes.translate', $source), ['locale' => 'de'])->assertStatus(422)->assertJsonValidationErrors('locale');
        $this->actingAs($this->user)->postJson(route('resumes.translate', $this->uploaded(null, 'draft')), ['locale' => 'fr'])->assertStatus(409);
        $this->actingAs(User::where('email', 'professional@example.com')->firstOrFail())->postJson(route('resumes.translate', $source), ['locale' => 'fr'])->assertNotFound();

        config(['ai.providers.openai.key' => null]);
        $this->actingAs($this->user)->postJson(route('resumes.translate', $source), ['locale' => 'fr'])->assertStatus(503);
        Http::assertNothingSent();
        $this->assertSame(0, Resume::where('source', 'translation')->count());
    }

    public function test_translating_needs_a_verified_email(): void
    {
        $source = $this->uploaded('en');
        $this->user->forceFill(['email_verified_at' => null])->save();

        $this->actingAs($this->user)->postJson(route('resumes.translate', $source), ['locale' => 'fr'])->assertForbidden();
    }

    public function test_a_failed_translation_gives_the_credit_back(): void
    {
        config(['billing.enabled' => true, 'billing.signup_credits' => 1]);
        $user = $this->buyer();
        $source = $this->uploaded('en', 'ready', $user->userable);
        Http::fake(['api.openai.com/v1/responses' => Http::sequence()->push(['error' => ['message' => 'Server error']], 500)->push($this->reply('en'))]);

        $this->actingAs($user)->postJson(route('resumes.translate', $source), ['locale' => 'fr'])->assertOk();

        $draft = Resume::where('translated_from_id', $source->id)->sole();
        $this->assertSame('failed', $draft->status);
        $this->assertSame(0, $draft->generations);
        $this->assertSame(0, $draft->credits_used);
        $this->assertSame(1, app(Credits::class)->balance($user));
        $this->assertSame(1, CreditTransaction::where('reason', 'refund')->where('resume_id', $draft->id)->count());

        // Trying again uses the credit for real.
        $this->actingAs($user)->postJson(route('resumes.translate', $source), ['locale' => 'fr'])->assertOk();
        $this->assertSame('draft', $draft->fresh()->status);
        $this->assertSame(0, app(Credits::class)->balance($user));
    }

    public function test_with_no_credit_left_nothing_is_written(): void
    {
        config(['billing.enabled' => true, 'billing.signup_credits' => 0]);
        $user = $this->buyer();
        $source = $this->uploaded('en', 'ready', $user->userable);
        Http::fake();

        $this->actingAs($user)->postJson(route('resumes.translate', $source), ['locale' => 'fr'])->assertStatus(402)->assertJsonPath('billing_url', route('billing.index'));

        Http::assertNothingSent();
        $this->assertSame(0, Resume::where('source', 'translation')->count());
    }

    public function test_a_draft_can_be_written_again_within_its_credit(): void
    {
        config(['billing.enabled' => true, 'billing.signup_credits' => 1, 'billing.rewrites_per_kit' => 1]);
        $user = $this->buyer();
        $source = $this->uploaded('en', 'ready', $user->userable);
        Http::fake(['api.openai.com/v1/responses' => Http::response($this->reply('en'))]);
        $this->actingAs($user)->postJson(route('resumes.translate', $source), ['locale' => 'fr']);
        $draft = Resume::where('translated_from_id', $source->id)->sole();

        $this->actingAs($user)->postJson(route('resumes.regenerate', $draft))->assertOk();
        $this->actingAs($user)->postJson(route('resumes.regenerate', $draft))->assertStatus(402);
        $this->assertSame(2, $draft->fresh()->generations);
        $this->assertSame(1, $draft->fresh()->credits_used);

        $this->actingAs($user)->postJson(route('resumes.regenerate', $source))->assertStatus(409);
    }

    public function test_an_uploaded_pdf_gets_an_editable_copy_in_its_own_language(): void
    {
        $source = $this->uploaded(null);
        Http::fake(['api.openai.com/v1/responses' => Http::response($this->reply('fr'))]);

        $url = $this->actingAs($this->user)->postJson(route('resumes.copy', $source))->assertOk()->json('url');

        $copy = Resume::where('translated_from_id', $source->id)->sole();
        $this->assertSame(route('resumes.show', $copy), $url);
        $this->assertSame(['My CV (editable)', 'copy', 'draft', 'fr'], [$copy->name, $copy->source, $copy->status, $copy->locale]);
        $this->assertTrue($copy->isEditable());
        $this->assertSame('Développeur Laravel', $copy->content['headline']);
        $this->assertSame('fr', $source->fresh()->locale);
        Http::assertSent(function ($request) {
            $content = collect($request['input'])->flatMap(fn ($message) => is_array($message['content'] ?? null) ? $message['content'] : []);
            $text = $content->where('type', 'input_text')->pluck('text')->implode("\n").json_encode($request['instructions'] ?? '');
            return $content->contains('type', 'input_file') && str_contains($text, 'in the language it is written in') && ! str_contains($text, 'Translate the attached');
        });

        // Asking again opens the same copy, and saving it lays it out.
        $this->actingAs($this->user)->postJson(route('resumes.copy', $source))->assertOk()->assertJsonPath('url', $url);
        Http::assertSentCount(1);
        $this->actingAs($this->user)->putJson(route('resumes.update', $copy), ['content' => $copy->content])->assertOk()->assertJsonPath('resume.status', 'ready');
        Storage::disk('local')->assertExists($copy->fresh()->path);
    }

    public function test_only_ready_uploaded_pdfs_can_be_copied(): void
    {
        $this->actingAs($this->user)->post('/app/resumes/from-profile', ['locale' => 'en']);
        Http::fake();

        $this->actingAs($this->user->fresh())->postJson(route('resumes.copy', Resume::sole()))->assertStatus(409);
        $this->actingAs($this->user)->postJson(route('resumes.copy', $this->uploaded('en', 'draft')))->assertStatus(409);
        $this->actingAs(User::where('email', 'professional@example.com')->firstOrFail())->postJson(route('resumes.copy', $this->uploaded('en')))->assertNotFound();
        config(['ai.providers.openai.key' => null]);
        $this->actingAs($this->user)->postJson(route('resumes.copy', $this->uploaded('en')))->assertStatus(503);
        Http::assertNothingSent();
    }

    public function test_a_failed_copy_gives_the_credit_back_and_can_be_read_again(): void
    {
        config(['billing.enabled' => true, 'billing.signup_credits' => 1]);
        $user = $this->buyer();
        $source = $this->uploaded('en', 'ready', $user->userable);
        Http::fake(['api.openai.com/v1/responses' => Http::sequence()->push(['error' => ['message' => 'Server error']], 500)->push($this->reply('en'))]);

        $this->actingAs($user)->postJson(route('resumes.copy', $source))->assertOk();
        $copy = Resume::where('translated_from_id', $source->id)->sole();
        $this->assertSame('failed', $copy->status);
        $this->assertSame('We could not read your resume right now. Please try again in a minute.', $copy->error);
        $this->assertSame(1, app(Credits::class)->balance($user));

        $this->actingAs($user)->postJson(route('resumes.regenerate', $copy))->assertOk();
        $this->assertSame('draft', $copy->fresh()->status);
        $this->assertSame(0, app(Credits::class)->balance($user));
    }

    private function uploaded(?string $locale, string $status = 'ready', ?Candidate $candidate = null): Resume
    {
        $path = 'resumes/'.str()->random(10).'.pdf';
        Storage::disk('local')->put($path, '%PDF-1.4');

        return Resume::create([
            'candidate_id' => ($candidate ?? $this->user->userable)->id, 'name' => 'My CV', 'locale' => $locale, 'source' => 'uploaded',
            'status' => $status, 'path' => $path, 'file_name' => 'cv.pdf', 'is_main' => true,
        ]);
    }

    private function buyer(): User
    {
        $user = Candidate::create()->user()->create(['name' => 'Buyer Name', 'email' => 'buyer@example.com', 'password' => 'password']);
        $user->forceFill(['email_verified_at' => now()])->save();

        return $user;
    }

    private function reply(string $sourceLanguage): array
    {
        $data = ['cv' => [
            'name' => 'Sami Ben Ali', 'contact' => ['sami@example.com'], 'headline' => 'Développeur Laravel', 'summary' => 'Je crée des applications web.',
            'skills' => ['Laravel', 'Vue'],
            'experience' => [['title' => 'Développeur', 'company' => 'Acme', 'location' => 'Tunis', 'start' => '2023-01', 'end' => '', 'current' => true, 'highlights' => ['Créer des API']]],
            'education' => [['school' => 'ISSAT Sousse', 'degree' => 'Ingénieur', 'field' => 'Logiciel', 'start' => '2018', 'end' => '2023']],
            'projects' => [], 'languages' => ['Français', 'Anglais'],
        ], 'source_language' => $sourceLanguage];

        return ['id' => 'resp_1', 'object' => 'response', 'status' => 'completed', 'model' => 'gpt-5.4-mini',
            'output' => [['type' => 'message', 'id' => 'msg_1', 'role' => 'assistant', 'status' => 'completed',
                'content' => [['type' => 'output_text', 'text' => json_encode($data), 'annotations' => []]]]],
            'usage' => ['input_tokens' => 100, 'output_tokens' => 20, 'total_tokens' => 120]];
    }
}

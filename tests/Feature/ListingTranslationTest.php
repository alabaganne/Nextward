<?php

namespace Tests\Feature;

use App\Ai\ListingTranslations;
use App\Models\Company;
use App\Models\Opportunity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ListingTranslationTest extends TestCase
{
    use RefreshDatabase;

    private string $english = "We are hiring a backend developer to join our team.\n\n• Build APIs with Laravel\n• Work with the product team on new features for our customers.";

    protected function setUp(): void
    {
        parent::setUp();
        config(['importer.ai.enabled' => true, 'importer.ai.translate' => true, 'importer.ai.provider' => 'openai', 'ai.providers.openai.key' => 'test-key']);
    }

    public function test_publishing_an_english_listing_writes_a_french_version_and_keeps_the_original(): void
    {
        Http::fake(['api.openai.com/v1/responses' => Http::response($this->reply([$this->answer(1, 'en', ['fr' => ['Développeur backend',
            "Nous recrutons un développeur backend pour rejoindre notre équipe.\n\n• Créer des API avec Laravel\n• Travailler avec l’équipe produit."]])]))]);

        $listing = $this->listing('Backend developer', $this->english);

        $this->assertSame('en', $listing->fresh()->language);
        $this->assertSame('Backend developer', $listing->fresh()->title);
        $french = $listing->translations()->sole();
        $this->assertSame('fr', $french->locale);
        $this->assertSame('Développeur backend', $french->title);
        $this->assertSame('developpeur-backend', $french->slug);
        $this->assertSame($listing->fresh()->sourceHash(), $french->source_hash);
        Http::assertSentCount(1);
        $this->assertSame(['en', 'fr'], $listing->fresh()->availableLocales());
    }

    public function test_a_listing_is_translated_again_only_when_its_text_changes(): void
    {
        Http::fake(['api.openai.com/v1/responses' => Http::sequence()
            ->push($this->reply([$this->answer(1, 'en', ['fr' => ['Développeur backend', str_repeat('Texte traduit. ', 10)]])]))
            ->push($this->reply([$this->answer(1, 'en', ['fr' => ['Développeur backend senior', str_repeat('Nouveau texte. ', 10)]])]))]);

        $listing = $this->listing('Backend developer', $this->english);
        $listing->update(['last_verified_at' => now()]);
        $this->assertSame(0, app(ListingTranslations::class)->translate($listing->fresh()));
        Http::assertSentCount(1);

        $listing->update(['title' => 'Senior backend developer']);
        Http::assertSentCount(2);
        $this->assertSame('Développeur backend senior', $listing->translations()->sole()->title);
    }

    public function test_an_unknown_language_is_found_and_both_site_languages_are_written_in_one_call(): void
    {
        Http::fake(['api.openai.com/v1/responses' => Http::response($this->reply([$this->answer(1, 'de', [
            'en' => ['Backend developer', str_repeat('English text. ', 10)],
            'fr' => ['Développeur backend', str_repeat('Texte français. ', 10)],
        ])]))]);

        $listing = $this->listing('Backend-Entwickler', str_repeat('Wir suchen einen Entwickler für unser Team. ', 5));

        $this->assertSame('de', $listing->fresh()->language);
        $this->assertEqualsCanonicalizing(['en', 'fr'], $listing->translations()->pluck('locale')->all());
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => str_contains(json_encode($request->data()), 'Translate into: en, fr'));
    }

    public function test_nothing_is_sent_when_translation_is_off(): void
    {
        Http::fake();
        config(['importer.ai.translate' => false]);

        $listing = $this->listing('Backend developer', $this->english);

        Http::assertNothingSent();
        $this->assertSame(0, $listing->translations()->count());
        $this->artisan('opportunities:translate')->expectsOutputToContain('Translations are off')->assertSuccessful();
    }

    public function test_the_backfill_command_translates_open_listings_that_are_missing_a_version(): void
    {
        config(['importer.ai.translate' => false]);
        $listing = $this->listing('Backend developer', $this->english);
        config(['importer.ai.translate' => true]);
        Http::fake(['api.openai.com/v1/responses' => Http::response($this->reply([$this->answer(1, 'en', ['fr' => ['Développeur backend', str_repeat('Texte traduit. ', 10)]])]))]);

        $this->artisan('opportunities:translate', ['--sync' => true])->expectsOutputToContain('Translated 1 listing(s) in 1 AI call(s).')->assertSuccessful();
        $this->assertSame('Développeur backend', $listing->translations()->sole()->title);
        $this->artisan('opportunities:translate', ['--sync' => true])->expectsOutputToContain('Translated 0 listing(s)');
    }

    public function test_imported_listings_wait_for_the_batch_run_and_share_one_call(): void
    {
        Http::fake(['api.openai.com/v1/responses' => Http::response($this->reply([
            $this->answer(1, 'en', ['fr' => ['Développeur trois', str_repeat('Texte trois. ', 10)]]),
            $this->answer(2, 'en', ['fr' => ['Développeur deux', str_repeat('Texte deux. ', 10)]]),
            // The answer for listing 3 is missing, so it stays out of date for the next run.
        ]))]);
        $listings = collect(['one', 'two', 'three'])->map(fn ($name) => $this->listing("Backend developer {$name}", $this->english, 'imported'));
        Http::assertNothingSent();

        $this->artisan('opportunities:translate', ['--sync' => true])->expectsOutputToContain('Translated 3 listing(s) in 1 AI call(s).')->assertSuccessful();

        Http::assertSentCount(1);
        $this->assertEqualsCanonicalizing(['Développeur trois', 'Développeur deux'], \App\Models\OpportunityTranslation::pluck('title')->all());
        $this->assertSame(1, $listings->filter(fn ($listing) => app(ListingTranslations::class)->needsWork($listing->fresh()))->count());
    }

    private function listing(string $title, string $description, string $origin = 'native'): Opportunity
    {
        return Opportunity::create([
            'title' => $title, 'description' => $description, 'company_id' => Company::create(['name' => 'Atlas Cloud'])->id,
            'closing_at' => today()->addMonth(), 'origin' => $origin,
        ]);
    }

    private function answer(int $id, string $source, array $translations): array
    {
        return ['id' => $id, 'source_language' => $source, 'translations' => collect($translations)
            ->map(fn ($text, $locale) => ['locale' => $locale, 'title' => $text[0], 'description' => $text[1]])->values()->all()];
    }

    private function reply(array $listings): array
    {
        $data = ['listings' => $listings];
        return ['id' => 'resp_1', 'object' => 'response', 'status' => 'completed', 'model' => 'gpt-5.4-mini',
            'output' => [['type' => 'message', 'id' => 'msg_1', 'role' => 'assistant', 'status' => 'completed',
                'content' => [['type' => 'output_text', 'text' => json_encode($data), 'annotations' => []]]]],
            'usage' => ['input_tokens' => 100, 'output_tokens' => 20, 'total_tokens' => 120]];
    }
}

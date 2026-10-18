<?php

namespace Tests\Feature;

use App\Ai\ListingTranslations;
use App\Models\Company;
use App\Models\Internship;
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
        Http::fake(['api.openai.com/v1/responses' => Http::response($this->reply([
            'source_language' => 'en', 'title' => 'Développeur backend',
            'description' => "Nous recrutons un développeur backend pour rejoindre notre équipe.\n\n• Créer des API avec Laravel\n• Travailler avec l’équipe produit.",
        ]))]);

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
            ->push($this->reply(['source_language' => 'en', 'title' => 'Développeur backend', 'description' => str_repeat('Texte traduit. ', 10)]))
            ->push($this->reply(['source_language' => 'en', 'title' => 'Développeur backend senior', 'description' => str_repeat('Nouveau texte. ', 10)]))]);

        $listing = $this->listing('Backend developer', $this->english);
        $listing->update(['last_verified_at' => now()]);
        $this->assertSame(0, app(ListingTranslations::class)->translate($listing->fresh()));
        Http::assertSentCount(1);

        $listing->update(['title' => 'Senior backend developer']);
        Http::assertSentCount(2);
        $this->assertSame('Développeur backend senior', $listing->translations()->sole()->title);
    }

    public function test_an_unknown_language_is_found_and_both_site_languages_are_written(): void
    {
        Http::fake(['api.openai.com/v1/responses' => Http::sequence()
            ->push($this->reply(['source_language' => 'de', 'title' => 'Backend developer', 'description' => str_repeat('English text. ', 10)]))
            ->push($this->reply(['source_language' => 'de', 'title' => 'Développeur backend', 'description' => str_repeat('Texte français. ', 10)]))]);

        $listing = $this->listing('Backend-Entwickler', str_repeat('Wir suchen einen Entwickler für unser Team. ', 5));

        $this->assertSame('de', $listing->fresh()->language);
        $this->assertEqualsCanonicalizing(['en', 'fr'], $listing->translations()->pluck('locale')->all());
        Http::assertSentCount(2);
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
        Http::fake(['api.openai.com/v1/responses' => Http::response($this->reply(['source_language' => 'en', 'title' => 'Développeur backend', 'description' => str_repeat('Texte traduit. ', 10)]))]);

        $this->artisan('opportunities:translate', ['--sync' => true])->expectsOutputToContain('Translated 1 listing(s).')->assertSuccessful();
        $this->assertSame('Développeur backend', $listing->translations()->sole()->title);
        $this->artisan('opportunities:translate', ['--sync' => true])->expectsOutputToContain('Translated 0 listing(s).');
    }

    private function listing(string $title, string $description): Internship
    {
        return Internship::create([
            'title' => $title, 'description' => $description, 'company_id' => Company::create(['name' => 'Atlas Cloud'])->id,
            'closing_at' => today()->addMonth(),
        ]);
    }

    private function reply(array $data): array
    {
        return ['id' => 'resp_1', 'object' => 'response', 'status' => 'completed', 'model' => 'gpt-5.4-mini',
            'output' => [['type' => 'message', 'id' => 'msg_1', 'role' => 'assistant', 'status' => 'completed',
                'content' => [['type' => 'output_text', 'text' => json_encode($data), 'annotations' => []]]]],
            'usage' => ['input_tokens' => 100, 'output_tokens' => 20, 'total_tokens' => 120]];
    }
}

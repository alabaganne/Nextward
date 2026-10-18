<?php

namespace App\Ai;

use App\Ai\Agents\ListingTranslator;
use App\Models\Opportunity;
use App\Support\LanguageGuess;
use Illuminate\Support\Str;

// Keeps an English and a French version of each listing; the original text stays on the listing itself.
class ListingTranslations
{
    public const LOCALES = ['en', 'fr'];

    public function __construct(private OpportunityClassifier $ai) {}

    public function enabled(): bool
    {
        return (bool) config('importer.ai.translate') && $this->ai->enabled();
    }

    public function needsWork(Opportunity $opportunity): bool
    {
        $opportunity->loadMissing('translations');
        $hash = $opportunity->sourceHash();
        foreach (self::LOCALES as $locale) {
            if ($opportunity->originalLocale() !== $locale && $opportunity->translation($locale)?->source_hash !== $hash) {
                return true;
            }
        }
        return false;
    }

    // Returns how many translations were written.
    public function translate(Opportunity $opportunity): int
    {
        if (! $this->enabled() || mb_strlen((string) $opportunity->description) > 40000) {
            return 0;
        }
        $opportunity->loadMissing('translations');
        $original = $opportunity->originalLocale() ?? LanguageGuess::guess($opportunity->title."\n".$opportunity->description);
        $hash = $opportunity->sourceHash();
        $written = 0;

        foreach (self::LOCALES as $locale) {
            if ($locale === $original) {
                continue;
            }
            if ($opportunity->translations->firstWhere('locale', $locale)?->source_hash === $hash) {
                continue;
            }
            $response = (new ListingTranslator($locale))->prompt(
                "Title: {$opportunity->title}\n\nDescription:\n{$opportunity->description}",
                provider: $this->ai->provider(),
                model: $this->ai->model('review'),
            );
            $result = $response->toArray();
            $source = LanguageGuess::normalize($result['source_language'] ?? null);
            if ($source === $locale) {
                // The listing was already in this language, so there is nothing to keep.
                $original = $locale;
                continue;
            }
            $original ??= $source;
            $title = Str::limit(trim((string) ($result['title'] ?? '')), 250, '');
            $description = trim((string) ($result['description'] ?? ''));
            if ($title === '' || mb_strlen($description) < 50) {
                continue;
            }
            $opportunity->translations()->updateOrCreate(['locale' => $locale], [
                'title' => $title,
                'description' => $description,
                'slug' => Str::limit(Str::slug($title), 180, '') ?: $opportunity->slug,
                'source_hash' => $hash,
                'model' => $response->meta->model ?? $this->ai->model('review'),
            ]);
            $written++;
        }

        if ($original) {
            $opportunity->translations()->where('locale', $original)->delete();
            if (! $opportunity->originalLocale()) {
                $opportunity->updateQuietly(['language' => $original]);
            }
        }
        $opportunity->unsetRelation('translations');

        return $written;
    }
}

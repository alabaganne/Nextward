<?php

namespace App\Ai;

use App\Ai\Agents\ListingTranslator;
use App\Models\Opportunity;
use App\Support\LanguageGuess;
use Illuminate\Support\Facades\Log;
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
        return $this->translateMany([$opportunity]);
    }

    // Translates several listings with as few AI calls as possible; returns how many translations were written.
    public function translateMany(iterable $opportunities): int
    {
        if (! $this->enabled()) {
            return 0;
        }
        $written = 0;
        foreach ($this->packs($opportunities) as $pack) {
            try {
                $written += $this->translatePack($pack);
            } catch (\Throwable $e) {
                // The next opportunities:translate run tries again.
                Log::warning('Listing translation failed.', ['opportunity_ids' => array_map(fn ($opportunity) => $opportunity->id, $pack), 'error' => Str::limit($e->getMessage(), 300)]);
            }
        }
        return $written;
    }

    // Groups listings that need work into packs of about importer.ai.translate_batch_chars of text, five listings at most.
    public function packs(iterable $opportunities): array
    {
        $packs = [];
        $pack = [];
        $size = 0;
        $max = max(1000, (int) config('importer.ai.translate_batch_chars', 12000));
        foreach ($opportunities as $opportunity) {
            if (mb_strlen((string) $opportunity->description) > 40000 || ! $targets = $this->targets($opportunity)) {
                continue;
            }
            $length = mb_strlen($opportunity->title.$opportunity->description) * count($targets);
            if ($pack && ($size + $length > $max || count($pack) >= 5)) {
                $packs[] = $pack;
                [$pack, $size] = [[], 0];
            }
            $pack[] = $opportunity;
            $size += $length;
        }
        return $pack ? [...$packs, $pack] : $packs;
    }

    // The site languages this listing still needs, leaving out its own language and versions that are up to date.
    private function targets(Opportunity $opportunity): array
    {
        $opportunity->loadMissing('translations');
        $original = $opportunity->originalLocale() ?? LanguageGuess::guess($opportunity->title."\n".$opportunity->description);
        $hash = $opportunity->sourceHash();
        return array_values(array_filter(self::LOCALES, fn ($locale) => $locale !== $original
            && $opportunity->translations->firstWhere('locale', $locale)?->source_hash !== $hash));
    }

    private function translatePack(array $pack): int
    {
        $targets = array_map(fn ($opportunity) => $this->targets($opportunity), $pack);
        $prompt = implode("\n\n", array_map(fn ($opportunity, $index) => 'Listing '.($index + 1)."\nTranslate into: ".implode(', ', $targets[$index])
            ."\nTitle: {$opportunity->title}\n\nDescription:\n{$opportunity->description}", $pack, array_keys($pack)));
        $response = (new ListingTranslator())->prompt($prompt, provider: $this->ai->provider(), model: $this->ai->model('review'));
        $model = $response->meta->model ?? $this->ai->model('review');
        $written = 0;
        foreach ($response->toArray()['listings'] ?? [] as $result) {
            $index = ($result['id'] ?? 0) - 1;
            if (! isset($pack[$index])) {
                continue;
            }
            $written += $this->save($pack[$index], $targets[$index], $result, $model);
        }
        return $written;
    }

    private function save(Opportunity $opportunity, array $targets, array $result, ?string $model): int
    {
        $original = $opportunity->originalLocale() ?? LanguageGuess::guess($opportunity->title."\n".$opportunity->description);
        $source = LanguageGuess::normalize($result['source_language'] ?? null);
        $original = in_array($source, $targets, true) ? $source : ($original ?? $source);
        $hash = $opportunity->sourceHash();
        $written = 0;
        foreach ($result['translations'] ?? [] as $translation) {
            $locale = $translation['locale'] ?? null;
            // A listing already in this language has nothing to keep.
            if (! in_array($locale, $targets, true) || $locale === $source) {
                continue;
            }
            $title = Str::limit(trim((string) ($translation['title'] ?? '')), 250, '');
            $description = trim((string) ($translation['description'] ?? ''));
            if ($title === '' || mb_strlen($description) < 50) {
                continue;
            }
            $opportunity->translations()->updateOrCreate(['locale' => $locale], [
                'title' => $title,
                'description' => $description,
                'slug' => Str::limit(Str::slug($title), 180, '') ?: $opportunity->slug,
                'source_hash' => $hash,
                'model' => $model,
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

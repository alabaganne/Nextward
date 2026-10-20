<?php

namespace App\Console\Commands;

use App\Ai\ListingTranslations;
use App\Jobs\TranslateOpportunityPack;
use App\Models\Opportunity;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class TranslateOpportunities extends Command
{
    protected $signature = 'opportunities:translate {--limit=200 : Most listings to queue} {--sync : Translate now instead of queueing}';
    protected $description = 'Write the missing English and French versions of published listings, a few listings per AI call';

    public function handle(ListingTranslations $translations): int
    {
        if (! $translations->enabled()) {
            $this->info('Translations are off: AI is disabled or has no key.');
            return self::SUCCESS;
        }
        $limit = max(1, (int) $this->option('limit'));
        $sync = (bool) $this->option('sync');
        $todo = collect();
        Opportunity::available()->with('translations')->chunkByIdDesc(200, function ($rows) use ($translations, $limit, $sync, $todo) {
            foreach ($rows as $opportunity) {
                if ($todo->count() >= $limit) { return false; }
                // A listing already queued in the last 15 minutes is left to that job.
                if (! $translations->needsWork($opportunity) || (! $sync && ! Cache::add('translating-opportunity:'.$opportunity->id, true, 900))) { continue; }
                $todo->push($opportunity);
            }
        });
        $packs = $translations->packs($todo);
        foreach ($packs as $pack) {
            $ids = array_map(fn ($opportunity) => $opportunity->id, $pack);
            $sync ? TranslateOpportunityPack::dispatchSync($ids) : TranslateOpportunityPack::dispatch($ids);
        }
        $this->info(($sync ? 'Translated' : 'Queued')." {$todo->count()} listing(s) in ".count($packs).' AI call(s).');
        return self::SUCCESS;
    }
}

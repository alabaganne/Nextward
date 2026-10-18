<?php

namespace App\Console\Commands;

use App\Ai\ListingTranslations;
use App\Jobs\TranslateOpportunity;
use App\Models\Internship;
use Illuminate\Console\Command;

class TranslateOpportunities extends Command
{
    protected $signature = 'opportunities:translate {--limit=200 : Most listings to queue} {--sync : Translate now instead of queueing}';
    protected $description = 'Write the missing English and French versions of published listings';

    public function handle(ListingTranslations $translations): int
    {
        if (! $translations->enabled()) {
            $this->info('Translations are off: AI is disabled or has no key.');
            return self::SUCCESS;
        }
        $limit = max(1, (int) $this->option('limit'));
        $count = 0;
        Internship::available()->with('translations')->chunkByIdDesc(200, function ($rows) use ($translations, $limit, &$count) {
            foreach ($rows as $opportunity) {
                if ($count >= $limit) { return false; }
                if (! $translations->needsWork($opportunity)) { continue; }
                $this->option('sync') ? TranslateOpportunity::dispatchSync($opportunity->id) : TranslateOpportunity::dispatch($opportunity->id);
                $count++;
            }
        });
        $this->info(($this->option('sync') ? 'Translated' : 'Queued')." {$count} listing(s).");
        return self::SUCCESS;
    }
}

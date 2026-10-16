<?php

namespace App\Console\Commands;

use App\Ai\OpportunityClassifier;
use App\Ai\SourceDiscovery;
use Illuminate\Console\Command;

class DiscoverOpportunitySources extends Command
{
    protected $signature = 'opportunities:discover {--max=10 : How many new sources to look for}';
    protected $description = 'Ask the AI to search the web for new job boards and save the ones that check out for approval in Imports';

    public function handle(SourceDiscovery $discovery, OpportunityClassifier $ai): int
    {
        if (! $discovery->enabled()) {
            $this->info('AI is off. Set '.$ai->keyName().' to look for new sources.');
            return self::SUCCESS;
        }
        $max = max(1, min(20, (int) $this->option('max')));
        try {
            $results = $discovery->run($max);
        } catch (\Throwable $e) {
            $this->error('Source search stopped: '.$e->getMessage());
            return self::FAILURE;
        }
        if ($results) {
            $this->table(['Result', 'Source', 'Note'], $results);
        }
        $added = collect($results)->where(0, 'added')->count();
        $this->info("{$added} new ".str('source')->plural($added).' saved for review in Imports. They stay off until you approve them.');
        $this->line(sprintf('Used %d input tokens, %d output tokens and %d web searches.', ...array_values($discovery->usage)));
        return self::SUCCESS;
    }
}

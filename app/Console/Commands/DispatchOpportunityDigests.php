<?php

namespace App\Console\Commands;

use App\Jobs\SendOpportunityDigests;
use App\Models\SavedSearch;
use Illuminate\Console\Command;

class DispatchOpportunityDigests extends Command
{
    protected $signature = 'opportunities:digests {--dry-run : Count due subscriptions without queueing or sending}';
    protected $description = 'Queue opted-in opportunity digests when due in each candidate timezone';

    public function handle(): int
    {
        if (! config('importer.digests_enabled') && ! $this->option('dry-run')) {
            $this->info('Email digests are disabled. Set OPPORTUNITY_DIGESTS_ENABLED after configuring the mail provider.');
            return self::SUCCESS;
        }
        $users = [];
        SavedSearch::whereIn('frequency', ['daily', 'weekly'])->whereNotNull('subscribed_at')
            ->with('user')->chunkById(200, function ($searches) use (&$users) {
                foreach ($searches as $search) {
                    if ($search->user?->isCandidate() && SendOpportunityDigests::isDue($search)) {
                        $users[$search->user_id] = true;
                    }
                }
            });
        if (! $this->option('dry-run')) {
            foreach (array_keys($users) as $userId) {
                SendOpportunityDigests::dispatch($userId);
            }
        }
        $this->info(count($users).' candidate digest jobs '.($this->option('dry-run') ? 'would be queued.' : 'queued.'));
        return self::SUCCESS;
    }
}

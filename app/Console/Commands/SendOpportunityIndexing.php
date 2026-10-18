<?php

namespace App\Console\Commands;

use App\Indexing\GoogleIndexing;
use App\Jobs\SendIndexingNotification;
use App\Models\Opportunity;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendOpportunityIndexing extends Command
{
    protected $signature = 'opportunities:indexing {--all : Enqueue all currently eligible JobPosting detail URLs} {--limit=100 : Maximum deliveries dispatched per run}';
    protected $description = 'Queue Google notifications for eligible opportunity updates and removals';

    public function handle(GoogleIndexing $indexing): int
    {
        if ($this->option('all')) {
            Opportunity::available()->chunkById(100, function ($opportunities) use ($indexing) {
                foreach ($opportunities as $opportunity) {
                    if ($indexing->eligible($opportunity)) $indexing->queue($opportunity);
                }
            });
        }
        $indexing->reconcile();
        if (!config('importer.indexing_enabled')) {
            $this->info('Google Indexing delivery is disabled. Notifications remain in the outbox.');
            return self::SUCCESS;
        }
        DB::table('indexing_notifications')->where('status', 'queued')->where('updated_at', '<', now()->subMinutes(15))
            ->update(['status' => 'pending', 'updated_at' => now()]);
        $limit = max(1, min(100, (int) $this->option('limit')));
        $pending = DB::table('indexing_notifications')->where('status', 'pending')
            ->where(fn ($query) => $query->whereNull('available_at')->orWhere('available_at', '<=', now()))
            ->orderBy('id')->limit($limit)->pluck('id');
        $count = 0;
        foreach ($pending as $id) {
            if (DB::table('indexing_notifications')->where('id', $id)->where('status', 'pending')->update(['status' => 'queued', 'updated_at' => now()])) {
                SendIndexingNotification::dispatch((int) $id);
                $count++;
            }
        }
        $this->info("Queued {$count} Indexing API notifications. Acknowledgement does not guarantee indexing.");
        return self::SUCCESS;
    }
}

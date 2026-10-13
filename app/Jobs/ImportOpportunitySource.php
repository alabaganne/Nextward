<?php

namespace App\Jobs;

use App\Importing\OpportunityImporter;
use App\Models\ImportSource;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Foundation\Queue\Queueable;

class ImportOpportunitySource implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 300;
    public int $uniqueFor = 350;
    public array $backoff = [300, 900, 3600];

    public function __construct(public int $sourceId, public int $limit = 40, public bool $recheck = false) {}

    public function uniqueId(): string { return 'opportunity-source:'.$this->sourceId; }

    public function handle(OpportunityImporter $importer): void
    {
        $source = ImportSource::find($this->sourceId);
        if (! $source?->isApproved()) { return; }
        if ($source->next_run_at?->isFuture() && ! $this->recheck) {
            return;
        }
        $result = $importer->run($source, $this->limit, false, $this->recheck);
        // Sync connections cannot honour delayed chains. The scheduler resumes pending work instead.
        if (($result['pending'] ?? false) && config('queue.default') !== 'sync') {
            self::dispatch($source->id, $this->limit)->delay(now()->addSeconds(30));
        }
    }
}

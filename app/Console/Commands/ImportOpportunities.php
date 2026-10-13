<?php

namespace App\Console\Commands;

use App\Importing\OpportunityImporter;
use App\Jobs\ImportOpportunitySource;
use App\Models\ImportSource;
use Illuminate\Console\Command;

class ImportOpportunities extends Command
{
    protected $signature = 'opportunities:import {--source=* : Source keys} {--limit=40} {--dry-run : Read without any persistent changes} {--sync : Process a bounded batch now} {--recheck : Reverify existing items} {--due : Only sources due now}';
    protected $description = 'Discover and import approved opportunity sources using bounded resumable batches';

    public function handle(OpportunityImporter $importer): int
    {
        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT);
        if (! $limit || $limit < 1 || $limit > 100) { $this->error('Limit must be between 1 and 100.'); return self::FAILURE; }
        $sources = ImportSource::where('enabled', true)->where('permission_status', 'approved')
            ->when($this->option('source'), fn ($q) => $q->whereIn('key', $this->option('source')))
            ->when($this->option('due'), fn ($q) => $q->where(fn ($q) => $q->whereNull('next_run_at')->orWhere('next_run_at', '<=', now())))->get();
        if ($sources->isEmpty()) { $this->info('No approved sources are enabled or due. Configure them in Imports.'); return self::SUCCESS; }
        $failed = false;
        foreach ($sources as $source) {
            try {
                if ($this->option('dry-run') || $this->option('sync')) {
                    $result = $importer->run($source, $limit, (bool) $this->option('dry-run'), (bool) $this->option('recheck'));
                    $this->line($source->key.': '.json_encode($result, JSON_UNESCAPED_SLASHES));
                } else {
                    ImportOpportunitySource::dispatch($source->id, $limit, (bool) $this->option('recheck'));
                    $this->info($source->key.': dispatched');
                }
            } catch (\Throwable $e) {
                $failed = true; $this->error($source->key.': '.$e->getMessage());
            }
        }
        return $failed ? self::FAILURE : self::SUCCESS;
    }
}

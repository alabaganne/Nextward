<?php

namespace App\Jobs;

use App\Importing\CandidateProfiles;
use App\Importing\OpportunityImporter;
use App\Models\ImportSource;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;

// The "Fetch new listings" button on the Sources page: what opportunities:fetch does, one source per job so each
// stays inside the worker's time limit. Progress is kept in the cache for the page to show.
class FetchNewOpportunities implements ShouldQueue
{
    use Queueable;

    public const STATE = 'imports:fetch';

    public int $tries = 1;
    public int $timeout = 300;

    public function __construct(public string $run, public array $sourceIds) {}

    public static function state(): ?array
    {
        return Cache::get(self::STATE);
    }

    // Running until it finishes, or until nothing has moved for 15 minutes (its worker stopped).
    public static function running(): bool
    {
        $state = self::state();
        return ($state['status'] ?? null) === 'running' && Carbon::parse($state['updated_at'])->gt(now()->subMinutes(15));
    }

    // Starts a fetch over every enabled source that publishes listings, in random order like the command.
    public static function start(int $count, string $by): bool
    {
        $sources = ImportSource::where('enabled', true)->where('permission_status', 'approved')->where('publication_mode', '!=', 'leads')
            ->pluck('id')->shuffle()->values()->all();
        if (! $sources) { return false; }
        $run = (string) Str::uuid();
        Cache::put(self::STATE, ['run' => $run, 'status' => 'running', 'count' => $count, 'added' => [], 'sources' => count($sources),
            'read' => 0, 'failed' => 0, 'by' => $by, 'started_at' => now()->toIso8601String(), 'updated_at' => now()->toIso8601String(), 'finished_at' => null], now()->addDay());
        self::dispatch($run, $sources);
        return true;
    }

    public function handle(OpportunityImporter $importer): void
    {
        $state = self::state();
        // A newer fetch replaced this one.
        if (($state['run'] ?? null) !== $this->run || $state['status'] !== 'running') { return; }
        $sources = $this->sourceIds;
        $source = ImportSource::find(array_shift($sources));
        if ($source?->isApproved()) {
            try {
                $result = $importer->run($source, 100, onlyNew: true, maxNew: $state['count'] - count($state['added']),
                    profiles: CandidateProfiles::load(), maxAgeDays: (int) config('importer.fetch_max_age_days'));
                $state['added'] = [...$state['added'], ...($result['new'] ?? [])];
            } catch (Throwable $e) {
                // The importer saved the error on the source and its run.
                report($e);
                $state['failed']++;
            }
        }
        $state['read']++;
        $more = $sources && count($state['added']) < $state['count'];
        $state = [...$state, 'status' => $more ? 'running' : 'done', 'updated_at' => now()->toIso8601String(), 'finished_at' => $more ? null : now()->toIso8601String()];
        Cache::put(self::STATE, $state, now()->addDay());
        if ($more) { self::dispatch($this->run, $sources); }
    }
}

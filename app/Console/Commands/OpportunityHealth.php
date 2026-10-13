<?php

namespace App\Console\Commands;

use App\Models\ImportSource;
use App\Models\Internship;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpportunityHealth extends Command
{
    protected $signature = 'opportunities:health {--public : Check actual homepage and browse HTML as well as the SSR process}';
    protected $description = 'Report import, queue, scheduler, delivery and public rendering health';

    public function handle(): int
    {
        $issues = [];
        $heartbeat = Cache::get('opportunities:scheduler:last_seen_at');
        if (! $heartbeat || \Carbon\Carbon::parse($heartbeat)->lt(now()->subMinutes(5))) {
            $issues[] = 'Scheduler heartbeat is missing or older than five minutes.';
        }
        if (config('queue.default') === 'database') {
            $oldest = DB::table('jobs')->where('available_at', '<=', now()->subMinutes(10)->timestamp)->min('created_at');
            if ($oldest) { $issues[] = 'Due queue work has waited longer than ten minutes. Check the queue worker.'; }
        } elseif (app()->isProduction() && config('queue.default') === 'sync') {
            $issues[] = 'Production uses the synchronous queue. Configure a supervised queue worker.';
        }
        $failedJobs = DB::table('failed_jobs')->count();
        if ($failedJobs) { $issues[] = $failedJobs.' failed queue jobs require review.'; }
        $overdue = ImportSource::where('enabled', true)->where('permission_status', 'approved')
            ->where(fn ($query) => $query->whereNull('last_run_at')->orWhere('next_run_at', '<', now()->subMinutes(30)))->count();
        if ($overdue) { $issues[] = $overdue.' approved sources are overdue or have never run.'; }
        $sourceErrors = ImportSource::whereNotNull('last_error')->count();
        if ($sourceErrors) { $issues[] = $sourceErrors.' sources have unresolved import errors.'; }
        $stale = 0;
        Internship::available()->where('origin', 'imported')->with('sources.importSource')
            ->chunkById(200, function ($opportunities) use (&$stale) {
                $stale += $opportunities->filter(fn ($opportunity) => ! $opportunity->hasFreshSource())->count();
            });
        if ($stale) { $issues[] = $stale.' public imported opportunities need fresh verification.'; }
        $uncertain = DB::table('opportunity_deliveries')->where('status', 'uncertain')->count();
        if ($uncertain) { $issues[] = $uncertain.' digest deliveries have uncertain outcomes and require manual review.'; }
        $indexingErrors = DB::table('indexing_notifications')->where('status', 'failed')->count();
        if ($indexingErrors) { $issues[] = $indexingErrors.' indexing notifications failed.'; }

        if (! config('inertia.ssr.enabled')) {
            $issues[] = 'Server rendering is disabled.';
        } else {
            try {
                $response = Http::timeout(5)->get(rtrim(config('inertia.ssr.url'), '/').'/health');
                if (! $response->successful() || $response->json('status') !== 'OK') { $issues[] = 'SSR process health check failed.'; }
            } catch (\Throwable) {
                $issues[] = 'SSR process is unreachable.';
            }
        }
        if ($this->option('public')) {
            foreach (['/', '/opportunities'] as $path) {
                try {
                    $response = Http::timeout(15)->get(rtrim(config('app.url'), '/').$path);
                    $html = preg_replace('~<script\b[^>]*>.*?</script>~is', '', $response->body());
                    if (! $response->successful() || ! preg_match('~<h1\b[^>]*>.+?</h1>~is', $html)
                        || ! preg_match('~<link\b[^>]*rel=["\x27]canonical["\x27]~i', $html)) {
                        $issues[] = 'Readable server HTML or canonical metadata is missing from '.$path;
                    }
                } catch (\Throwable) {
                    $issues[] = 'Public page is unreachable: '.$path;
                }
            }
        }
        if ($issues) {
            foreach ($issues as $issue) { $this->error($issue); }
            Log::warning('Opportunity service health requires attention.', ['issues' => $issues]);
            return self::FAILURE;
        }
        $this->info('Scheduler, queues, sources, delivery logs and SSR checks passed.');
        return self::SUCCESS;
    }
}

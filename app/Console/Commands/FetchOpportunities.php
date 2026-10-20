<?php

namespace App\Console\Commands;

use App\Ai\OpportunityClassifier;
use App\Importing\ImportFocus;
use App\Importing\OpportunityCsv;
use App\Importing\OpportunityImporter;
use App\Models\ImportSource;
use App\Models\Opportunity;
use Illuminate\Console\Command;

class FetchOpportunities extends Command
{
    protected $signature = 'opportunities:fetch {--count=10 : How many new opportunities to add} {--source=* : Only use these source keys}
        {--focus= : Keyword group from config/importer.php, such as tech} {--keywords= : Comma-separated title words, such as flutter,react}
        {--no-csv : Do not add the new opportunities to the CSV file}';
    protected $description = 'Read the import sources now and add a set number of opportunities that were never added before';

    public function handle(OpportunityImporter $importer): int
    {
        $count = filter_var($this->option('count'), FILTER_VALIDATE_INT);
        if (! $count || $count < 1 || $count > 100) { $this->error('Count must be between 1 and 100.'); return self::FAILURE; }
        $sources = ImportSource::where('enabled', true)->where('permission_status', 'approved')
            ->when($this->option('source'), fn ($q) => $q->whereIn('key', $this->option('source')))->orderBy('id')->get();
        $focus = ImportFocus::for($this->option('focus') ?: null, $this->option('keywords') ? explode(',', $this->option('keywords')) : []);
        if (! $focus) { $this->error('Unknown focus. Add it under focus in config/importer.php.'); return self::FAILURE; }
        [$focus, $focusFields] = $focus;
        $ai = app(OpportunityClassifier::class);
        $this->line($ai->enabled() ? $ai->label().' reads each new listing before it is added.' : 'AI is off (no '.$ai->keyName().' or OPPORTUNITY_AI_ENABLED=false); keyword rules sort the listings.');
        if ($sources->isEmpty()) { $this->info('No approved sources are enabled. Run php artisan db:seed --class=ImportSourceSeeder first.'); return self::SUCCESS; }

        // Alternate Tunisian boards and worldwide employer boards so the new set mixes both.
        [$tunisia, $worldwide] = $sources->partition(fn ($source) => $source->provider === 'jsonld');
        [$tunisia, $worldwide] = [$tunisia->values(), $worldwide->shuffle()->values()];
        $ordered = collect(range(0, max($tunisia->count(), $worldwide->count()) - 1))
            ->flatMap(fn ($i) => [$tunisia->get($i), $worldwide->get($i)])->filter()->values();

        $added = [];
        $quota = ['tunisia' => (int) ceil($count / 2), 'worldwide' => intdiv($count, 2)];
        // The first pass splits the target between Tunisian and worldwide sources; the second fills any gap from either.
        foreach ([true, false] as $split) {
            foreach ($ordered as $source) {
                $side = $source->provider === 'jsonld' ? 'tunisia' : 'worldwide';
                $limit = min($count - count($added), $split ? $quota[$side] : PHP_INT_MAX);
                if (count($added) >= $count) { break 2; }
                if ($limit < 1) { continue; }
                try {
                    $result = $importer->run($source->fresh(), 100, onlyNew: true, maxNew: $limit, focus: $focus, focusFields: $focusFields);
                } catch (\Throwable $e) {
                    $this->warn($source->key.': skipped, '.$e->getMessage());
                    continue;
                }
                $new = $result['new'] ?? [];
                $added = [...$added, ...$new];
                $quota[$side] -= count($new);
                if (($result['status'] ?? null) === 'locked') {
                    $this->line($source->key.': busy with a scheduled run, skipped');
                } elseif ($new || $this->output->isVerbose()) {
                    $this->line(sprintf('%s: %d new, %d skipped (old, out of scope or already added)', $source->key, count($new), $result['rejected'] ?? 0));
                }
            }
        }

        if ($reason = OpportunityClassifier::offReason()) {
            $this->warn($ai->label().' stopped during this run, so keyword rules sorted the rest: '.$reason);
        }
        if (! $added) {
            $this->info('No new opportunities found. Everything the sources list right now is already added, too old or out of scope.');
            return self::SUCCESS;
        }
        $this->table(['Title', 'Company', 'Kind', 'Location', 'Link'], Opportunity::with('company.user')->whereIn('id', $added)->get()
            ->map(fn ($role) => [str($role->title)->limit(50), str($role->company->display_name)->limit(30), $role->kind, str($role->location_label)->limit(30), $role->public_url]));
        $this->info(count($added).' new '.str('opportunity')->plural(count($added)).' added.');
        if (! $this->option('no-csv')) {
            $path = OpportunityCsv::path();
            $written = OpportunityCsv::append($path, Opportunity::whereIn('id', $added)->get());
            $this->line("Saved {$written} to ".str($path)->after(base_path().'/').'. On the server run: php artisan opportunities:import-csv');
        }
        return self::SUCCESS;
    }
}

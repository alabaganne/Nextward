<?php

namespace App\Console\Commands;

use App\Ai\OpportunityClassifier;
use App\Importing\CandidateProfiles;
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
        {--days= : Only listings posted in the last this many days (default importer.fetch_max_age_days)}
        {--everyone : Add IT listings even when no candidate profile wants them}
        {--no-csv : Do not add the new opportunities to the CSV file}';
    protected $description = 'Read candidate profiles, then the import sources, and add a set number of recent opportunities they want';

    public function handle(OpportunityImporter $importer): int
    {
        $count = filter_var($this->option('count'), FILTER_VALIDATE_INT);
        if (! $count || $count < 1 || $count > 100) { $this->error('Count must be between 1 and 100.'); return self::FAILURE; }
        $sources = ImportSource::where('enabled', true)->where('permission_status', 'approved')
            ->when($this->option('source'), fn ($q) => $q->whereIn('key', $this->option('source')))->orderBy('id')->get();
        $focus = ImportFocus::for($this->option('focus') ?: null, $this->option('keywords') ? explode(',', $this->option('keywords')) : []);
        if (! $focus) { $this->error('Unknown focus. Add it under focus in config/importer.php.'); return self::FAILURE; }
        [$focus, $focusFields] = $focus;
        $days = filter_var($this->option('days') ?? config('importer.fetch_max_age_days'), FILTER_VALIDATE_INT);
        if (! $days || $days < 1) { $this->error('Days must be a whole number above 0.'); return self::FAILURE; }
        $profiles = $this->option('everyone') ? new CandidateProfiles() : CandidateProfiles::load();
        if ($profiles->isEmpty()) {
            $this->line($this->option('everyone') ? 'Adding IT listings for everyone.' : 'No candidate profile has fields or skills yet, so any IT listing can be added. Read a resume with php artisan opportunities:profile.');
        } else {
            $summary = $profiles->summary();
            $this->line(sprintf('Matching listings posted in the last %d days against %d candidate %s.', $days, count($profiles->profiles), str('profile')->plural(count($profiles->profiles))));
            $this->line('Fields: '.(collect($summary['fields'])->map(fn ($n, $name) => "{$name} ({$n})")->implode(', ') ?: 'none'));
            $this->line('Top skills: '.(implode(', ', $summary['skills']) ?: 'none'));
        }
        $ai = app(OpportunityClassifier::class);
        $this->line($ai->enabled() ? $ai->label().' reads each new listing before it is added.' : 'AI is off (no '.$ai->keyName().' or OPPORTUNITY_AI_ENABLED=false); keyword rules sort the listings.');
        if ($sources->isEmpty()) { $this->info('No approved sources are enabled. Run php artisan db:seed --class=ImportSourceSeeder first.'); return self::SUCCESS; }

        $added = [];
        foreach ($sources->shuffle() as $source) {
            if (count($added) >= $count) { break; }
            try {
                $result = $importer->run($source->fresh(), config('importer.run_limit'), onlyNew: true, maxNew: $count - count($added), focus: $focus, focusFields: $focusFields, profiles: $profiles, maxAgeDays: $days);
            } catch (\Throwable $e) {
                $this->warn($source->key.': skipped, '.$e->getMessage());
                continue;
            }
            $new = $result['new'] ?? [];
            $added = [...$added, ...$new];
            if (($result['status'] ?? null) === 'locked') {
                $this->line($source->key.': busy with a scheduled run, skipped');
            } elseif ($new || $this->output->isVerbose()) {
                $this->line(sprintf('%s: %d new, %d skipped (old, out of scope, not wanted by a profile or already added)', $source->key, count($new), $result['rejected'] ?? 0));
            }
        }

        if ($reason = OpportunityClassifier::offReason()) {
            $this->warn($ai->label().' stopped during this run, so keyword rules sorted the rest: '.$reason);
        }
        if (! $added) {
            $this->info('No new opportunities found. Everything the sources list right now is already added, too old, out of scope or not wanted by a profile.');
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

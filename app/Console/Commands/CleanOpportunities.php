<?php

namespace App\Console\Commands;

use App\Importing\OpportunityImporter;
use App\Importing\OpportunityNormalizer;
use App\Importing\SourceHttp;
use App\Importing\SourceReader;
use App\Models\Opportunity;
use App\Models\OpportunitySource;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CleanOpportunities extends Command
{
    protected $signature = 'opportunities:clean {--dry-run : Show what would change and save nothing} {--source= : Only listings from this source key}';
    protected $description = 'Clean the location, description, work arrangement, contract types and skills of imported listings';

    public function handle(OpportunityImporter $importer): int
    {
        $normalizer = new OpportunityNormalizer();
        $dryRun = (bool) $this->option('dry-run');
        $readers = [];
        $listings = 0;
        $sources = OpportunitySource::with('importSource', 'opportunity')->whereNotNull('normalized_data')
            ->when($this->option('source'), fn ($query, $key) => $query->whereHas('importSource', fn ($query) => $query->where('key', $key)));
        foreach ($sources->lazyById(100) as $item) {
            $source = $item->importSource;
            // Board APIs keep the raw listing, so it is read again; page sources only keep the saved text.
            $input = $source ? ($readers[$source->id] ??= new SourceReader(new SourceHttp($source)))->saved($source, $item) : null;
            $old = $item->normalized_data;
            $data = $normalizer->clean($old, $input);
            // The AI answers stay on top of the cleaned keyword guesses.
            if ($item->ai_review) { $data = $importer->withReview($data, $item->ai_review); }
            $changes = array_filter([...OpportunityNormalizer::CLEANED, ...OpportunityNormalizer::REMOTE_FACTS], fn ($key) => ($old[$key] ?? null) != ($data[$key] ?? null));
            if (! $changes) { continue; }
            $listings++;
            $this->line("#{$item->id} ".($data['title'] ?? '').($source ? " ({$source->key})" : ''));
            foreach ($changes as $key) { $this->line("  {$key}: ".$this->change($key, $old[$key] ?? null, $data[$key] ?? null)); }
            if ($dryRun) { continue; }
            DB::transaction(function () use ($item, $old, $data, $changes) {
                $item->update(['normalized_data' => $data]);
                $role = $item->opportunity;
                if (! $role || $role->origin !== 'imported') { return; }
                $overrides = $role->moderation_overrides ?? [];
                // A field still showing this source's old value is updated; other sources and moderators keep theirs.
                foreach ($changes as $key) {
                    if (! array_key_exists($key, $overrides) && $role->{$key} == ($old[$key] ?? null)) { $role->{$key} = $data[$key] ?? null; }
                }
                $role->save();
            });
        }

        $skillLists = 0;
        $roles = Opportunity::where('origin', 'imported')->with('skills', 'sources')
            ->when($this->option('source'), fn ($query, $key) => $query->whereHas('sources.importSource', fn ($query) => $query->where('key', $key)));
        foreach ($roles->lazyById(100) as $role) {
            // Skills picked by the AI or by a moderator are kept.
            if (array_key_exists('skills', $role->moderation_overrides ?? []) || $role->sources->contains(fn ($item) => ! empty($item->ai_review['skills']))) { continue; }
            $names = $normalizer->skills($role->title, (string) $role->description);
            $current = $role->skills->pluck('name')->all();
            if ($this->sameNames($names, $current)) { continue; }
            $skillLists++;
            $this->line("Opportunity #{$role->id} {$role->title}");
            $this->line('  skills: '.($current ? implode(', ', $current) : 'none').' → '.($names ? implode(', ', $names) : 'none'));
            if (! $dryRun) { $role->skills()->sync($importer->skillIds($role->title, (string) $role->description)); }
        }

        $summary = "{$listings} ".Str::plural('listing', $listings).' and '."{$skillLists} skill ".Str::plural('list', $skillLists);
        $this->info($dryRun ? "{$summary} would change. Nothing was saved." : "{$summary} cleaned.");
        return self::SUCCESS;
    }

    private function change(string $key, $before, $after): string
    {
        if ($key === 'description') {
            return 'text changed, '.substr_count((string) $before, "\n").' → '.substr_count((string) $after, "\n").' line breaks';
        }
        return $this->show($key, $before).' → '.$this->show($key, $after);
    }

    private function show(string $key, $value): string
    {
        if ($key === 'locations') { $value = array_map(fn ($place) => is_array($place) ? ($place['location'] ?? '') : '', (array) $value); }
        $value = is_array($value) ? implode(' | ', array_filter($value)) : (string) $value;
        return $value === '' ? 'none' : Str::limit($value, 80);
    }

    private function sameNames(array $a, array $b): bool
    {
        $a = array_map('mb_strtolower', $a); $b = array_map('mb_strtolower', $b);
        sort($a); sort($b);
        return $a === $b;
    }
}

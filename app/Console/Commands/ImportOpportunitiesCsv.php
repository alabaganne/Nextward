<?php

namespace App\Console\Commands;

use App\Importing\OpportunityCsv;
use App\Importing\OpportunityImporter;
use App\Models\ImportSource;
use App\Models\OpportunitySource;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ImportOpportunitiesCsv extends Command
{
    protected $signature = 'opportunities:import-csv {path? : CSV file made by opportunities:fetch, default is OPPORTUNITY_CSV_PATH}';
    protected $description = 'Add the opportunities from a CSV file made on another machine, skipping any that are already here';

    public function handle(OpportunityImporter $importer): int
    {
        $path = OpportunityCsv::path($this->argument('path'));
        $counts = ['added' => 0, 'already here' => 0, 'skipped' => 0];
        try {
            foreach (OpportunityCsv::rows($path) as $line => $row) {
                [$status, $note] = $this->add($importer, $row);
                $counts[$status]++;
                if ($status === 'skipped') { $this->warn(($row['title'] ?: 'Row '.($line + 2)).': '.$note); }
            }
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
        $this->info(sprintf('%d added, %d already here, %d skipped.', $counts['added'], $counts['already here'], $counts['skipped']));
        return self::SUCCESS;
    }

    private function add(OpportunityImporter $importer, array $row): array
    {
        $source = ImportSource::where('key', $row['source_key'])->first();
        if (! $source) { return ['skipped', "source {$row['source_key']} is not set up here; run php artisan db:seed --class=ImportSourceSeeder"]; }
        $data = json_decode($row['normalized_data'], true);
        if (! is_array($data) || ! $row['external_id'] || ! $row['last_verified_at']) { return ['skipped', 'the row is incomplete']; }

        $item = OpportunitySource::firstOrNew(['import_source_id' => $source->id, 'external_id' => $row['external_id']]);
        if ($item->internship_id) { return ['already here', null]; }
        if ($item->content_hash) { return ['skipped', 'it was added here before and removed later']; }

        $checked = Carbon::parse($row['last_verified_at']);
        $item->fill([
            'source_url' => $row['source_url'], 'application_url' => $data['application_url'] ?? null,
            'normalized_data' => $data, 'ai_review' => json_decode($row['ai_review'], true) ?: $item->ai_review,
            'first_seen_at' => $item->first_seen_at ?? Carbon::parse($row['first_seen_at'] ?: $row['last_verified_at']),
            'last_discovered_at' => $item->last_discovered_at ?? $checked,
            'last_verified_at' => $item->last_verified_at?->gt($checked) ? $item->last_verified_at : $checked,
            'state' => 'pending', 'error' => null,
        ])->save();

        try {
            $role = $importer->publish($item);
        } catch (\RuntimeException $e) {
            return ['skipped', $e->getMessage().' It was checked '.$checked->diffForHumans().'; run the scraper again for a fresh file.'];
        }
        return $role->wasRecentlyCreated ? ['added', null] : ['already here', null];
    }
}

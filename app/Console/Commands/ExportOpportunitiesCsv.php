<?php

namespace App\Console\Commands;

use App\Importing\OpportunityCsv;
use App\Models\Opportunity;
use Illuminate\Console\Command;

class ExportOpportunitiesCsv extends Command
{
    protected $signature = 'opportunities:export-csv {path? : Default is OPPORTUNITY_CSV_PATH} {--since= : Only opportunities first seen on or after this date}';
    protected $description = 'Add the open imported opportunities to the CSV file, skipping rows already in it';

    public function handle(): int
    {
        $path = OpportunityCsv::path($this->argument('path'));
        $roles = Opportunity::available()->where('origin', 'imported')
            ->when($this->option('since'), fn ($q, $since) => $q->where('first_seen_at', '>=', $since))->orderBy('id')->get();
        $written = OpportunityCsv::append($path, $roles);
        $this->info("Saved {$written} new rows to {$path}.");
        return self::SUCCESS;
    }
}

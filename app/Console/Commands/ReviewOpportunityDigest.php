<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ReviewOpportunityDigest extends Command
{
    protected $signature = 'opportunities:review-digest {batch : The delivery batch UUID} {--mark-sent : Record confirmed delivery without sending} {--retry-confirmed-unsent : Allow the next digest job to retry after the provider confirms no delivery}';
    protected $description = 'Inspect or resolve an uncertain digest after reviewing the mail provider';

    public function handle(): int
    {
        if (Validator::make(['batch' => $this->argument('batch')], ['batch' => 'required|uuid'])->fails()) {
            $this->error('Provide a valid batch UUID.');
            return self::FAILURE;
        }
        if ($this->option('mark-sent') && $this->option('retry-confirmed-unsent')) {
            $this->error('Choose one outcome, not both.');
            return self::FAILURE;
        }
        $query = DB::table('opportunity_deliveries')->where('batch_id', $this->argument('batch'));
        $records = (clone $query)->get();
        if ($records->isEmpty()) {
            $this->error('Delivery batch not found.');
            return self::FAILURE;
        }
        $this->table(['Status', 'Items'], $records->groupBy('status')->map(fn ($rows, $status) => [$status, $rows->count()])->values()->all());
        if ($this->option('mark-sent')) {
            DB::transaction(function () use ($query, $records) {
                $query->whereIn('status', ['uncertain', 'sending'])->update(['status' => 'sent', 'sent_at' => now(), 'last_error' => null, 'updated_at' => now()]);
                DB::table('saved_searches')->whereIn('id', $records->pluck('saved_search_id')->filter())->update(['last_sent_at' => now(), 'updated_at' => now()]);
            });
            $this->info('Confirmed delivery recorded. No email was sent.');
        } elseif ($this->option('retry-confirmed-unsent')) {
            $query->where('status', 'uncertain')->update(['status' => 'pending', 'attempts' => 0, 'last_error' => null, 'updated_at' => now()]);
            $this->info('Confirmed-unsent items are pending. The next scheduled job will recheck consent and availability.');
        }
        return self::SUCCESS;
    }
}

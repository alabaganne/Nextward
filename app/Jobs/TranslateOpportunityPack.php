<?php

namespace App\Jobs;

use App\Ai\ListingTranslations;
use App\Models\Opportunity;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

// Translates a few listings in one AI call. Failures are logged and the next opportunities:translate run tries again.
class TranslateOpportunityPack implements ShouldQueue
{
    use Queueable;

    public int $timeout = 400;
    public int $tries = 1;

    public function __construct(public array $opportunityIds) {}

    public function handle(ListingTranslations $translations): void
    {
        $translations->translateMany(Opportunity::with('translations')->whereIn('id', $this->opportunityIds)
            ->where('publication_status', 'published')->get());
    }
}

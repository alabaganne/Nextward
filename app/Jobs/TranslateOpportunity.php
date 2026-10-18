<?php

namespace App\Jobs;

use App\Ai\ListingTranslations;
use App\Models\Internship;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TranslateOpportunity implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Queueable;

    public int $timeout = 400;
    public int $uniqueFor = 900;

    public function __construct(public int $opportunityId) {}

    public function uniqueId(): string { return 'translate-opportunity:'.$this->opportunityId; }

    public function handle(ListingTranslations $translations): void
    {
        $opportunity = Internship::find($this->opportunityId);
        if (! $opportunity || $opportunity->publication_status !== 'published') { return; }
        try {
            $translations->translate($opportunity);
        } catch (\Throwable $e) {
            // The hourly opportunities:translate run tries again, so a failure here never blocks an import.
            Log::warning('Listing translation failed.', ['internship_id' => $opportunity->id, 'error' => Str::limit($e->getMessage(), 300)]);
        }
    }
}

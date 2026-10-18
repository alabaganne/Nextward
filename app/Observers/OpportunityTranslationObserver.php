<?php

namespace App\Observers;

use App\Ai\ListingTranslations;
use App\Jobs\TranslateOpportunity;
use App\Models\Opportunity;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class OpportunityTranslationObserver implements ShouldHandleEventsAfterCommit
{
    public function saved(Opportunity $opportunity): void
    {
        $changed = $opportunity->wasRecentlyCreated || $opportunity->wasChanged(['title', 'description', 'publication_status']);
        if ($changed && $opportunity->publication_status === 'published' && app(ListingTranslations::class)->enabled()) {
            TranslateOpportunity::dispatch($opportunity->id);
        }
    }
}

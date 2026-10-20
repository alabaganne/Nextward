<?php

namespace App\Observers;

use App\Ai\ListingTranslations;
use App\Jobs\TranslateOpportunity;
use App\Models\Opportunity;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

// Employer posts are translated right away. Imported listings wait for opportunities:translate,
// which runs every 10 minutes and translates several listings per AI call.
class OpportunityTranslationObserver implements ShouldHandleEventsAfterCommit
{
    public function saved(Opportunity $opportunity): void
    {
        if ($opportunity->origin === 'imported') { return; }
        $changed = $opportunity->wasRecentlyCreated || $opportunity->wasChanged(['title', 'description', 'publication_status']);
        if ($changed && $opportunity->publication_status === 'published' && app(ListingTranslations::class)->enabled()) {
            TranslateOpportunity::dispatch($opportunity->id);
        }
    }
}

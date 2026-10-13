<?php

namespace App\Observers;

use App\Indexing\GoogleIndexing;
use App\Models\Internship;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class OpportunityIndexingObserver implements ShouldHandleEventsAfterCommit
{
    public function created(Internship $opportunity): void
    {
        app(GoogleIndexing::class)->queue($opportunity);
    }

    public function updated(Internship $opportunity): void
    {
        $changed = array_diff(array_keys($opportunity->getChanges()), ['last_verified_at', 'first_seen_at', 'updated_at', 'moderation_overrides']);
        if ($changed) {
            app(GoogleIndexing::class)->queue($opportunity);
        }
    }

    public function deleted(Internship $opportunity): void
    {
        app(GoogleIndexing::class)->deleted($opportunity);
    }
}

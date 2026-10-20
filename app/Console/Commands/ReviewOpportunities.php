<?php

namespace App\Console\Commands;

use App\Ai\OpportunityClassifier;
use App\Importing\OpportunityImporter;
use App\Models\Opportunity;
use Illuminate\Console\Command;

// Asks the AI again about open imported listings, a batch per call, so they get who can apply, skills and filter values.
class ReviewOpportunities extends Command
{
    protected $signature = 'opportunities:review {--limit=200 : Most listings to read} {--all : Also read listings that already say who can apply}';
    protected $description = 'Read open imported listings again with the AI to fill who can apply, skills and filter values';

    public function handle(OpportunityClassifier $ai, OpportunityImporter $importer): int
    {
        if (! $ai->enabled()) {
            $this->info('AI is off or has no key.');
            return self::SUCCESS;
        }
        $roles = Opportunity::available()->where('origin', 'imported')
            ->when(! $this->option('all'), fn ($query) => $query->where('applicant_scope', 'unknown'))
            ->with(['sources' => fn ($query) => $query->whereNotNull('normalized_data')->orderBy('id')])
            ->latest('id')->limit(max(1, (int) $this->option('limit')))->get()
            ->filter(fn ($role) => $role->sources->isNotEmpty())->keyBy('id');
        $reviews = $ai->classifyMany($roles->map(fn ($role) => $role->sources->first()->normalized_data)->all(), $importer->fieldNames(), $importer->skillNames());
        $weak = [];
        foreach ($reviews as $id => $review) {
            $importer->applyReview($roles[$id], $roles[$id]->sources->first(), $review);
            if ($review['quality'] === 'reject') { $weak[] = "#{$id} {$roles[$id]->title}: {$review['quality_reason']}"; }
        }
        $this->info('Read '.count($reviews).' of '.$roles->count().' listing(s).');
        if ($weak) {
            $this->warn('The AI judged these listings low quality. They stay online; suppress them in Imports if you agree:');
            foreach ($weak as $line) { $this->line('  '.$line); }
        }
        return self::SUCCESS;
    }
}

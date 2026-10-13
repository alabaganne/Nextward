<?php

namespace App\Console\Commands;

use App\Importing\OpportunityImporter;
use Illuminate\Console\Command;

class ReconcileOpportunities extends Command
{
    protected $signature = 'opportunities:reconcile';
    protected $description = 'Close expired roles and suspend imported roles awaiting fresh verification';

    public function handle(OpportunityImporter $importer): int
    {
        $this->info($importer->reconcile().' opportunities reconciled.');
        return self::SUCCESS;
    }
}

<?php

namespace App\Jobs;

use App\Importing\CompanyVerifier;
use App\Importing\OpportunityImporter;
use App\Models\Company;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ResearchCompany implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 1;
    public int $timeout = 240;
    public int $uniqueFor = 3600;

    public function __construct(public int $companyId) {}

    public function uniqueId(): string
    {
        return (string) $this->companyId;
    }

    public function handle(CompanyVerifier $verifier, OpportunityImporter $importer): void
    {
        if (! $company = Company::find($this->companyId)) { return; }
        $verifier->verify($company);
        $importer->release($company);
    }
}

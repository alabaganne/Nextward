<?php

namespace App\Console\Commands;

use App\Importing\CompanyVerifier;
use App\Importing\OpportunityImporter;
use App\Models\Company;
use Illuminate\Console\Command;

class ResearchCompanies extends Command
{
    protected $signature = 'companies:research {--limit=20 : Most companies to check in one run} {--company=* : Only these company ids}';
    protected $description = 'Find and check the websites of imported companies, fill in their facts and give the Verified badge';

    public function handle(CompanyVerifier $verifier, OpportunityImporter $importer): int
    {
        // Companies whose listings wait for the check come first, then companies with open listings; each gets two tries, a day apart.
        $companies = Company::whereNull('verified_at')->whereNull('claimed_by_company_id')->whereNotNull('import_identity')
            ->when($this->option('company'), fn ($query, $ids) => $query->whereIn('id', $ids),
                fn ($query) => $query->where('research_attempts', '<', 2)
                    ->where(fn ($query) => $query->whereNull('researched_at')->orWhere('researched_at', '<', now()->subHours(20)))
                    ->where(fn ($query) => $query->whereExists(fn ($query) => $query->from('opportunity_sources')->whereColumn('opportunity_sources.company_id', 'companies.id')->where('state', 'waiting'))
                        ->orWhereHas('opportunities', fn ($query) => $query->available())))
            ->orderByRaw('(SELECT COUNT(*) FROM opportunity_sources WHERE opportunity_sources.company_id = companies.id AND state = ?) DESC', ['waiting'])
            ->orderBy('id')->limit((int) $this->option('limit'))->get();

        $counts = ['verified' => 0, 'failed' => 0, 'skipped' => 0];
        foreach ($companies as $company) {
            $result = $verifier->verify($company);
            $importer->release($company);
            $counts[$result === true ? 'verified' : ($result === false ? 'failed' : 'skipped')]++;
            $this->line(sprintf('%s: %s', $company->name, $result === true ? 'verified' : ($result === false ? ($company->verification['error'] ?? 'not verified') : 'not checked (AI off and no website)')));
        }
        $this->info("{$counts['verified']} verified, {$counts['failed']} not verified, {$counts['skipped']} not checked.");
        return self::SUCCESS;
    }
}

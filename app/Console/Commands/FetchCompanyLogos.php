<?php

namespace App\Console\Commands;

use App\Importing\CompanyLogos;
use App\Models\Company;
use App\Models\User;
use Illuminate\Console\Command;

class FetchCompanyLogos extends Command
{
    protected $signature = 'companies:logos {--limit=50 : Most logos to download in one run}';
    protected $description = 'Download outside company logos once, so pages only show copies served by Nextward';

    public function handle(CompanyLogos $logos): int
    {
        // An outside account image goes through the same download as an imported logo.
        User::where('userable_type', (new Company())->getMorphClass())
            ->where(fn ($query) => $query->where('image', 'like', 'http://%')->orWhere('image', 'like', 'https://%'))
            ->with('userable')->get()->each(function (User $user) {
                if ($user->userable && ! $user->userable->logo_url) {
                    $user->userable->update(['logo_url' => $user->image]);
                }
                $user->update(['image' => null]);
            });

        $saved = 0;
        $failed = 0;
        // New logos first; failed ones are tried again after a week.
        Company::whereNotNull('logo_url')
            ->where(fn ($query) => $query->whereNull('logo_fetched_at')
                ->orWhere(fn ($query) => $query->whereNull('logo_path')->where('logo_fetched_at', '<', now()->subWeek())))
            ->orderByRaw('logo_fetched_at IS NOT NULL')->orderBy('id')->limit((int) $this->option('limit'))->get()
            ->each(function (Company $company) use ($logos, &$saved, &$failed) {
                $logos->fetch($company) ? $saved++ : $failed++;
            });

        $this->info("{$saved} logos saved, {$failed} failed.");
        return self::SUCCESS;
    }
}

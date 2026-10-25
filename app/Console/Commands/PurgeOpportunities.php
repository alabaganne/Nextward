<?php

namespace App\Console\Commands;

use App\Models\Application;
use App\Models\Company;
use App\Models\Opportunity;
use App\Notifications\ApplicationReviewed;
use App\Notifications\ApplicationSubmitted;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PurgeOpportunities extends Command
{
    protected $signature = 'opportunities:purge
        {--origin=imported : imported, native (posted by companies) or all}
        {--source=* : Only listings found on these source keys}
        {--remember : Keep the source records, so these listings are never imported again}
        {--keep-companies : Keep imported companies left with no listings}
        {--dry-run : Count what would be deleted and delete nothing}
        {--force : Do not ask before deleting}';
    protected $description = 'Delete listings with their applications, saves, views, alerts, reports, translations, kits and files';

    // Rows that belong to a listing and go with it.
    private const RELATED = [
        'applications', 'application_kits', 'likes', 'opportunity_views', 'opportunity_deliveries',
        'opportunity_reports', 'opportunity_translations', 'opportunity_audits', 'opportunity_sources',
    ];

    public function handle(): int
    {
        $origin = $this->option('origin');
        if (! in_array($origin, ['imported', 'native', 'all'], true)) {
            $this->error('--origin must be imported, native or all.');
            return self::INVALID;
        }

        $ids = Opportunity::query()
            ->when($origin !== 'all', fn ($query) => $query->where('origin', $origin))
            ->when($this->option('source'), fn ($query, $keys) => $query->whereHas('sources.importSource', fn ($query) => $query->whereIn('key', $keys)))
            ->pluck('id')->all();
        // Duplicates merged into a deleted listing go too, or they would show again.
        for ($found = $ids; $found; $ids = [...$ids, ...$found]) {
            $found = collect(array_chunk($found, 500))->flatMap(fn ($chunk) => Opportunity::whereIn('merged_into_id', $chunk)->whereNotIn('id', $ids)->pluck('id'))->unique()->all();
        }
        if (! $ids) {
            $this->info('No listings match.');
            return self::SUCCESS;
        }

        $tables = $this->option('remember') ? array_diff(self::RELATED, ['opportunity_sources']) : self::RELATED;
        $counts = ['opportunities' => count($ids)] + array_fill_keys($tables, 0) + ['skillables' => 0];
        foreach (array_chunk($ids, 500) as $chunk) {
            foreach ($tables as $table) { $counts[$table] += DB::table($table)->whereIn('opportunity_id', $chunk)->count(); }
            $counts['skillables'] += $this->skills($chunk)->count();
        }
        $this->table(['Table', 'Rows'], collect($counts)->map(fn ($count, $table) => [$table, $count])->values()->all());

        if ($this->option('dry-run')) {
            $this->info('Dry run. Nothing was deleted.');
            return self::SUCCESS;
        }
        if (! $this->option('force') && ! $this->confirm('Delete these rows for good?')) {
            $this->line('Nothing was deleted.');
            return self::FAILURE;
        }

        $files = [];
        $applicationIds = [];
        $companyIds = [];
        foreach (array_chunk($ids, 500) as $chunk) {
            DB::transaction(function () use ($chunk, $tables, &$files, &$applicationIds, &$companyIds) {
                $applications = Application::whereIn('opportunity_id', $chunk)->get(['id', 'attachments']);
                $applicationIds = [...$applicationIds, ...$applications->modelKeys()];
                $files = [...$files, ...$applications->flatMap(fn ($application) => $application->attachments ?: [])
                    ->map(fn ($attachment) => is_array($attachment) ? Application::attachmentStorage($attachment) : null)->filter()];

                $listings = Opportunity::whereIn('id', $chunk)->get(['id', 'company_id', 'attachments']);
                $companyIds = [...$companyIds, ...$listings->pluck('company_id')->filter()];
                $files = [...$files, ...$listings->flatMap(fn ($listing) => $listing->attachments ?: [])
                    ->map(fn ($attachment) => $this->listingFile($attachment))->filter()];

                $kits = DB::table('application_kits')->whereIn('opportunity_id', $chunk)->pluck('id');
                foreach ($kits->chunk(500) as $kitIds) {
                    DB::table('credit_transactions')->whereIn('application_kit_id', $kitIds)->update(['application_kit_id' => null]);
                }
                if ($this->option('remember')) {
                    DB::table('opportunity_sources')->whereIn('opportunity_id', $chunk)->update(['opportunity_id' => null]);
                }
                // Search engines are told these pages are gone by the indexing reconcile.
                DB::table('indexing_notifications')->whereIn('opportunity_id', $chunk)->update(['opportunity_id' => null]);
                foreach ($tables as $table) { DB::table($table)->whereIn('opportunity_id', $chunk)->delete(); }
                $this->skills($chunk)->delete();
                Opportunity::whereIn('merged_into_id', $chunk)->update(['merged_into_id' => null]);
                Opportunity::whereIn('id', $chunk)->delete();
            });
        }

        $notifications = $this->notifications($applicationIds);
        $companies = $this->option('keep-companies') ? 0 : $this->emptyCompanies(array_unique($companyIds));
        foreach ($files as $file) { Storage::disk($file['disk'])->delete($file['path']); }

        $this->info(count($ids).' listings deleted, with '.count($applicationIds).' applications, '.count($files).' files, '
            ."{$notifications} notifications and {$companies} empty imported companies.");
        return self::SUCCESS;
    }

    private function skills(array $ids)
    {
        return DB::table('skillables')->where('skillable_type', 'opportunity')->whereIn('skillable_id', $ids);
    }

    private function listingFile($attachment): ?array
    {
        $path = is_array($attachment) ? parse_url($attachment['url'] ?? '', PHP_URL_PATH) : null;
        return is_string($path) && str_starts_with($path, '/storage/internships/') && ! str_contains($path, '..')
            ? ['disk' => 'public', 'path' => substr($path, strlen('/storage/'))] : null;
    }

    // Notifications that link to a deleted application.
    private function notifications(array $applicationIds): int
    {
        if (! $applicationIds) { return 0; }
        $links = array_fill_keys(array_merge(...array_map(fn ($id) => ["/applications/{$id}", "/app/applications/{$id}"], $applicationIds)), true);
        $ids = DB::table('notifications')->whereIn('type', [ApplicationSubmitted::class, ApplicationReviewed::class])
            ->get(['id', 'data'])
            ->filter(fn ($row) => isset($links[json_decode($row->data, true)['action'] ?? '']))
            ->pluck('id');
        foreach ($ids->chunk(500) as $chunk) { DB::table('notifications')->whereIn('id', $chunk)->delete(); }
        return $ids->count();
    }

    // Companies made by the importer that now have no listings, no account and nothing else tied to them.
    private function emptyCompanies(array $ids): int
    {
        $deleted = 0;
        foreach (array_chunk($ids, 500) as $chunk) {
            Company::whereIn('id', $chunk)->whereNotNull('import_identity')
                ->whereDoesntHave('opportunities')->whereDoesntHave('user')
                // Listings still waiting for this company's website check need it.
                ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('opportunity_sources')->whereColumn('opportunity_sources.company_id', 'companies.id')->where('state', 'waiting'))
                ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('applications')->whereColumn('applications.company_id', 'companies.id'))
                ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('company_claims')->whereColumn('company_claims.company_id', 'companies.id'))
                ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('import_sources')->whereColumn('import_sources.company_id', 'companies.id'))
                ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('companies as merged')->whereColumn('merged.claimed_by_company_id', 'companies.id'))
                ->get()->each(function (Company $company) use (&$deleted) {
                    $company->delete();
                    $deleted++;
                });
        }
        return $deleted;
    }
}

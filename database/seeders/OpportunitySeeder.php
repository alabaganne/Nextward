<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Company;
use App\Models\Field;
use App\Models\ImportSource;
use App\Models\Internship;
use App\Models\OpportunitySource;
use App\Models\Skill;
use Carbon\CarbonImmutable;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OpportunitySeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $catalogue = require __DIR__.'/data/opportunities.php';
        $verifiedAt = CarbonImmutable::parse($catalogue['verified_at']);
        $fresh = $verifiedAt->gte(now()->subHours(config('importer.freshness_hours', 48)));

        DB::transaction(function () use ($catalogue, $verifiedAt, $fresh) {
            foreach ($catalogue['opportunities'] as $record) {
                // Existing moderation decisions and verification dates must survive repeat seeding.
                if (Internship::where('application_url', $record['url'])->exists()) { continue; }

                $company = Company::firstOrCreate(['domain' => $record['company']['domain']], [
                    'name' => $record['company']['name'], 'website' => $record['company']['website'],
                ]);
                $source = ImportSource::firstOrCreate(['key' => 'curated-'.$record['key']], [
                    'name' => $record['company']['name'].' careers', 'provider' => 'jsonld',
                    'url' => $record['url'], 'allowed_domains' => [parse_url($record['url'], PHP_URL_HOST)],
                    'company_id' => $company->id, 'enabled' => false, 'publication_mode' => 'review',
                    'permission_status' => 'approved',
                    'permission_notes' => 'Editorial inclusion of public vacancy facts and an original Nextward summary, checked on 2026-10-13. Approval covers this curated snapshot only. No employer affiliation or permission for automated full-text copying is claimed. Review permissions and adapter support before enabling imports.',
                    'settings' => ['employer_name' => $company->name, 'curated_snapshot' => true],
                    'cadence_hours' => 24, 'request_delay_ms' => 1000, 'max_requests' => 5,
                ]);
                $item = OpportunitySource::firstOrCreate([
                    'import_source_id' => $source->id, 'external_id' => hash('sha256', $record['url']),
                ], [
                    'source_url' => $record['url'], 'application_url' => $record['url'],
                    'first_seen_at' => $verifiedAt, 'last_discovered_at' => $verifiedAt,
                    'last_verified_at' => $verifiedAt, 'state' => 'review',
                ]);
                if (! $item->wasRecentlyCreated) { continue; }

                $data = array_merge([
                    'origin' => 'imported', 'application_mode' => 'external', 'application_url' => $record['url'],
                    'slug' => Str::limit(Str::slug($record['title']), 180, ''),
                    'title' => $record['title'], 'description' => $record['description'],
                    'kind' => $record['kind'], 'company_id' => $company->id,
                    'field_id' => Field::firstOrCreate(['name' => $record['field']])->id,
                    'city_id' => $record['country_code'] === 'TN' ? City::firstOrCreate(['name' => 'Tunis'])->id : null,
                    'country_code' => $record['country_code'], 'location' => $record['location'],
                    'language' => $record['language'], 'work_type' => $record['work_type'],
                    'publication_status' => $fresh && $source->permission_status === 'approved' ? 'published' : 'draft',
                    'status' => 'open', 'posted_at' => $record['posted_at'],
                    'first_seen_at' => $verifiedAt, 'last_verified_at' => $verifiedAt,
                    'content_updated_at' => $verifiedAt, 'attachments' => [],
                ], $record['details']);
                $opportunity = Internship::create($data);
                $opportunity->skills()->sync(collect($record['skills'])
                    ->map(fn ($name) => Skill::firstOrCreate(['name' => $name])->id));
                $item->update([
                    'internship_id' => $opportunity->id, 'state' => $data['publication_status'] === 'published' ? 'published' : 'review',
                    'normalized_data' => array_merge($data, [
                        'employer' => $company->name, 'website' => $company->website,
                    ]),
                    'content_hash' => hash('sha256', json_encode($data)),
                ]);
            }
        });

        if (! $fresh) {
            $this->command?->warn('The curated snapshot is older than the freshness limit. New listings were saved as drafts. Recheck employer pages before publishing; rerunning this seed does not reverify them.');
        }
    }
}

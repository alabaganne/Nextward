<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\ImportSource;
use App\Models\Opportunity;
use App\Models\Skill;
use App\Models\User;
use App\Notifications\ApplicationSubmitted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class PurgeOpportunitiesTest extends TestCase
{
    use RefreshDatabase;

    private Candidate $candidate;
    private ImportSource $source;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->candidate = Candidate::factory()->create();
        $this->candidate->user()->save(User::factory()->make());
        $this->source = ImportSource::create(['key' => 'tanitjobs', 'name' => 'Tanitjobs', 'provider' => 'jsonld',
            'url' => 'https://www.tanitjobs.com/sitemap.xml', 'allowed_domains' => ['www.tanitjobs.com']]);
    }

    public function test_dry_run_counts_and_deletes_nothing(): void
    {
        $this->imported();

        $this->artisan('opportunities:purge --dry-run')->expectsOutputToContain('Dry run')->assertSuccessful();
        $this->assertSame(1, Opportunity::count());
        $this->assertSame(1, Application::count());
    }

    public function test_imported_listings_go_with_their_rows_files_and_empty_companies(): void
    {
        $native = Opportunity::factory()->create();
        $kept = Application::factory()->create(['opportunity_id' => $native->id, 'candidate_id' => $this->candidate->id]);
        $role = $this->imported();
        $duplicate = $this->imported(['merged_into_id' => $role->id]);
        $company = $role->company;

        $this->artisan('opportunities:purge')->expectsConfirmation('Delete these rows for good?', 'yes')->assertSuccessful();

        $this->assertSame([$native->id], Opportunity::pluck('id')->all());
        $this->assertSame([$kept->id], Application::pluck('id')->all());
        foreach (['likes', 'opportunity_views', 'opportunity_translations', 'opportunity_sources', 'application_kits', 'opportunity_reports'] as $table) {
            $this->assertSame(0, DB::table($table)->whereIn('opportunity_id', [$role->id, $duplicate->id])->count(), $table);
        }
        $this->assertSame(0, DB::table('skillables')->where('skillable_type', 'opportunity')->whereIn('skillable_id', [$role->id, $duplicate->id])->count());
        $this->assertSame(0, DB::table('notifications')->count());
        $this->assertSame([null, null], DB::table('credit_transactions')->where('reason', 'kit')->pluck('application_kit_id')->all());
        Storage::disk('local')->assertMissing('applications/cv.pdf');
        $this->assertNull(Company::find($company->id));
        $this->assertNotNull($native->company->fresh());
    }

    public function test_remember_keeps_source_records_so_listings_stay_out(): void
    {
        $role = $this->imported();

        $this->artisan('opportunities:purge --remember --keep-companies --force')->assertSuccessful();

        $this->assertSame(0, Opportunity::count());
        $this->assertSame(1, DB::table('opportunity_sources')->whereNull('opportunity_id')->count());
        $this->assertNotNull(Company::find($role->company_id));
    }

    public function test_source_option_only_touches_that_source(): void
    {
        $kept = $this->imported();
        $other = ImportSource::create(['key' => 'keejob', 'name' => 'Keejob', 'provider' => 'jsonld',
            'url' => 'https://www.keejob.com/sitemap.xml', 'allowed_domains' => ['www.keejob.com']]);
        $this->imported([], $other);

        $this->artisan('opportunities:purge --source=keejob --force')->assertSuccessful();

        $this->assertSame([$kept->id], Opportunity::pluck('id')->all());
    }

    private function imported(array $attributes = [], ?ImportSource $source = null): Opportunity
    {
        $company = Company::forceCreate(['name' => 'Imported Co', 'import_identity' => Str::random(12)]);
        $role = Opportunity::factory()->create(['company_id' => $company->id, 'origin' => 'imported', 'application_mode' => 'external'] + $attributes);
        $source ??= $this->source;
        DB::table('opportunity_sources')->insert(['import_source_id' => $source->id, 'opportunity_id' => $role->id, 'external_id' => Str::random(8),
            'source_url' => $source->url, 'content_hash' => 'abc', 'first_seen_at' => now(), 'created_at' => now(), 'updated_at' => now()]);

        Storage::disk('local')->put('applications/cv.pdf', 'pdf');
        $application = Application::factory()->create(['opportunity_id' => $role->id, 'candidate_id' => $this->candidate->id,
            'attachments' => [['name' => 'cv.pdf', 'disk' => 'local', 'path' => 'applications/cv.pdf']]]);
        $this->candidate->user->notify(new ApplicationSubmitted($application));
        DB::table('likes')->insert(['candidate_id' => $this->candidate->id, 'opportunity_id' => $role->id]);
        DB::table('opportunity_views')->insert(['candidate_id' => $this->candidate->id, 'opportunity_id' => $role->id, 'viewed_at' => now()]);
        DB::table('opportunity_reports')->insert(['opportunity_id' => $role->id, 'reason' => 'closed', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('opportunity_translations')->insert(['opportunity_id' => $role->id, 'locale' => 'fr', 'title' => 'Rôle', 'description' => 'Texte',
            'slug' => 'role', 'source_hash' => str_repeat('a', 40), 'created_at' => now(), 'updated_at' => now()]);
        $kit = DB::table('application_kits')->insertGetId(['candidate_id' => $this->candidate->id, 'opportunity_id' => $role->id, 'locale' => 'en',
            'created_at' => now(), 'updated_at' => now()]);
        DB::table('credit_transactions')->insert(['user_id' => $this->candidate->user->id, 'amount' => -1, 'reason' => 'kit', 'application_kit_id' => $kit]);
        $role->skills()->attach(Skill::firstOrCreate(['name' => 'Laravel']));

        return $role;
    }
}

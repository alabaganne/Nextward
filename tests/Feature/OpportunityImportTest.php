<?php

namespace Tests\Feature;

use App\Ai\OpportunityClassifier;
use App\Ai\SourceDiscovery;
use App\Importing\CandidateProfiles;
use App\Importing\OpportunityImporter;
use App\Importing\OpportunityNormalizer;
use App\Importing\SourceHttp;
use App\Jobs\FetchNewOpportunities;
use App\Models\Candidate;
use App\Models\City;
use App\Models\Company;
use App\Models\Field;
use App\Models\ImportRun;
use App\Models\ImportSource;
use App\Models\Opportunity;
use App\Models\OpportunitySource;
use App\Models\User;
use Database\Seeders\ImportSourceSeeder;
use Database\Seeders\OpportunitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OpportunityImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-15 10:00:00', 'UTC'));
        SourceHttp::$resolveUsing = fn () => ['93.184.216.34'];
        config(['importer.csv_path' => sys_get_temp_dir().'/nextward-test-'.uniqid().'.csv']);
        Field::create(['name' => 'Web Development']);
        // Most tests here cover other rules with any kind of title; the scope tests turn it back on.
        config(['importer.scope' => null]);
    }

    protected function tearDown(): void
    {
        SourceHttp::$resolveUsing = null;
        OpportunityClassifier::resetOffReason();
        @unlink(config('importer.csv_path'));
        parent::tearDown();
    }

    public function test_seeded_listings_stay_public_for_the_hand_check_window(): void
    {
        $this->travelTo(Carbon::parse('2026-10-15 15:00:00', 'UTC'));
        $this->seed(OpportunitySeeder::class);
        $this->assertSame(6, Opportunity::available()->count());
        $this->assertSame(0, Opportunity::where('posted_at', '<', now()->subDays(30))->count());

        $this->travelTo(Carbon::parse('2026-10-20 12:00:00', 'UTC'));
        app(OpportunityImporter::class)->reconcile();
        $this->assertSame(6, Opportunity::available()->count());

        $this->travelTo(Carbon::parse('2026-10-30 12:00:00', 'UTC'));
        app(OpportunityImporter::class)->reconcile();
        $this->assertSame(0, Opportunity::available()->count());
    }

    public function test_daily_sources_are_seeded_enabled(): void
    {
        $this->seed(ImportSourceSeeder::class);

        $enabled = ImportSource::where('enabled', true)->where('permission_status', 'approved')->pluck('key');
        $this->assertNotContains('tanitjobs', $enabled);
        $this->assertNotContains('keejob', $enabled);
        $this->assertFalse(ImportSource::where('provider', 'jsonld')->exists());
        $this->assertTrue($enabled->contains(fn ($key) => str_starts_with($key, 'greenhouse-')));
    }

    public function test_imports_only_add_listings_a_profile_wants_and_add_the_rest_once_one_does(): void
    {
        Http::fake([
            '*/robots.txt' => Http::response('', 404),
            'boards-api.greenhouse.io/v1/boards/acme/jobs*' => Http::response(['jobs' => [
                $this->greenhouseJob(1, 'Mobile Developer Intern', 'Paris, France', 1),
                $this->greenhouseJob(2, 'Data Engineer Intern', 'Berlin, Germany', 2),
            ]]),
        ]);
        $this->source(['key' => 'greenhouse-acme', 'provider' => 'greenhouse',
            'url' => 'https://boards-api.greenhouse.io/v1/boards/acme/jobs?content=true',
            'allowed_domains' => ['boards-api.greenhouse.io', 'job-boards.greenhouse.io'], 'settings' => ['scope' => 'reachable']]);
        Field::create(['name' => 'Mobile Development']);
        Field::create(['name' => 'Data Science & AI']);
        CandidateProfiles::save('Resume', ['fields' => ['Mobile Development'], 'kinds' => ['internship']]);
        // Seeded demo accounts never count as profiles.
        Candidate::create(['field_id' => Field::where('name', 'Data Science & AI')->value('id')])->user()
            ->create(['name' => 'Demo', 'email' => 'demo@example.com', 'password' => 'password']);

        $this->artisan('opportunities:fetch --count=5 --no-csv')->expectsOutputToContain('against 1 candidate profile.')
            ->expectsOutputToContain('1 new opportunity added.')->assertSuccessful();
        $this->assertSame(['Mobile Developer Intern'], Opportunity::pluck('title')->all());
        $this->assertSame('unmatched', OpportunitySource::where('external_id', '2')->value('state'));

        CandidateProfiles::save('Data', ['fields' => ['Data Science & AI']]);
        $this->artisan('opportunities:fetch --count=5 --no-csv')->expectsOutputToContain('1 new opportunity added.')->assertSuccessful();
        $this->assertSame(2, Opportunity::count());

        $this->assertTrue(CandidateProfiles::forget('Data'));
        $this->assertSame(['Resume'], array_keys(CandidateProfiles::saved()));
    }

    public function test_a_candidate_profile_matches_by_shared_skills_and_respects_kind_and_level(): void
    {
        $profiles = new CandidateProfiles([['name' => 'A', 'fields' => [], 'skills' => ['Laravel', 'Vue'], 'kinds' => ['job'],
            'seniority' => ['mid'], 'countries' => [], 'country' => 'TN']]);
        $job = ['kind' => 'job', 'seniority' => 'mid', 'excluded_countries' => []];

        $this->assertTrue($profiles->wants($job, 'Web Development', ['laravel', 'Vue', 'Docker']));
        $this->assertFalse($profiles->wants($job, 'Web Development', ['Laravel']));
        $this->assertFalse($profiles->wants(['kind' => 'internship'] + $job, null, ['Laravel', 'Vue']));
        $this->assertFalse($profiles->wants(['seniority' => 'senior'] + $job, null, ['Laravel', 'Vue']));
        $this->assertFalse($profiles->wants(['excluded_countries' => ['TN']] + $job, null, ['Laravel', 'Vue']));
    }

    public function test_fetch_only_adds_recent_listings_and_leaves_older_ones_for_the_daily_import(): void
    {
        Http::fake([
            '*/robots.txt' => Http::response('', 404),
            'boards-api.greenhouse.io/v1/boards/acme/jobs*' => Http::response(['jobs' => [
                $this->greenhouseJob(1, 'Backend Intern', 'Paris, France', 3),
                $this->greenhouseJob(2, 'Frontend Intern', 'Paris, France', 20),
            ]]),
        ]);
        $this->source(['key' => 'greenhouse-acme', 'provider' => 'greenhouse',
            'url' => 'https://boards-api.greenhouse.io/v1/boards/acme/jobs?content=true',
            'allowed_domains' => ['boards-api.greenhouse.io', 'job-boards.greenhouse.io'], 'settings' => ['scope' => 'reachable']]);

        $this->artisan('opportunities:fetch --count=5 --no-csv --days=14')->expectsOutputToContain('1 new opportunity added.')->assertSuccessful();
        $this->assertSame(['Backend Intern'], Opportunity::pluck('title')->all());
        $this->assertSame('pending', OpportunitySource::where('external_id', '2')->value('state'));

        $this->travel(1)->days();
        app(OpportunityImporter::class)->run(ImportSource::sole(), 100);
        $this->assertSame(2, Opportunity::count());
    }

    public function test_discovery_turns_down_tunisian_sites(): void
    {
        $result = app(SourceDiscovery::class)->store(['provider' => 'sitemap', 'board' => 'https://www.example.tn/sitemap.xml', 'company' => 'Example']);

        $this->assertSame(['skipped', 'Rejected: Nextward no longer reads Tunisian job sites.'], [$result[0], $result[2]]);
        $this->assertFalse(ImportSource::exists());
    }

    public function test_board_import_keeps_recent_reachable_roles_and_reads_the_list_payload(): void
    {
        Http::fake([
            '*/robots.txt' => Http::response('', 404),
            'boards-api.greenhouse.io/v1/boards/acme/jobs*' => Http::response(['jobs' => [
                $this->greenhouseJob(1, 'Software Engineering Intern', 'San Francisco, California, United States', 2),
                $this->greenhouseJob(2, 'Backend Engineer', 'Remote US', 3),
                $this->greenhouseJob(3, 'Platform Engineer', 'Home based - Worldwide', 1),
                $this->greenhouseJob(4, 'Data Intern', 'Berlin, Germany', 90),
            ]]),
        ]);
        $source = $this->source(['key' => 'greenhouse-acme', 'provider' => 'greenhouse',
            'url' => 'https://boards-api.greenhouse.io/v1/boards/acme/jobs?content=true',
            'allowed_domains' => ['boards-api.greenhouse.io', 'job-boards.greenhouse.io'],
            'settings' => ['scope' => 'reachable', 'authoritative_employer' => true]]);

        app(OpportunityImporter::class)->run($source, 100);

        $this->assertEqualsCanonicalizing(['Software Engineering Intern', 'Platform Engineer'], Opportunity::pluck('title')->all());
        $this->assertSame('remote', Opportunity::where('title', 'Platform Engineer')->value('work_type'));
        $this->assertSame('Job is not open to candidates in Tunisia.', OpportunitySource::where('external_id', '2')->value('error'));
        $this->assertSame('Posted more than 30 days ago.', OpportunitySource::where('external_id', '4')->value('error'));
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'boards-api.greenhouse.io/v1/boards/acme/jobs/'));
    }

    public function test_old_imported_roles_are_closed_by_reconcile(): void
    {
        $company = Company::forceCreate(['name' => 'Acme']);
        $role = Opportunity::create(['title' => 'Old role', 'description' => str_repeat('Old role details. ', 10),
            'company_id' => $company->id, 'field_id' => Field::first()->id, 'origin' => 'imported',
            'application_mode' => 'external', 'posted_at' => now()->subDays(61)]);

        app(OpportunityImporter::class)->reconcile();

        $this->assertSame('closed', $role->fresh()->status);
    }

    public function test_keejob_style_stage_titles_are_opportunities(): void
    {
        $normalizer = new OpportunityNormalizer();
        $kind = fn (string $title, array $types = ['OTHER']) => $normalizer->normalize([
            'title' => $title, 'description' => str_repeat('Description du poste et des missions. ', 5),
            'employer' => 'Acme', 'application_url' => 'https://www.keejob.com/offres-emploi/1/x/', 'employment_types' => $types,
        ])['kind'];

        $this->assertSame('internship', $kind('Stage – Business Developer B2B'));
        $this->assertSame('internship', $kind('Stage Technicien (H/F)'));
        $this->assertSame('internship', $kind('Campus Assistant - Stage (6 mois)'));
        $this->assertSame('job', $kind('Stage Manager'));
        $this->assertSame('job', $kind('Backend Engineer, Late-stage startup'));
        $this->assertSame('job', $kind('Agent de Guichet', ['FULL_TIME', 'TEMPORARY', 'INTERN']));
        $this->assertSame('internship', $kind('Assistant marketing', ['INTERN']));
    }

    public function test_remote_roles_tied_to_another_country_are_not_open_to_tunisia(): void
    {
        $normalize = fn (array $input) => (new OpportunityNormalizer())->normalize($input + [
            'title' => 'Software Engineer', 'description' => str_repeat('Build and run the product with the team. ', 5),
            'employer' => 'Acme', 'application_url' => 'https://jobs.lever.co/acme/1', 'work_type' => 'remote',
        ]);

        $this->assertSame('restricted', $normalize(['location' => 'New York, NY', 'country_code' => 'US'])['remote_eligibility']);
        $this->assertSame('restricted', $normalize(['location' => 'Canada (Remote)'])['remote_eligibility']);
        $this->assertSame('unknown', $normalize(['location' => 'Home based - EMEA'])['remote_eligibility']);
        $this->assertSame('restricted', $normalize(['location' => 'San Francisco Bay Area or Remote (U.S.)'])['remote_eligibility']);
        $this->assertSame('restricted', $normalize(['location' => 'Remote, AMER', 'description' => str_repeat('We are fully remote and hire globally. ', 5)])['remote_eligibility']);
        $this->assertSame('remote', $normalize(['location' => 'Home based - Worldwide', 'work_type' => null])['work_type']);
    }

    public function test_links_with_accented_letters_are_encoded(): void
    {
        $data = (new OpportunityNormalizer())->normalize([
            'title' => 'Chargé de recrutement', 'description' => str_repeat('Description du poste et des missions. ', 5),
            'employer' => 'Acme', 'application_url' => 'https://www.tanitjobs.com/job/2062650/chargé-de-recrutement/',
        ]);

        $this->assertSame('https://www.tanitjobs.com/job/2062650/charg%C3%A9-de-recrutement/', $data['application_url']);
    }

    public function test_the_same_employer_and_role_on_two_tunisian_boards_is_one_listing(): void
    {
        Http::fake([
            '*/robots.txt' => Http::response("User-agent: *\nAllow: /\n"),
            'www.tanitjobs.com/sitemap.xml' => Http::response($this->sitemap(['https://www.tanitjobs.com/job/11/stage-web/', 'https://www.tanitjobs.com/job/12/comptable/'])),
            'www.tanitjobs.com/job/11/*' => Http::response($this->jobPage('Stage Développeur Web (H/F)', 'ACME SARL', null, 'https://www.tanitjobs.com/job/11/stage-web/')),
            'www.tanitjobs.com/job/12/*' => Http::response($this->jobPage('Comptable', 'ACME SARL', null, 'https://www.tanitjobs.com/job/12/comptable/')),
            'www.keejob.com/sitemap-jobs.xml' => Http::response($this->sitemap(['https://www.keejob.com/offres-emploi/21/stage-web/'])),
            'www.keejob.com/offres-emploi/21/*' => Http::response($this->jobPage('Stage Développeur Web', 'Acme', 'https://www.keejob.com/offres-emploi/entreprises/5/acme/', 'https://www.keejob.com/offres-emploi/21/stage-web/')),
        ]);
        $tanitjobs = $this->source(['key' => 'tanitjobs', 'url' => 'https://www.tanitjobs.com/sitemap.xml',
            'allowed_domains' => ['www.tanitjobs.com'], 'settings' => ['job_path_prefix' => '/job/']]);
        $keejob = $this->source(['key' => 'keejob', 'url' => 'https://www.keejob.com/sitemap-jobs.xml',
            'allowed_domains' => ['www.keejob.com'], 'settings' => ['job_path_prefix' => '/offres-emploi/']]);

        app(OpportunityImporter::class)->run($tanitjobs, 100);
        app(OpportunityImporter::class)->run($keejob, 100);

        $this->assertSame(1, Company::count());
        $this->assertNull(Company::first()->website);
        $this->assertSame(2, Opportunity::count());
        $stage = Opportunity::where('kind', 'internship')->sole();
        $this->assertSame(2, $stage->sources()->count());
        $this->assertSame('https://www.tanitjobs.com/job/11/stage-web/', $stage->application_url);
    }

    public function test_a_curated_listing_and_the_employer_board_copy_are_one_listing(): void
    {
        $this->travelTo(Carbon::parse('2026-10-15 15:00:00', 'UTC'));
        $this->seed(OpportunitySeeder::class);
        $title = 'Graduate Software Engineer, Open Source and Linux, Canonical Ubuntu';
        Http::fake([
            '*/robots.txt' => Http::response('', 404),
            'boards-api.greenhouse.io/v1/boards/canonical/jobs*' => Http::response(['jobs' => [
                ['company_name' => 'Canonical', 'first_published' => '2026-10-05T15:46:26Z'] + $this->greenhouseJob(8249005, $title, 'Home based - Worldwide', 10),
            ]]),
        ]);
        $source = $this->source(['key' => 'greenhouse-canonical', 'provider' => 'greenhouse',
            'url' => 'https://boards-api.greenhouse.io/v1/boards/canonical/jobs?content=true',
            'allowed_domains' => ['boards-api.greenhouse.io', 'job-boards.greenhouse.io'], 'settings' => ['scope' => 'reachable']]);

        app(OpportunityImporter::class)->run($source, 100);

        $this->assertSame(1, Opportunity::where('title', $title)->count());
        $this->assertSame(2, Opportunity::where('title', $title)->sole()->sources()->count());
        $this->assertSame(1, Company::where('name', 'Canonical')->count());
    }

    public function test_fetch_adds_the_requested_number_and_never_repeats_or_restores_deleted_ones(): void
    {
        Http::fake([
            '*/robots.txt' => Http::response('', 404),
            'boards-api.greenhouse.io/v1/boards/acme/jobs*' => Http::response(['jobs' => [
                $this->greenhouseJob(1, 'Design Intern', 'Paris, France', 1),
                $this->greenhouseJob(2, 'Data Intern', 'Berlin, Germany', 2),
                $this->greenhouseJob(3, 'Product Intern', 'Remote', 3),
            ]]),
        ]);
        $this->source(['key' => 'greenhouse-acme', 'provider' => 'greenhouse',
            'url' => 'https://boards-api.greenhouse.io/v1/boards/acme/jobs?content=true',
            'allowed_domains' => ['boards-api.greenhouse.io', 'job-boards.greenhouse.io'], 'settings' => ['scope' => 'reachable']]);

        $this->artisan('opportunities:fetch --count=2')->expectsOutputToContain('2 new opportunities added.')->assertSuccessful();
        $this->assertSame(2, Opportunity::count());

        $this->artisan('opportunities:fetch --count=2')->expectsOutputToContain('1 new opportunity added.')->assertSuccessful();
        $this->artisan('opportunities:fetch --count=2')->expectsOutputToContain('No new opportunities found.')->assertSuccessful();
        $this->assertSame(3, Opportunity::count());

        Opportunity::where('title', 'Design Intern')->delete();
        $this->travel(1)->days();
        app(OpportunityImporter::class)->run(ImportSource::sole(), 100);
        $this->assertSame(0, Opportunity::where('title', 'Design Intern')->count());
        $this->assertSame(2, Opportunity::count());
    }

    public function test_the_fetch_button_adds_new_listings_from_every_source_and_never_repeats_them(): void
    {
        Http::fake([
            '*/robots.txt' => Http::response('', 404),
            'boards-api.greenhouse.io/v1/boards/acme/jobs*' => Http::response(['jobs' => [
                $this->greenhouseJob(1, 'Design Intern', 'Paris, France', 1), $this->greenhouseJob(2, 'Data Intern', 'Berlin, Germany', 2),
            ]]),
            'boards-api.greenhouse.io/v1/boards/beta/jobs*' => Http::response(['jobs' => [
                ['company_name' => 'Beta'] + $this->greenhouseJob(3, 'Backend Engineer', 'Remote', 1),
                ['company_name' => 'Beta'] + $this->greenhouseJob(4, 'Frontend Engineer', 'Remote', 2),
            ]]),
        ]);
        foreach (['acme', 'beta'] as $board) {
            $this->source(['key' => "greenhouse-{$board}", 'provider' => 'greenhouse', 'url' => "https://boards-api.greenhouse.io/v1/boards/{$board}/jobs?content=true",
                'allowed_domains' => ['boards-api.greenhouse.io', 'job-boards.greenhouse.io'], 'settings' => ['scope' => 'reachable']]);
        }
        $this->source(['key' => 'remoteok', 'provider' => 'remoteok', 'url' => 'https://remoteok.com/api', 'allowed_domains' => ['remoteok.com'], 'publication_mode' => 'leads']);
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->post(route('imports.fetch'), ['count' => 3])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertSame(3, Opportunity::count());
        $this->actingAs($admin)->get(route('imports.sources.index'))->assertInertia(fn ($page) => $page
            ->where('fetch.running', false)->where('fetch.count', 3)->has('fetch.added', 3)->where('fetch.by', $admin->name));
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'remoteok.com'));

        $this->actingAs($admin)->post(route('imports.fetch'), ['count' => 10])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('imports.fetch'), ['count' => 10])->assertSessionHasNoErrors();

        $this->assertSame(4, Opportunity::count());
        $this->assertSame(4, Opportunity::distinct()->count('job_key'));
        $this->actingAs($admin)->get(route('imports.sources.index'))->assertInertia(fn ($page) => $page->has('fetch.added', 0)->where('fetch.read', 2));
    }

    public function test_a_second_fetch_waits_for_the_first_and_only_admins_can_start_one(): void
    {
        Queue::fake();
        $this->source(['key' => 'greenhouse-acme', 'provider' => 'greenhouse', 'url' => 'https://boards-api.greenhouse.io/v1/boards/acme/jobs?content=true',
            'allowed_domains' => ['boards-api.greenhouse.io', 'job-boards.greenhouse.io']]);
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs(User::factory()->create())->post(route('imports.fetch'), ['count' => 10])->assertForbidden();
        $this->actingAs($admin)->post(route('imports.fetch'), ['count' => 500])->assertSessionHasErrors('count');
        $this->actingAs($admin)->post(route('imports.fetch'), ['count' => 10])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('imports.fetch'), ['count' => 10])->assertSessionHasErrors(['count' => 'A fetch is already running. Wait for it to finish.']);
        Queue::assertPushed(FetchNewOpportunities::class, 1);

        // A fetch whose worker stopped no longer blocks a new one.
        $this->travel(20)->minutes();
        $this->actingAs($admin)->get(route('imports.sources.index'))->assertInertia(fn ($page) => $page->where('fetch.running', false)->where('fetch.stopped', true));
        $this->actingAs($admin)->post(route('imports.fetch'), ['count' => 10])->assertSessionHasNoErrors();
    }

    public function test_fetch_with_a_focus_only_adds_matching_titles_and_leaves_the_rest_for_later(): void
    {
        Http::fake([
            '*/robots.txt' => Http::response('', 404),
            'boards-api.greenhouse.io/v1/boards/acme/jobs*' => Http::response(['jobs' => [
                $this->greenhouseJob(1, 'Account Executive Intern', 'Paris, France', 1),
                $this->greenhouseJob(2, 'Mobile Developer Intern (Flutter)', 'Paris, France', 2),
            ]]),
        ]);
        $this->source(['key' => 'greenhouse-acme', 'provider' => 'greenhouse',
            'url' => 'https://boards-api.greenhouse.io/v1/boards/acme/jobs?content=true',
            'allowed_domains' => ['boards-api.greenhouse.io', 'job-boards.greenhouse.io'], 'settings' => ['scope' => 'reachable']]);

        Field::create(['name' => 'Mobile Development']);
        Field::create(['name' => 'Software Engineering']);

        $this->artisan('opportunities:fetch --focus=tech --count=5')->expectsOutputToContain('1 new opportunity added.')->assertSuccessful();

        $this->assertSame(['Mobile Developer Intern (Flutter)'], Opportunity::pluck('title')->all());
        $this->assertSame('Mobile Development', Opportunity::sole()->field->name);
        $this->assertNull(OpportunitySource::where('external_id', '1')->value('last_verified_at'));
    }

    public function test_ai_review_sets_the_field_and_keeps_non_software_roles_out_of_a_tech_fetch(): void
    {
        $this->app->instance(OpportunityClassifier::class, new class extends OpportunityClassifier {
            public function enabled(): bool { return true; }

            public function classifyMany(array $listings, array $fields, array $skills = []): array
            {
                return array_map(function ($data) {
                    $software = ! str_contains($data['title'], 'Trainer');
                    return ['in_scope' => $software, 'quality' => 'good', 'quality_reason' => '', 'field' => $software ? 'Mobile Development' : 'Other',
                        'kind' => 'job', 'seniority' => 'senior', 'work_type' => 'remote', 'employment_types' => [], 'program_type' => 'none', 'skills' => [],
                        'applicant_scope' => 'anywhere', 'eligible_countries' => [], 'excluded_countries' => [], 'relocation' => 'unknown',
                        'visa_sponsorship' => 'unknown', 'evidence' => 'We hire anywhere in the world.', 'model' => 'test'];
                }, $listings);
            }
        });
        Field::create(['name' => 'Mobile Development']);
        Http::fake([
            '*/robots.txt' => Http::response('', 404),
            'boards-api.greenhouse.io/v1/boards/acme/jobs*' => Http::response(['jobs' => [
                $this->greenhouseJob(1, 'Web Developer Trainer', 'Remote', 1),
                $this->greenhouseJob(2, 'Flutter Developer', 'Remote', 2),
            ]]),
        ]);
        $this->source(['key' => 'greenhouse-acme', 'provider' => 'greenhouse',
            'url' => 'https://boards-api.greenhouse.io/v1/boards/acme/jobs?content=true',
            'allowed_domains' => ['boards-api.greenhouse.io', 'job-boards.greenhouse.io'], 'settings' => ['scope' => 'reachable']]);

        $this->artisan('opportunities:fetch --focus=tech --count=5 --no-csv')->expectsOutputToContain('1 new opportunity added.')->assertSuccessful();

        $role = Opportunity::sole();
        $this->assertSame(['Flutter Developer', 'Mobile Development', 'senior', 'eligible', 'anywhere'], [$role->title, $role->field->name, $role->seniority, $role->remote_eligibility, $role->applicant_scope]);
        $this->assertSame('pending', OpportunitySource::where('external_id', '1')->value('state'));
        $this->assertSame('test', OpportunitySource::where('external_id', '2')->first()->ai_review['model']);
    }

    public function test_every_import_keeps_to_the_it_scope_and_rejects_the_rest_for_good(): void
    {
        config(['importer.scope' => 'tech']);
        foreach (['Software Engineering', 'DevOps & Cloud', 'Data Science & AI'] as $name) { Field::create(['name' => $name]); }
        Http::fake([
            '*/robots.txt' => Http::response('', 404),
            'boards-api.greenhouse.io/v1/boards/acme/jobs*' => Http::response(['jobs' => [
                $this->greenhouseJob(1, 'Account Executive', 'Remote', 1),
                $this->greenhouseJob(2, 'DevOps Engineer', 'Remote', 1),
                $this->greenhouseJob(3, 'Data Engineer', 'Remote', 2),
                $this->greenhouseJob(4, 'Mechanical Engineer', 'Remote', 2),
                $this->greenhouseJob(5, 'IT Recruiter', 'Remote', 3),
            ]]),
        ]);
        $source = $this->source(['key' => 'greenhouse-acme', 'provider' => 'greenhouse',
            'url' => 'https://boards-api.greenhouse.io/v1/boards/acme/jobs?content=true',
            'allowed_domains' => ['boards-api.greenhouse.io', 'job-boards.greenhouse.io']]);

        app(OpportunityImporter::class)->run($source, 100);

        $this->assertEqualsCanonicalizing(['DevOps Engineer', 'Data Engineer'], Opportunity::pluck('title')->all());
        $this->assertSame('DevOps & Cloud', Opportunity::where('title', 'DevOps Engineer')->sole()->field->name);
        foreach (['1', '4', '5'] as $id) {
            $item = OpportunitySource::where('external_id', $id)->sole();
            $this->assertSame(['rejected', 'Outside IT scope.'], [$item->state, $item->error]);
        }
        // Titles outside the scope are rejected before the listing is read.
        $this->assertNull(OpportunitySource::where('external_id', '4')->value('last_verified_at'));
        $this->assertNotNull(OpportunitySource::where('external_id', '5')->value('last_verified_at'));

        $this->travel(1)->days();
        app(OpportunityImporter::class)->run($source->fresh(), 100);
        $this->assertSame(2, Opportunity::count());
    }

    public function test_a_board_link_without_a_title_is_read_before_the_scope_decides(): void
    {
        config(['importer.scope' => 'tech']);
        Http::fake([
            '*/robots.txt' => Http::response('', 404),
            'www.tanitjobs.com/sitemap.xml' => Http::response($this->sitemap(['https://www.tanitjobs.com/job/11/', 'https://www.tanitjobs.com/job/12/'])),
            'www.tanitjobs.com/job/11/*' => Http::response($this->jobPage('Développeur Web Laravel', 'ACME SARL', null, 'https://www.tanitjobs.com/job/11/')),
            'www.tanitjobs.com/job/12/*' => Http::response($this->jobPage('Comptable', 'ACME SARL', null, 'https://www.tanitjobs.com/job/12/')),
        ]);
        $source = $this->source(['key' => 'tanitjobs', 'url' => 'https://www.tanitjobs.com/sitemap.xml',
            'allowed_domains' => ['www.tanitjobs.com'], 'settings' => ['job_path_prefix' => '/job/']]);

        app(OpportunityImporter::class)->run($source, 100);

        $this->assertSame(['Développeur Web Laravel'], Opportunity::pluck('title')->all());
        $this->assertSame('Outside IT scope.', OpportunitySource::where('external_id', hash('sha256', 'https://www.tanitjobs.com/job/12/'))->value('error'));
    }

    public function test_an_admin_can_still_publish_a_listing_outside_the_scope(): void
    {
        config(['importer.scope' => 'tech']);
        Http::fake([
            '*/robots.txt' => Http::response('', 404),
            'boards-api.greenhouse.io/v1/boards/acme/jobs*' => Http::response(['jobs' => [$this->greenhouseJob(1, 'Developer Advocate', 'Remote', 1)]]),
        ]);
        $source = $this->source(['key' => 'greenhouse-acme', 'provider' => 'greenhouse',
            'url' => 'https://boards-api.greenhouse.io/v1/boards/acme/jobs?content=true',
            'allowed_domains' => ['boards-api.greenhouse.io', 'job-boards.greenhouse.io']]);
        app(OpportunityImporter::class)->run($source, 100);
        $item = OpportunitySource::sole();
        $this->assertSame(['rejected', 'Outside IT scope.'], [$item->state, $item->error]);

        app(OpportunityImporter::class)->publish($item->fresh());

        $this->assertSame('Developer Advocate', Opportunity::sole()->title);
    }

    public function test_openai_reads_each_new_listing_through_the_ai_package(): void
    {
        $this->useOpenAi();
        Field::create(['name' => 'Mobile Development']);
        $review = ['listings' => [$this->review(1, ['kind' => 'internship', 'seniority' => 'entry'])]];
        Http::fake([
            '*/robots.txt' => Http::response('', 404),
            'boards-api.greenhouse.io/v1/boards/acme/jobs*' => Http::response(['jobs' => [$this->greenhouseJob(1, 'Flutter Developer Intern', 'Remote', 1)]]),
            'api.openai.com/v1/responses' => Http::response($this->openAiReply([$this->openAiText(json_encode($review))])),
        ]);
        $this->source(['key' => 'greenhouse-acme', 'provider' => 'greenhouse',
            'url' => 'https://boards-api.greenhouse.io/v1/boards/acme/jobs?content=true',
            'allowed_domains' => ['boards-api.greenhouse.io', 'job-boards.greenhouse.io'], 'settings' => ['scope' => 'reachable']]);

        $this->artisan('opportunities:fetch --focus=tech --count=5 --no-csv')
            ->expectsOutputToContain('OpenAI reads each new listing')->expectsOutputToContain('1 new opportunity added.')->assertSuccessful();

        $role = Opportunity::sole();
        $this->assertSame(['Mobile Development', 'internship', 'eligible'], [$role->field->name, $role->kind, $role->remote_eligibility]);
        $this->assertSame('gpt-5.4-mini-2026-03-17', OpportunitySource::sole()->ai_review['model']);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/responses') && $request['model'] === 'gpt-5.4-mini'
            && $request['text']['format']['type'] === 'json_schema' && $request['reasoning']['effort'] === 'low');
    }

    public function test_the_ai_reads_new_listings_in_one_batch_and_judges_their_quality(): void
    {
        $this->useOpenAi();
        Field::create(['name' => 'Mobile Development']);
        Http::fake([
            '*/robots.txt' => Http::response('', 404),
            'boards-api.greenhouse.io/v1/boards/acme/jobs*' => Http::response(['jobs' => [
                $this->greenhouseJob(1, 'Flutter Developer', 'Remote', 1),
                $this->greenhouseJob(2, 'Crypto Trading Developer', 'Remote', 1),
                $this->greenhouseJob(3, 'Web Developer', 'Remote', 1),
            ]]),
            // No answer comes back for listing 3, so keyword rules sort it.
            'api.openai.com/v1/responses' => Http::response($this->openAiReply([$this->openAiText(json_encode(['listings' => [
                $this->review(1, ['skills' => ['Flutter', 'Dart', 'Not A Skill']]),
                $this->review(2, ['quality' => 'reject', 'quality_reason' => 'Asks candidates to pay a training fee.']),
            ]]))])),
        ]);
        $source = $this->source(['key' => 'greenhouse-acme', 'provider' => 'greenhouse',
            'url' => 'https://boards-api.greenhouse.io/v1/boards/acme/jobs?content=true',
            'allowed_domains' => ['boards-api.greenhouse.io', 'job-boards.greenhouse.io']]);

        app(OpportunityImporter::class)->run($source, 100);

        $this->assertCount(1, Http::recorded(fn ($request) => str_ends_with($request->url(), '/responses')));
        $this->assertEqualsCanonicalizing(['Flutter Developer', 'Web Developer'], Opportunity::pluck('title')->all());
        $flutter = Opportunity::where('title', 'Flutter Developer')->sole();
        $this->assertSame(['anywhere', 'eligible', 'Mobile Development'], [$flutter->applicant_scope, $flutter->remote_eligibility, $flutter->field->name]);
        $this->assertEqualsCanonicalizing(['Flutter', 'Dart'], $flutter->skills->pluck('name')->all());
        $rejected = OpportunitySource::where('external_id', '2')->sole();
        $this->assertSame(['rejected', 'Low quality: Asks candidates to pay a training fee.'], [$rejected->state, $rejected->error]);
        $this->assertSame('unknown', Opportunity::where('title', 'Web Developer')->sole()->applicant_scope);
        $this->assertNull(OpportunitySource::where('external_id', '3')->value('ai_review'));
    }

    public function test_a_role_that_needs_local_work_rights_is_not_open_to_remote_work_from_tunisia(): void
    {
        $data = app(OpportunityImporter::class)->withReview(['title' => 'Backend Engineer', 'description' => 'Build APIs.', 'work_type' => 'remote',
            'remote_eligibility' => 'unknown', 'relocation' => 'unknown', 'visa_sponsorship' => 'unknown', 'eligibility_evidence' => []],
            $this->review(1, ['applicant_scope' => 'local', 'eligible_countries' => ['US'], 'evidence' => 'Must be authorized to work in the US.']));

        $this->assertSame(['local', 'restricted', ['US']], [$data['applicant_scope'], $data['remote_eligibility'], $data['eligible_countries']]);
    }

    public function test_the_review_command_fills_who_can_apply_for_listings_already_online(): void
    {
        $this->useOpenAi();
        Field::create(['name' => 'Mobile Development']);
        Http::fake([
            '*/robots.txt' => Http::response('', 404),
            'boards-api.greenhouse.io/v1/boards/acme/jobs*' => Http::response(['jobs' => [$this->greenhouseJob(1, 'Flutter Developer', 'Remote', 1)]]),
        ]);
        config(['importer.ai.enabled' => false]);
        app(OpportunityImporter::class)->run($this->source(['key' => 'greenhouse-acme', 'provider' => 'greenhouse',
            'url' => 'https://boards-api.greenhouse.io/v1/boards/acme/jobs?content=true',
            'allowed_domains' => ['boards-api.greenhouse.io', 'job-boards.greenhouse.io']]), 100);
        $this->assertSame('unknown', Opportunity::sole()->applicant_scope);
        config(['importer.ai.enabled' => true]);
        Http::fake(['api.openai.com/v1/responses' => Http::response($this->openAiReply([$this->openAiText(json_encode(['listings' => [$this->review(1)]]))]))]);

        $this->artisan('opportunities:review')->expectsOutputToContain('Read 1 of 1 listing(s).')->assertSuccessful();

        $this->assertSame(['anywhere', 'Mobile Development'], [Opportunity::sole()->applicant_scope, Opportunity::sole()->field->name]);
        $this->assertEqualsCanonicalizing(['Flutter', 'Dart'], Opportunity::sole()->skills->pluck('name')->all());
    }

    public function test_a_bad_openai_key_stops_ai_for_the_run_and_keyword_rules_carry_on(): void
    {
        $this->useOpenAi();
        Http::fake([
            '*/robots.txt' => Http::response('', 404),
            'boards-api.greenhouse.io/v1/boards/acme/jobs*' => Http::response(['jobs' => [
                $this->greenhouseJob(1, 'Software Engineering Intern', 'Paris, France', 1),
                $this->greenhouseJob(2, 'Web Developer Intern', 'Paris, France', 2),
            ]]),
            'api.openai.com/v1/responses' => Http::response(['error' => ['message' => 'Incorrect API key provided.', 'code' => 'invalid_api_key']], 401),
        ]);
        $this->source(['key' => 'greenhouse-acme', 'provider' => 'greenhouse',
            'url' => 'https://boards-api.greenhouse.io/v1/boards/acme/jobs?content=true',
            'allowed_domains' => ['boards-api.greenhouse.io', 'job-boards.greenhouse.io'], 'settings' => ['scope' => 'reachable']]);

        $this->artisan('opportunities:fetch --count=2 --no-csv')
            ->expectsOutputToContain('OpenAI stopped during this run, so keyword rules sorted the rest: Incorrect API key provided.')
            ->expectsOutputToContain('2 new opportunities added.')->assertSuccessful();

        $this->assertCount(1, Http::recorded(fn ($request) => str_ends_with($request->url(), '/responses')));
    }

    public function test_a_csv_from_one_machine_adds_the_same_opportunities_once_on_another(): void
    {
        Http::fake([
            '*/robots.txt' => Http::response('', 404),
            'boards-api.greenhouse.io/v1/boards/acme/jobs*' => Http::response(['jobs' => [
                $this->greenhouseJob(1, 'Software Engineering Intern', 'Paris, France', 1),
                $this->greenhouseJob(2, 'Mobile Engineering Intern', 'Paris, France', 2),
            ]]),
        ]);
        $this->source(['key' => 'greenhouse-acme', 'provider' => 'greenhouse',
            'url' => 'https://boards-api.greenhouse.io/v1/boards/acme/jobs?content=true',
            'allowed_domains' => ['boards-api.greenhouse.io', 'job-boards.greenhouse.io'], 'settings' => ['scope' => 'reachable']]);
        $this->artisan('opportunities:fetch --count=2')->expectsOutputToContain('Saved 2 to')->assertSuccessful();

        // The server starts without these listings but with the same sources.
        OpportunitySource::query()->delete();
        Opportunity::query()->delete();
        Company::query()->delete();

        $this->artisan('opportunities:import-csv')->expectsOutputToContain('2 added, 0 already here, 0 skipped.')->assertSuccessful();
        $this->artisan('opportunities:import-csv')->expectsOutputToContain('0 added, 2 already here, 0 skipped.')->assertSuccessful();
        $this->assertSame(2, Opportunity::available()->count());
        $this->assertSame(1, Company::count());
    }

    public function test_a_csv_checked_too_long_ago_is_not_published(): void
    {
        Http::fake([
            '*/robots.txt' => Http::response('', 404),
            'boards-api.greenhouse.io/v1/boards/acme/jobs*' => Http::response(['jobs' => [$this->greenhouseJob(1, 'Software Engineering Intern', 'Paris, France', 1)]]),
        ]);
        $this->source(['key' => 'greenhouse-acme', 'provider' => 'greenhouse',
            'url' => 'https://boards-api.greenhouse.io/v1/boards/acme/jobs?content=true',
            'allowed_domains' => ['boards-api.greenhouse.io', 'job-boards.greenhouse.io'], 'settings' => ['scope' => 'reachable']]);
        $this->artisan('opportunities:fetch --count=1')->assertSuccessful();
        OpportunitySource::query()->delete();
        Opportunity::query()->delete();

        $this->travel(3)->days();
        $this->artisan('opportunities:import-csv')->expectsOutputToContain('0 added, 0 already here, 1 skipped.')->assertSuccessful();
    }

    public function test_suggested_sources_are_checked_and_saved_switched_off(): void
    {
        Http::fake([
            '*/robots.txt' => Http::response('', 404),
            'boards-api.greenhouse.io/v1/boards/tunisoft/jobs' => Http::response(['jobs' => [['id' => 1, 'title' => 'Developer', 'absolute_url' => 'https://job-boards.greenhouse.io/tunisoft/jobs/1']]]),
            'boards-api.greenhouse.io/v1/boards/empty/jobs' => Http::response(['jobs' => []]),
        ]);
        $discovery = app(SourceDiscovery::class);
        $suggest = fn (string $board) => $discovery->store(['provider' => 'greenhouse', 'board' => $board, 'company' => 'TuniSoft', 'job_path_prefix' => '', 'why' => 'Hires developers in Tunis.']);

        $this->assertSame('added', $suggest('tunisoft')[0]);
        $this->assertSame('skipped', $suggest('tunisoft')[0]);
        $this->assertSame('skipped', $suggest('empty')[0]);
        $this->assertSame('skipped', $suggest('https://boards.greenhouse.io/x')[0]);

        $source = ImportSource::sole();
        $this->assertSame(['greenhouse-tunisoft', false, 'pending'], [$source->key, $source->enabled, $source->permission_status]);
        $this->assertSame(['boards-api.greenhouse.io', 'job-boards.greenhouse.io'], $source->allowed_domains);
    }

    public function test_openai_discovery_saves_the_sources_it_suggests(): void
    {
        $this->useOpenAi();
        $call = ['type' => 'function_call', 'id' => 'fc_1', 'call_id' => 'call_1', 'name' => 'suggest_source', 'status' => 'completed',
            'arguments' => json_encode(['provider' => 'greenhouse', 'board' => 'tunisoft', 'company' => 'TuniSoft', 'job_path_prefix' => '', 'why' => 'Hires developers in Tunis.'])];
        Http::fake([
            '*/robots.txt' => Http::response('', 404),
            'boards-api.greenhouse.io/v1/boards/tunisoft/jobs' => Http::response(['jobs' => [['id' => 1, 'title' => 'Developer', 'absolute_url' => 'https://job-boards.greenhouse.io/tunisoft/jobs/1']]]),
            'api.openai.com/v1/responses' => Http::sequence()
                ->push($this->openAiReply([['type' => 'web_search_call', 'id' => 'ws_1', 'status' => 'completed'], $call]))
                ->push($this->openAiReply([$this->openAiText('Saved one source.')])),
        ]);

        $this->artisan('opportunities:discover --max=1')->expectsOutputToContain('1 new source saved for review in Imports.')
            ->expectsOutputToContain('and 1 web searches.')->assertSuccessful();

        $source = ImportSource::sole();
        $this->assertSame(['greenhouse-tunisoft', false], [$source->key, $source->enabled]);
        $this->assertStringStartsWith('Suggested by OpenAI on', $source->permission_notes);
        $requests = Http::recorded(fn ($request) => str_ends_with($request->url(), '/responses'))->map(fn ($pair) => $pair[0]->data())->values();
        $this->assertSame('gpt-5.5', $requests[0]['model']);
        $this->assertContains('web_search', collect($requests[0]['tools'])->pluck('type')->all());
        $this->assertSame('call_1', collect($requests[1]['input'])->firstWhere('type', 'function_call_output')['call_id']);
    }

    public function test_follow_up_batches_reuse_the_saved_backlog(): void
    {
        $jobs = ['https://www.tanitjobs.com/job/31/a/', 'https://www.tanitjobs.com/job/32/b/', 'https://www.tanitjobs.com/job/33/c/'];
        Http::fake([
            '*/robots.txt' => Http::response('', 404),
            'www.tanitjobs.com/sitemap.xml' => Http::response($this->sitemap($jobs)),
            'www.tanitjobs.com/job/*' => fn ($request) => Http::response($this->jobPage('Stage '.basename($request->url()), 'Acme', null, $request->url())),
        ]);
        $source = $this->source(['key' => 'tanitjobs', 'url' => 'https://www.tanitjobs.com/sitemap.xml',
            'allowed_domains' => ['www.tanitjobs.com'], 'settings' => ['job_path_prefix' => '/job/']]);

        $first = app(OpportunityImporter::class)->run($source, 1);
        $this->assertTrue($first['pending']);
        while ($source->fresh()->items()->whereNull('last_verified_at')->exists()) {
            $this->travel(31)->seconds();
            app(OpportunityImporter::class)->run($source->fresh(), 1);
        }

        $this->assertSame(3, Opportunity::count());
        Http::assertSentCount(1 + 1 + 3);
        $this->assertCount(1, Http::recorded(fn ($request) => str_ends_with($request->url(), '/sitemap.xml')));
    }

    public function test_a_4xx_robots_file_means_no_rules_and_hybrid_roles_stay_hybrid(): void
    {
        Http::fake([
            'api.ashbyhq.com/robots.txt' => Http::response('Unauthorized', 401),
            'api.ashbyhq.com/posting-api/job-board/acme*' => Http::response(['jobs' => [[
                'id' => 'a1', 'title' => 'Design Intern', 'descriptionPlain' => str_repeat('Work with the design team on real projects. ', 4),
                'location' => 'New York, NY (HQ)', 'isRemote' => true, 'workplaceType' => 'Hybrid', 'employmentType' => 'Intern',
                'publishedAt' => now()->subDay()->toIso8601String(), 'jobUrl' => 'https://jobs.ashbyhq.com/acme/a1',
                'applyUrl' => 'https://jobs.ashbyhq.com/acme/a1/application', 'isListed' => true,
            ]]]),
        ]);
        $source = $this->source(['key' => 'ashby-acme', 'provider' => 'ashby', 'url' => 'https://api.ashbyhq.com/posting-api/job-board/acme',
            'allowed_domains' => ['api.ashbyhq.com', 'jobs.ashbyhq.com'], 'settings' => ['employer_name' => 'Acme', 'scope' => 'reachable']]);

        app(OpportunityImporter::class)->run($source, 100);

        $role = Opportunity::sole();
        $this->assertSame('hybrid', $role->work_type);
        $this->assertSame('internship', $role->kind);
    }

    public function test_sitemap_discovery_stops_cleanly_at_the_budget(): void
    {
        Http::fake([
            '*/robots.txt' => Http::response('', 404),
            'www.tanitjobs.com/sitemap_index.xml' => Http::response('<?xml version="1.0"?><sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"><sitemap><loc>https://www.tanitjobs.com/s1.xml</loc></sitemap><sitemap><loc>https://www.tanitjobs.com/s2.xml</loc></sitemap></sitemapindex>'),
            'www.tanitjobs.com/s1.xml' => Http::response($this->sitemap(['https://www.tanitjobs.com/job/41/a/'])),
            'www.tanitjobs.com/s2.xml' => Http::response($this->sitemap(['https://www.tanitjobs.com/job/42/b/'])),
        ]);
        $source = $this->source(['key' => 'tanitjobs', 'url' => 'https://www.tanitjobs.com/sitemap_index.xml', 'max_requests' => 3,
            'allowed_domains' => ['www.tanitjobs.com'], 'settings' => ['job_path_prefix' => '/job/']]);

        $result = app(OpportunityImporter::class)->run($source, 100);

        $this->assertSame('completed', $result['status']);
        $this->assertFalse($result['complete']);
        $this->assertSame(1, $source->items()->count());
        $this->assertSame('completed', ImportRun::sole()->status);
    }

    private function review(int $id, array $answers = []): array
    {
        return [...['id' => $id, 'in_scope' => true, 'quality' => 'good', 'quality_reason' => '', 'field' => 'Mobile Development', 'kind' => 'job',
            'seniority' => 'mid', 'work_type' => 'remote', 'employment_types' => ['FULL_TIME'], 'program_type' => 'none', 'skills' => ['Flutter', 'Dart'],
            'applicant_scope' => 'anywhere', 'eligible_countries' => [], 'excluded_countries' => [], 'relocation' => 'unknown',
            'visa_sponsorship' => 'unknown', 'evidence' => 'Hiring worldwide.'], ...$answers];
    }

    private function useOpenAi(): void
    {
        config(['importer.ai.enabled' => true, 'importer.ai.provider' => 'openai', 'ai.providers.openai.key' => 'test-key']);
    }

    private function openAiReply(array $output): array
    {
        return ['id' => 'resp_'.uniqid(), 'object' => 'response', 'status' => 'completed', 'model' => 'gpt-5.4-mini-2026-03-17',
            'output' => $output, 'usage' => ['input_tokens' => 100, 'output_tokens' => 20, 'total_tokens' => 120]];
    }

    private function openAiText(string $text): array
    {
        return ['type' => 'message', 'id' => 'msg_'.uniqid(), 'role' => 'assistant', 'status' => 'completed',
            'content' => [['type' => 'output_text', 'text' => $text, 'annotations' => []]]];
    }

    private function source(array $attributes): ImportSource
    {
        return ImportSource::create($attributes + [
            'name' => $attributes['key'], 'provider' => 'jsonld', 'permission_status' => 'approved',
            'permission_notes' => 'Approved for this test source.', 'publication_mode' => 'automatic', 'enabled' => true,
            'cadence_hours' => 24, 'request_delay_ms' => 0, 'max_requests' => 100,
        ]);
    }

    private function greenhouseJob(int $id, string $title, string $location, int $daysAgo): array
    {
        return ['id' => $id, 'title' => $title, 'company_name' => 'Acme', 'location' => ['name' => $location],
            'content' => htmlspecialchars('<p>'.str_repeat('You will build and ship product features with the team. ', 3).'</p>'),
            'absolute_url' => "https://job-boards.greenhouse.io/acme/jobs/{$id}", 'first_published' => now()->subDays($daysAgo)->toIso8601String()];
    }

    private function sitemap(array $urls): string
    {
        $entries = implode('', array_map(fn ($url) => "<url><loc>{$url}</loc></url>", $urls));
        return '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.$entries.'</urlset>';
    }

    private function jobPage(string $title, string $employer, ?string $sameAs, string $url): string
    {
        $posting = ['@context' => 'https://schema.org', '@type' => 'JobPosting', 'title' => $title, 'url' => $url,
            'description' => '<p>'.str_repeat('Rejoignez notre équipe pour travailler sur des projets concrets. ', 3).'</p>',
            'datePosted' => now()->subDays(2)->toDateString(), 'employmentType' => 'OTHER',
            'hiringOrganization' => array_filter(['@type' => 'Organization', 'name' => $employer, 'sameAs' => $sameAs]),
            'jobLocation' => ['@type' => 'Place', 'address' => ['@type' => 'PostalAddress', 'addressLocality' => 'Tunis', 'addressCountry' => 'TN']]];
        return '<html><head><script type="application/ld+json">'.json_encode($posting).'</script></head><body></body></html>';
    }
}

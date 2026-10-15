<?php

namespace Tests\Feature;

use App\Importing\OpportunityImporter;
use App\Importing\OpportunityNormalizer;
use App\Importing\SourceHttp;
use App\Models\City;
use App\Models\Company;
use App\Models\Field;
use App\Models\ImportRun;
use App\Models\ImportSource;
use App\Models\Internship;
use App\Models\OpportunitySource;
use Database\Seeders\ImportSourceSeeder;
use Database\Seeders\OpportunitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpportunityImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-15 10:00:00', 'UTC'));
        SourceHttp::$resolveUsing = fn () => ['93.184.216.34'];
        Field::create(['name' => 'Web Development']);
    }

    protected function tearDown(): void
    {
        SourceHttp::$resolveUsing = null;
        parent::tearDown();
    }

    public function test_seeded_listings_stay_public_for_the_hand_check_window(): void
    {
        $this->travelTo(Carbon::parse('2026-10-15 15:00:00', 'UTC'));
        $this->seed(OpportunitySeeder::class);
        $this->assertSame(6, Internship::available()->count());
        $this->assertSame(0, Internship::where('posted_at', '<', now()->subDays(30))->count());

        $this->travelTo(Carbon::parse('2026-10-20 12:00:00', 'UTC'));
        app(OpportunityImporter::class)->reconcile();
        $this->assertSame(6, Internship::available()->count());

        $this->travelTo(Carbon::parse('2026-10-30 12:00:00', 'UTC'));
        app(OpportunityImporter::class)->reconcile();
        $this->assertSame(0, Internship::available()->count());
    }

    public function test_daily_sources_are_seeded_enabled(): void
    {
        $this->seed(ImportSourceSeeder::class);

        $enabled = ImportSource::where('enabled', true)->where('permission_status', 'approved')->pluck('key');
        $this->assertContains('tanitjobs', $enabled);
        $this->assertContains('keejob', $enabled);
        $this->assertTrue($enabled->contains(fn ($key) => str_starts_with($key, 'greenhouse-')));
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

        $this->assertEqualsCanonicalizing(['Software Engineering Intern', 'Platform Engineer'], Internship::pluck('title')->all());
        $this->assertSame('remote', Internship::where('title', 'Platform Engineer')->value('work_type'));
        $this->assertSame('Job is not open to candidates in Tunisia.', OpportunitySource::where('external_id', '2')->value('error'));
        $this->assertSame('Posted more than 30 days ago.', OpportunitySource::where('external_id', '4')->value('error'));
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'boards-api.greenhouse.io/v1/boards/acme/jobs/'));
    }

    public function test_old_imported_roles_are_closed_by_reconcile(): void
    {
        $company = Company::forceCreate(['name' => 'Acme']);
        $role = Internship::create(['title' => 'Old role', 'description' => str_repeat('Old role details. ', 10),
            'company_id' => $company->id, 'field_id' => Field::first()->id, 'origin' => 'imported',
            'application_mode' => 'external', 'posted_at' => now()->subDays(61)]);

        app(OpportunityImporter::class)->reconcile();

        $this->assertSame('closed', $role->fresh()->status);
    }

    public function test_keejob_style_stage_titles_are_internships(): void
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
        $this->assertSame(2, Internship::count());
        $stage = Internship::where('kind', 'internship')->sole();
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

        $this->assertSame(1, Internship::where('title', $title)->count());
        $this->assertSame(2, Internship::where('title', $title)->sole()->sources()->count());
        $this->assertSame(1, Company::where('name', 'Canonical')->count());
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

        $this->assertSame(3, Internship::count());
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

        $role = Internship::sole();
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

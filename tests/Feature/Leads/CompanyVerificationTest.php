<?php

namespace Tests\Feature\Leads;

use App\Ai\OpportunityClassifier;
use App\Importing\CompanyLogos;
use App\Importing\CompanyVerifier;
use App\Importing\JobKey;
use App\Importing\OpportunityImporter;
use App\Importing\SourceHttp;
use App\Models\Company;
use App\Models\Field;
use App\Models\ImportSource;
use App\Models\Opportunity;
use App\Models\OpportunitySource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CompanyVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-25 10:00:00', 'UTC'));
        SourceHttp::$resolveUsing = fn () => ['93.184.216.34'];
        Field::create(['name' => 'Web Development']);
        config(['importer.scope' => null]);
    }

    protected function tearDown(): void
    {
        SourceHttp::$resolveUsing = null;
        parent::tearDown();
    }

    public function test_new_listings_wait_for_the_company_check_and_publish_once_it_passes(): void
    {
        config(['importer.verify_companies' => true]);
        $this->verifierAnswers([true]);
        $this->board([$this->job(1, 'Frontend Engineer')]);

        $role = Opportunity::sole();
        $this->assertSame('Acme', $role->company->name);
        $this->assertNotNull($role->company->verified_at);
        $this->assertSame('published', OpportunitySource::sole()->state);
    }

    public function test_listings_of_a_company_with_no_website_found_twice_are_rejected(): void
    {
        config(['importer.verify_companies' => true]);
        $this->verifierAnswers([false, false]);
        $source = $this->board([$this->job(1, 'Frontend Engineer')]);

        $item = OpportunitySource::sole();
        $this->assertSame(['waiting', 'Company website not checked yet.'], [$item->state, $item->error]);
        $this->assertSame(0, Opportunity::count());

        // The second try is a day later, on the next refresh.
        $this->travel(21)->hours();
        app(OpportunityImporter::class)->run($source, recheck: true);
        $this->assertSame(['rejected', 'Company website not found.'], [$item->fresh()->state, $item->fresh()->error]);
        $this->assertSame(0, Opportunity::count());
    }

    public function test_the_check_is_off_for_job_leads_and_companies_with_an_account(): void
    {
        config(['importer.verify_companies' => true]);
        $this->verifierAnswers([]);
        $company = Company::forceCreate(['name' => 'Acme', 'import_identity' => 'manual']);
        $company->user()->create(['name' => 'Acme', 'email' => 'jobs@acme.test', 'password' => bcrypt('secret-pass')]);
        $this->board([$this->job(1, 'Frontend Engineer')], ['company_id' => $company->id]);
        $this->assertSame('published', OpportunitySource::sole()->state);
    }

    public function test_the_ai_finds_the_website_and_the_site_check_fills_the_facts(): void
    {
        $this->aiOn();
        Http::fake([
            'api.openai.com/v1/responses' => Http::response($this->reply(['found' => true, 'website' => 'https://www.acme.dev', 'about' => 'Acme builds payment tools for small shops.',
                'size' => '51-200', 'founded_year' => 2016, 'headquarters' => 'Lyon, France', 'country_code' => 'FR', 'stage' => 'startup',
                'linkedin_url' => 'https://www.linkedin.com/company/acme-dev/', 'careers_url' => 'https://www.acme.dev/careers', 'sources' => ['https://www.acme.dev/about']])),
            'www.acme.dev*' => Http::response('<html><head><title>Payments for small shops</title><link rel="icon" href="/favicon.ico"><link rel="apple-touch-icon" href="/touch.png"></head>'
                .'<body><a href="https://job-boards.greenhouse.io/acme">Jobs</a></body></html>', 200, ['Content-Type' => 'text/html']),
        ]);
        $company = $this->companyWithBoard('Acme');

        $this->assertTrue(app(CompanyVerifier::class)->verify($company));
        $company->refresh();
        $this->assertNotNull($company->verified_at);
        $this->assertSame(['https://www.acme.dev', 'acme.dev', 'Lyon, France', 'FR', '51-200', 2016, 'startup'],
            [$company->website, $company->domain, $company->location, $company->country_code, $company->size, $company->founded_year, $company->stage]);
        $this->assertSame('https://www.acme.dev/touch.png', $company->logo_url);
        $this->assertSame('https://www.linkedin.com/company/acme-dev/', $company->linkedin_url);
        $this->assertTrue($company->verification['links_job_board']);
        $this->assertSame('ai', $company->verification['by']);
    }

    public function test_a_profile_page_or_a_site_about_someone_else_is_not_verified(): void
    {
        $this->aiOn();
        Http::fake([
            'api.openai.com/v1/responses' => Http::sequence()
                ->push($this->reply($this->facts('https://www.linkedin.com/company/acme')))
                ->push($this->reply($this->facts('https://acme-shoes.example'))),
            'acme-shoes.example*' => Http::response('<html><title>Comfortable shoes</title><body>Shoes for every season.</body></html>'),
        ]);
        $company = $this->companyWithBoard('Acme Robotics');
        $verifier = app(CompanyVerifier::class);

        $this->assertFalse($verifier->verify($company));
        $this->assertSame('No website found.', $company->fresh()->verification['error']);
        $this->assertFalse($verifier->verify($company->fresh()));
        $company->refresh();
        $this->assertSame('The website does not show the company name or link to its jobs.', $company->verification['error']);
        $this->assertSame(2, $company->research_attempts);
        $this->assertNull($company->verified_at);
    }

    public function test_without_ai_a_known_website_is_still_checked_and_nothing_counts_when_none_is_known(): void
    {
        Http::fake(['notion.so*' => Http::response('<html><title>Notion – The all-in-one workspace</title></html>')]);
        $known = Company::forceCreate(['name' => 'Notion Labs, Inc.', 'website' => 'https://notion.so', 'import_identity' => 'x:1']);
        $unknown = Company::forceCreate(['name' => 'Mystery Co', 'import_identity' => 'x:2']);

        $this->assertTrue(app(CompanyVerifier::class)->verify($known));
        $this->assertTrue($known->fresh()->verification['name_on_site']);
        $this->assertNull(app(CompanyVerifier::class)->verify($unknown));
        $this->assertSame(0, $unknown->fresh()->research_attempts);
    }

    public function test_a_company_whose_website_belongs_to_another_company_is_merged_into_it(): void
    {
        Http::fake(['tether.to*' => Http::response('<html><title>Tether</title></html>')]);
        $tether = Company::forceCreate(['name' => 'Tether', 'domain' => 'tether.to', 'import_identity' => 'x:1']);
        $copy = Company::forceCreate(['name' => 'Tether Holdings SA', 'website' => 'https://tether.to', 'import_identity' => 'x:2']);

        $this->assertTrue(app(CompanyVerifier::class)->verify($copy));
        $this->assertSame($tether->id, $copy->fresh()->claimed_by_company_id);
        $this->assertNotNull($tether->fresh()->verified_at);
        $this->assertSame(JobKey::company('Tether'), JobKey::company('Tether Holdings SA'));
    }

    public function test_verified_companies_show_the_badge(): void
    {
        $company = Company::forceCreate(['name' => 'Acme', 'verified_at' => now(), 'size' => '11-50', 'stage' => 'startup']);
        Company::forceCreate(['name' => 'Other']);

        $this->get($company->publicUrl())->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('company.verified', true)->where('company.size', '11-50')->where('company.stage', 'startup'));
        $this->get('/companies?show=all')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('companies.data.0.verified', true)->where('companies.data.1.verified', false));
    }

    private function verifierAnswers(array $answers): void
    {
        $this->app->instance(CompanyVerifier::class, new class(app(OpportunityClassifier::class), app(CompanyLogos::class), $answers) extends CompanyVerifier
        {
            public function __construct(OpportunityClassifier $classifier, CompanyLogos $fetcher, private array $answers)
            {
                parent::__construct($classifier, $fetcher);
            }

            public function verify(Company $company): ?bool
            {
                $passed = array_shift($this->answers);
                $company->forceFill(['researched_at' => now(), 'research_attempts' => $company->research_attempts + 1, 'verified_at' => $passed ? now() : null])->save();
                return $passed;
            }
        });
    }

    private function board(array $jobs, array $attributes = []): ImportSource
    {
        Http::fake(['*/robots.txt' => Http::response('', 404), 'boards-api.greenhouse.io/v1/boards/acme/jobs*' => Http::response(['jobs' => $jobs])]);
        $source = ImportSource::create($attributes + ['key' => 'greenhouse-acme', 'name' => 'Acme', 'provider' => 'greenhouse',
            'url' => 'https://boards-api.greenhouse.io/v1/boards/acme/jobs?content=true', 'allowed_domains' => ['boards-api.greenhouse.io', 'job-boards.greenhouse.io'],
            'permission_status' => 'approved', 'permission_notes' => 'Test source.', 'publication_mode' => 'automatic', 'enabled' => true,
            'cadence_hours' => 24, 'request_delay_ms' => 0, 'max_requests' => 100]);
        app(OpportunityImporter::class)->run($source);
        return $source;
    }

    private function job(int $id, string $title): array
    {
        return ['id' => $id, 'title' => $title, 'company_name' => 'Acme', 'location' => ['name' => 'Remote'],
            'content' => htmlspecialchars('<p>'.str_repeat('You will build and ship React product features with the team. ', 3).'</p>'),
            'absolute_url' => "https://job-boards.greenhouse.io/acme/jobs/{$id}", 'first_published' => now()->subDay()->toIso8601String()];
    }

    private function companyWithBoard(string $name): Company
    {
        $company = Company::forceCreate(['name' => $name, 'import_identity' => 'x:'.$name]);
        $source = ImportSource::create(['key' => 'greenhouse-acme', 'name' => 'Acme', 'provider' => 'greenhouse', 'url' => 'https://boards-api.greenhouse.io/v1/boards/acme/jobs',
            'allowed_domains' => ['boards-api.greenhouse.io'], 'permission_status' => 'approved', 'permission_notes' => 'Test.', 'publication_mode' => 'automatic',
            'enabled' => true, 'cadence_hours' => 24, 'request_delay_ms' => 0, 'max_requests' => 10]);
        OpportunitySource::create(['import_source_id' => $source->id, 'external_id' => '1', 'source_url' => 'https://job-boards.greenhouse.io/acme/jobs/1',
            'state' => 'waiting', 'company_id' => $company->id, 'first_seen_at' => now(), 'last_discovered_at' => now()]);
        return $company;
    }

    private function aiOn(): void
    {
        config(['importer.ai.enabled' => true, 'importer.ai.provider' => 'openai', 'ai.providers.openai.key' => 'test-key']);
    }

    private function facts(string $website): array
    {
        return ['found' => true, 'website' => $website, 'about' => '', 'size' => '', 'founded_year' => 0, 'headquarters' => '', 'country_code' => '',
            'stage' => '', 'linkedin_url' => '', 'careers_url' => '', 'sources' => []];
    }

    private function reply(array $data): array
    {
        return ['id' => 'resp_1', 'object' => 'response', 'status' => 'completed', 'model' => 'gpt-5.5',
            'output' => [['type' => 'message', 'id' => 'msg_1', 'role' => 'assistant', 'status' => 'completed',
                'content' => [['type' => 'output_text', 'text' => json_encode($data), 'annotations' => []]]]],
            'usage' => ['input_tokens' => 100, 'output_tokens' => 20, 'total_tokens' => 120]];
    }
}

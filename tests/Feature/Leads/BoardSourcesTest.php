<?php

namespace Tests\Feature\Leads;

use App\Importing\OpportunityImporter;
use App\Importing\SourceHttp;
use App\Models\Field;
use App\Models\ImportSource;
use App\Models\Opportunity;
use App\Models\OpportunitySource;
use Database\Seeders\ImportSourceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BoardSourcesTest extends TestCase
{
    use RefreshDatabase;

    private string $body;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-25 10:00:00', 'UTC'));
        SourceHttp::$resolveUsing = fn () => ['93.184.216.34'];
        Field::create(['name' => 'Web Development']);
        config(['importer.scope' => null]);
        $this->body = str_repeat('You will build React and TypeScript features for our customers with a small team. ', 3);
    }

    protected function tearDown(): void
    {
        SourceHttp::$resolveUsing = null;
        parent::tearDown();
    }

    public function test_workable_and_recruitee_boards_publish_like_other_employer_boards(): void
    {
        Http::fake([
            '*/robots.txt' => Http::response('', 404),
            'apply.workable.com/api/v1/widget/accounts/acme*' => Http::response(['name' => 'Acme', 'jobs' => [[
                'title' => 'Frontend Engineer - EMEA Remote', 'shortcode' => 'AB12CD', 'url' => 'https://apply.workable.com/j/AB12CD',
                'application_url' => 'https://apply.workable.com/j/AB12CD/apply', 'published_on' => '2026-10-20', 'telecommuting' => true,
                'country' => 'France', 'city' => 'Paris', 'employment_type' => 'Full-time', 'locations' => [['country' => 'France', 'countryCode' => 'FR', 'city' => 'Paris']],
                'description' => '<p>'.$this->body.'</p>',
            ]]]),
            'beta.recruitee.com/api/offers' => Http::response(['offers' => [[
                'id' => 77, 'title' => 'Full Stack Developer', 'careers_url' => 'https://beta.recruitee.com/o/full-stack-developer',
                'careers_apply_url' => 'https://beta.recruitee.com/o/full-stack-developer/c/new', 'published_at' => '2026-10-21 09:00:00 UTC',
                'company_name' => 'Beta', 'location' => 'Amsterdam, Netherlands', 'country_code' => 'NL', 'hybrid' => true,
                'employment_type_code' => 'fulltime_permanent', 'description' => '<p>'.$this->body.'</p>', 'requirements' => '<p>Visa sponsorship is offered.</p>',
            ]]]),
        ]);
        app(OpportunityImporter::class)->run($this->source('workable-acme', 'workable', 'https://apply.workable.com/api/v1/widget/accounts/acme?details=true', ['apply.workable.com'], 'automatic'));
        app(OpportunityImporter::class)->run($this->source('recruitee-beta', 'recruitee', 'https://beta.recruitee.com/api/offers', ['beta.recruitee.com'], 'automatic'));

        $workable = Opportunity::where('title', 'Frontend Engineer - EMEA Remote')->firstOrFail();
        $this->assertSame('Acme', $workable->company->name);
        $this->assertSame('remote', $workable->work_type);
        $this->assertSame('ats', $workable->date_source);
        $this->assertSame('2026-10-20', $workable->first_posted_at->toDateString());
        $recruitee = Opportunity::where('title', 'Full Stack Developer')->firstOrFail();
        $this->assertSame('hybrid', $recruitee->work_type);
        $this->assertSame('NL', $recruitee->country_code);
        $this->assertSame('yes', $recruitee->visa_sponsorship);
    }

    public function test_remote_boards_only_keep_listings_for_job_leads_and_never_publish(): void
    {
        Http::fake([
            '*/robots.txt' => Http::response('', 404),
            'himalayas.app/jobs/api*' => Http::response(['nextCursor' => null, 'jobs' => [
                $this->himalayas('Senior React Developer', 'Gamma', [], 'This role is only open to candidates based in LATAM. '.$this->body),
                $this->himalayas('Account Executive', 'Gamma', [], $this->body),
            ]]),
            'remoteok.com/api' => Http::response([['legal' => 'API Terms of Service: please link back.'], [
                'id' => '901', 'position' => 'Full Stack Engineer', 'company' => 'Delta', 'date' => now()->subDay()->toIso8601String(),
                'location' => 'Worldwide', 'description' => '<p>'.$this->body.'</p>', 'url' => 'https://remoteOK.com/remote-jobs/901', 'apply_url' => 'https://remoteOK.com/remote-jobs/901',
            ], [
                'id' => '902', 'position' => 'Frontend Engineer', 'company' => 'Delta', 'date' => now()->subDays(9)->toIso8601String(),
                'location' => '', 'description' => '<p>'.$this->body.'</p>', 'url' => 'https://remoteOK.com/remote-jobs/902',
            ]]),
        ]);
        $himalayas = $this->source('himalayas', 'himalayas', 'https://himalayas.app/jobs/api?limit=20', ['himalayas.app'], 'leads');
        $remoteok = $this->source('remoteok', 'remoteok', 'https://remoteok.com/api', ['remoteok.com'], 'leads');
        app(OpportunityImporter::class)->run($himalayas);
        app(OpportunityImporter::class)->run($remoteok);

        $this->assertSame(0, Opportunity::count());
        $react = OpportunitySource::where('external_id', 'https://himalayas.app/companies/gamma/jobs/senior-react-developer')->firstOrFail();
        $this->assertSame('lead', $react->state);
        // Himalayas shows "all countries" when the employer set no limit; that default is not kept.
        $this->assertNotSame('anywhere', $react->normalized_data['applicant_scope'] ?? null);
        $this->assertNull($react->normalized_data['location']);
        $this->assertSame('rejected', OpportunitySource::where('external_id', 'https://himalayas.app/companies/gamma/jobs/account-executive')->value('state'));
        $this->assertSame('lead', OpportunitySource::where('external_id', '901')->value('state'));
        $this->assertSame('Posted more than 3 days ago.', OpportunitySource::where('external_id', '902')->value('error'));
        $this->assertFalse(OpportunitySource::where('external_id', 'legal')->exists());

        $this->expectException(\RuntimeException::class);
        app(OpportunityImporter::class)->publish($react);
    }

    public function test_board_listings_with_an_employer_ats_link_keep_it_and_suggest_the_board(): void
    {
        $story = ['objectID' => '5000', 'title' => 'Ask HN: Who is hiring? (October 2026)'];
        $comment = ['objectID' => '5001', 'parent_id' => 5000, 'created_at' => now()->subDay()->toIso8601String(),
            'comment_text' => 'Omega | Senior Frontend Engineer | REMOTE (EMEA) | Full-time<p>'.$this->body.' Apply: <a href="https:&#x2F;&#x2F;jobs.ashbyhq.com&#x2F;omega&#x2F;0b5f1a2c-1111-2222-3333-444455556666">https://jobs.ashbyhq.com/omega/0b5f1a2c-1111-2222-3333-444455556666</a></p>'];
        Http::fake([
            '*/robots.txt' => Http::response('', 404),
            'hn.algolia.com/api/v1/search_by_date?tags=story*' => Http::response(['hits' => [$story]]),
            'hn.algolia.com/api/v1/search_by_date?tags=comment*' => Http::response(['hits' => [$comment, ['objectID' => '5002', 'parent_id' => 5001, 'comment_text' => 'A reply']]]),
            'weworkremotely.com/*' => Http::response('<?xml version="1.0"?><rss version="2.0"><channel><item><title>Sigma: Backend Engineer</title><region>Anywhere in the World</region>'
                .'<type>Full-Time</type><description>&lt;p&gt;'.$this->body.'&lt;/p&gt;</description><pubDate>'.now()->subDay()->toRfc2822String().'</pubDate>'
                .'<guid>https://weworkremotely.com/remote-jobs/sigma-backend-engineer</guid><link>https://weworkremotely.com/remote-jobs/sigma-backend-engineer</link></item></channel></rss>'),
        ]);
        app(OpportunityImporter::class)->run($this->source('hn-who-is-hiring', 'hn', 'https://hn.algolia.com/api/v1/search_by_date?tags=story,author_whoishiring&hitsPerPage=5', ['hn.algolia.com', 'news.ycombinator.com'], 'leads'));
        app(OpportunityImporter::class)->run($this->source('wwr-back-end', 'wwr', 'https://weworkremotely.com/categories/remote-back-end-programming-jobs.rss', ['weworkremotely.com'], 'leads'));

        $hn = OpportunitySource::where('external_id', '5001')->firstOrFail();
        $this->assertSame('lead', $hn->state);
        $this->assertSame('Omega', $hn->normalized_data['employer']);
        $this->assertSame('Senior Frontend Engineer', $hn->normalized_data['title']);
        $this->assertSame('https://jobs.ashbyhq.com/omega/0b5f1a2c-1111-2222-3333-444455556666', $hn->normalized_data['ats_url']);
        $this->assertSame('https://jobs.ashbyhq.com/omega/0b5f1a2c-1111-2222-3333-444455556666', $hn->url_key);
        $this->assertFalse(OpportunitySource::where('external_id', '5002')->exists());
        $suggested = ImportSource::where('key', 'ashby-omega')->firstOrFail();
        $this->assertSame('pending', $suggested->permission_status);
        $this->assertFalse($suggested->enabled);
        $this->assertSame('https://api.ashbyhq.com/posting-api/job-board/omega', $suggested->url);

        $wwr = OpportunitySource::where('external_id', 'https://weworkremotely.com/remote-jobs/sigma-backend-engineer')->firstOrFail();
        $this->assertSame(['Sigma', 'Backend Engineer', null], [$wwr->normalized_data['employer'], $wwr->normalized_data['title'], $wwr->normalized_data['location']]);
    }

    public function test_seeded_boards_only_feed_job_leads(): void
    {
        $this->seed(ImportSourceSeeder::class);
        $boards = ImportSource::whereIn('provider', ['himalayas', 'remoteok', 'remotive', 'wwr', 'hn'])->get();
        $this->assertCount(7, $boards);
        $this->assertTrue($boards->every(fn ($board) => $board->publication_mode === 'leads'));
        $this->assertTrue(ImportSource::where('provider', 'workable')->exists());
    }

    private function source(string $key, string $provider, string $url, array $domains, string $mode): ImportSource
    {
        return ImportSource::create(['key' => $key, 'name' => $key, 'provider' => $provider, 'url' => $url, 'allowed_domains' => $domains,
            'permission_status' => 'approved', 'permission_notes' => 'Test source.', 'publication_mode' => $mode, 'enabled' => true,
            'cadence_hours' => 24, 'request_delay_ms' => 0, 'max_requests' => 100, 'settings' => ['employer_name' => 'Acme']]);
    }

    private function himalayas(string $title, string $company, array $places, string $description): array
    {
        $slug = \Illuminate\Support\Str::slug($title);
        return ['title' => $title, 'companyName' => $company, 'locationRestrictions' => $places, 'description' => '<p>'.$description.'</p>',
            'pubDate' => now()->subDay()->timestamp, 'employmentType' => 'Full Time',
            'applicationLink' => "https://himalayas.app/companies/gamma/jobs/{$slug}", 'guid' => "https://himalayas.app/companies/gamma/jobs/{$slug}"];
    }
}

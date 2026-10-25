<?php

namespace Tests\Feature\Leads;

use App\Importing\CandidateProfiles;
use App\Importing\OpportunityImporter;
use App\Importing\OpportunityNormalizer;
use App\Importing\SourceHttp;
use App\Models\Field;
use App\Models\ImportSource;
use App\Models\Opportunity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImportDedupeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-15 10:00:00', 'UTC'));
        SourceHttp::$resolveUsing = fn () => ['93.184.216.34'];
        Field::create(['name' => 'Web Development']);
        config(['importer.scope' => null]);
    }

    protected function tearDown(): void
    {
        SourceHttp::$resolveUsing = null;
        parent::tearDown();
    }

    public function test_a_board_copy_and_the_employer_ats_listing_are_one_job_with_the_ats_date(): void
    {
        Http::fake([
            '*/robots.txt' => Http::response('', 404),
            'remote-board.example/sitemap.xml' => Http::response('<?xml version="1.0"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"><url><loc>https://remote-board.example/jobs/1</loc></url></urlset>'),
            'remote-board.example/jobs/1' => Http::response($this->page('Frontend Engineer - Remote', 'Acme Inc', now()->subDay()->toDateString())),
            'boards-api.greenhouse.io/v1/boards/acme/jobs*' => Http::response(['jobs' => [$this->greenhouse(7, 'Senior Frontend Engineer', 'Remote', 10)]]),
        ]);
        $board = $this->source(['key' => 'board', 'url' => 'https://remote-board.example/sitemap.xml', 'allowed_domains' => ['remote-board.example']]);
        $ats = $this->source(['key' => 'greenhouse-acme', 'provider' => 'greenhouse', 'url' => 'https://boards-api.greenhouse.io/v1/boards/acme/jobs?content=true',
            'allowed_domains' => ['boards-api.greenhouse.io', 'job-boards.greenhouse.io']]);

        app(OpportunityImporter::class)->run($board);
        app(OpportunityImporter::class)->run($ats);

        $this->assertSame(1, Opportunity::count());
        $role = Opportunity::first();
        $this->assertSame('ats', $role->date_source);
        $this->assertTrue($role->first_posted_at->isSameDay(now()->subDays(10)));
        $this->assertSame(2, $role->sources()->count());
        $this->assertNotNull($role->job_key);
    }

    public function test_a_closed_job_posted_again_with_a_new_id_is_a_repost_not_a_new_job(): void
    {
        $jobs = [$this->greenhouse(1, 'Senior Frontend Engineer', 'Remote', 5)];
        Http::fake([
            '*/robots.txt' => Http::response('', 404),
            'boards-api.greenhouse.io/v1/boards/acme/jobs*' => function () use (&$jobs) { return Http::response(['jobs' => $jobs]); },
        ]);
        $source = $this->source(['key' => 'greenhouse-acme', 'provider' => 'greenhouse', 'url' => 'https://boards-api.greenhouse.io/v1/boards/acme/jobs?content=true',
            'allowed_domains' => ['boards-api.greenhouse.io', 'job-boards.greenhouse.io']]);
        app(OpportunityImporter::class)->run($source);
        $role = Opportunity::firstOrFail();
        $first = $role->first_posted_at->copy();
        $role->sources()->update(['state' => 'closed']);
        $role->update(['status' => 'closed']);

        $this->travel(21)->days();
        $jobs = [$this->greenhouse(2, 'Frontend Engineer (100% Remote)', 'Remote', 1)];
        app(OpportunityImporter::class)->run($source->fresh());

        $this->assertSame(1, Opportunity::count());
        $role->refresh();
        $this->assertSame('open', $role->status);
        $this->assertTrue($role->first_posted_at->equalTo($first));
        $this->assertNotNull($role->reposted_at);
    }

    public function test_a_second_open_opening_with_the_same_title_on_the_same_board_stays_separate(): void
    {
        Http::fake([
            '*/robots.txt' => Http::response('', 404),
            'boards-api.greenhouse.io/v1/boards/acme/jobs*' => Http::response(['jobs' => [
                $this->greenhouse(1, 'Software Engineer', 'Remote', 3), $this->greenhouse(2, 'Software Engineer', 'Remote', 2),
            ]]),
        ]);
        app(OpportunityImporter::class)->run($this->source(['key' => 'greenhouse-acme', 'provider' => 'greenhouse',
            'url' => 'https://boards-api.greenhouse.io/v1/boards/acme/jobs?content=true', 'allowed_domains' => ['boards-api.greenhouse.io', 'job-boards.greenhouse.io']]));

        $this->assertSame(2, Opportunity::count());
    }

    public function test_years_of_experience_are_read_from_the_description(): void
    {
        $normalizer = new OpportunityNormalizer();
        $this->assertSame(8, $normalizer->years('You have 8+ years of professional software engineering experience.'));
        $this->assertSame(5, $normalizer->years('3+ years of experience with React and 5-7 years of experience overall.'));
        $this->assertNull($normalizer->years('We have been around for 10 years and serve 200 customers.'));
    }

    public function test_a_profile_that_needs_a_visa_only_wants_reachable_roles(): void
    {
        $profiles = new CandidateProfiles([CandidateProfiles::save('Owner', ['fields' => ['Web Development'], 'country' => 'TN', 'needs_visa' => true, 'countries' => ['DE', 'US']])]);
        $role = fn (array $data) => $data + ['kind' => 'job', 'seniority' => 'senior', 'country_code' => null, 'locations' => [], 'location' => null,
            'excluded_countries' => [], 'eligible_countries' => [], 'applicant_scope' => 'unknown', 'remote_eligibility' => 'unknown',
            'relocation' => 'unknown', 'visa_sponsorship' => 'unknown', 'eligibility_evidence' => [], 'description' => ''];

        $this->assertFalse($profiles->wants($role(['work_type' => 'onsite', 'country_code' => 'DE']), 'Web Development', []));
        $this->assertTrue($profiles->wants($role(['work_type' => 'onsite', 'country_code' => 'DE', 'visa_sponsorship' => 'yes']), 'Web Development', []));
        $this->assertFalse($profiles->wants($role(['work_type' => 'onsite', 'country_code' => 'JP', 'visa_sponsorship' => 'yes']), 'Web Development', []));
        $this->assertFalse($profiles->wants($role(['work_type' => 'remote', 'location' => 'Remote - US only']), 'Web Development', []));
        $this->assertTrue($profiles->wants($role(['work_type' => 'remote', 'location' => 'Remote (EMEA)']), 'Web Development', []));
    }

    private function source(array $attributes): ImportSource
    {
        return ImportSource::create($attributes + [
            'name' => $attributes['key'], 'provider' => 'jsonld', 'permission_status' => 'approved', 'permission_notes' => 'Test source.',
            'publication_mode' => 'automatic', 'enabled' => true, 'cadence_hours' => 24, 'request_delay_ms' => 0, 'max_requests' => 100,
        ]);
    }

    private function greenhouse(int $id, string $title, string $location, int $daysAgo): array
    {
        return ['id' => $id, 'title' => $title, 'company_name' => 'Acme', 'location' => ['name' => $location],
            'content' => htmlspecialchars('<p>'.str_repeat('You will build and ship React product features with the team. ', 3).'</p>'),
            'absolute_url' => "https://job-boards.greenhouse.io/acme/jobs/{$id}", 'first_published' => now()->subDays($daysAgo)->toIso8601String()];
    }

    private function page(string $title, string $employer, string $posted): string
    {
        $posting = ['@context' => 'https://schema.org', '@type' => 'JobPosting', 'title' => $title, 'url' => 'https://remote-board.example/jobs/1/apply',
            'description' => '<p>'.str_repeat('You will build and ship React product features with the team. ', 3).'</p>',
            'datePosted' => $posted, 'jobLocationType' => 'TELECOMMUTE', 'hiringOrganization' => ['@type' => 'Organization', 'name' => $employer]];
        return '<html><head><script type="application/ld+json">'.json_encode($posting).'</script></head><body></body></html>';
    }
}

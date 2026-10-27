<?php

namespace Tests\Feature\Leads;

use App\Ai\JobPageCheck;
use App\Ai\OpportunityClassifier;
use App\Importing\OpportunityImporter;
use App\Importing\SourceHttp;
use App\Models\Field;
use App\Models\ImportSource;
use App\Models\OpportunitySource;
use App\Models\User;
use Database\Seeders\ImportSourceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class LinkedPagesTest extends TestCase
{
    use RefreshDatabase;

    private object $ai;
    private string $body;
    private array $posts = [];
    private array $pages = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-25 10:00:00', 'UTC'));
        SourceHttp::$resolveUsing = fn () => ['93.184.216.34'];
        Field::create(['name' => 'Web Development']);
        config(['importer.scope' => null, 'importer.verify_companies' => false]);
        $this->body = str_repeat('You will build Laravel and React features for our customers with a small team in Berlin. ', 4);
        // Answers by page address; "next" is the link to follow, turned into its number on the page.
        $this->ai = new class(app(OpportunityClassifier::class)) extends JobPageCheck {
            public array $answers = [];
            public array $calls = [];

            public function enabled(): bool { return true; }

            public function check(string $post, array $page): array
            {
                $this->calls[] = ['post' => $post, 'page' => $page];
                $answer = $this->answers[$page['url']] ?? ['verdict' => 'not_job', 'reason' => 'Nothing here.'];
                if ($answer === 'fail') { throw new RuntimeException('AI provider [openai] is overloaded.'); }
                $next = isset($answer['next']) ? array_search($answer['next'], array_column($page['links'], 0), true) : false;
                unset($answer['next']);
                return [...['next_link' => $next === false ? 0 : $next + 1, 'title' => '', 'employer' => '', 'employer_site' => false, 'location' => '',
                    'work_type' => 'unknown', 'employment_types' => [], 'model' => 'test'], ...$answer];
            }
        };
        $this->app->instance(JobPageCheck::class, $this->ai);
        Http::fake(function ($request) {
            $url = $request->url();
            return match (true) {
                str_ends_with($url, '/robots.txt') => Http::response('', 404),
                str_contains($url, 'tags=story') => Http::response(['hits' => [['objectID' => '5000', 'title' => 'Ask HN: Who is hiring? (October 2026)']]]),
                str_contains($url, 'tags=comment') => Http::response(['hits' => $this->posts]),
                default => isset($this->pages[$url]) ? Http::response($this->pages[$url]) : Http::response('', 404),
            };
        });
    }

    protected function tearDown(): void
    {
        SourceHttp::$resolveUsing = null;
        parent::tearDown();
    }

    public function test_the_ai_follows_a_post_link_to_the_job_page_and_sends_it_to_review(): void
    {
        $this->fakeThread([
            $this->hnPost('6001', 'Acme | Backend Engineer | Berlin | VISA <a href="https:&#x2F;&#x2F;acme.dev&#x2F;">acme.dev</a> <a href="https:&#x2F;&#x2F;www.linkedin.com&#x2F;in&#x2F;founder">me</a>'),
        ], [
            'https://acme.dev/' => '<html><title>Acme</title><body><nav><a href="/about">About</a></nav><main><h1>Acme builds billing software</h1><p>'.$this->body.'</p>'
                .'<a href="https://www.linkedin.com/company/acme">LinkedIn</a> <a href="/careers/backend-engineer">Backend Engineer</a></main></body></html>',
            'https://acme.dev/careers/backend-engineer' => '<html><title>Backend Engineer - Acme</title><body><header>Acme menu</header><main><h1>Backend Engineer</h1><p>'.$this->body.'</p>'
                .'<p>We sponsor visas and help you move to Berlin.</p></main><footer>Imprint</footer></body></html>',
        ]);
        $this->ai->answers = [
            'https://acme.dev/' => ['verdict' => 'more', 'reason' => 'Home page that links the role.', 'next' => 'https://acme.dev/careers/backend-engineer'],
            'https://acme.dev/careers/backend-engineer' => ['verdict' => 'job', 'reason' => 'Job posting for a Backend Engineer at Acme in Berlin.', 'title' => 'Backend Engineer',
                'employer' => 'Acme', 'employer_site' => true, 'location' => 'Berlin, Germany', 'work_type' => 'onsite', 'employment_types' => ['FULL_TIME']],
        ];

        $pages = $this->runPages();

        $item = $pages->items()->sole();
        $this->assertSame('https://acme.dev/', $item->source_url);
        $this->assertSame('review', $item->state);
        $this->assertSame(['Backend Engineer', 'Acme', 'DE', 'onsite'], [$item->normalized_data['title'], $item->normalized_data['employer'], $item->normalized_data['country_code'], $item->normalized_data['work_type']]);
        $this->assertSame('https://acme.dev/careers/backend-engineer', $item->normalized_data['application_url']);
        $this->assertSame('https://news.ycombinator.com/item?id=6001', $item->normalized_data['page_check']['post_url']);
        $this->assertStringContainsString('We sponsor visas', $item->normalized_data['description']);
        $this->assertStringNotContainsString('VISA', $item->normalized_data['description']);
        $this->assertStringNotContainsString('Imprint', $item->normalized_data['description']);
        $this->assertCount(2, $this->ai->calls);
        $this->assertStringContainsString('Acme | Backend Engineer', $this->ai->calls[0]['post']);
        $this->assertSame(['https://acme.dev/careers/backend-engineer', 'https://acme.dev/about'], array_column($this->ai->calls[0]['page']['links'], 0));

        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $this->actingAs($admin)->get(route('imports.review'))->assertOk()
            ->assertInertia(fn ($page) => $page->where('items.data.0.post_url', 'https://news.ycombinator.com/item?id=6001'));
        $this->actingAs($admin)->post(route('imports.items.publish', $item))->assertSessionHasNoErrors();
        $role = $item->fresh()->opportunity;
        $this->assertSame('Backend Engineer', $role->title);
        $this->assertSame('https://acme.dev/careers/backend-engineer', $role->application_url);
        $this->assertSame('acme.dev', $role->company->domain);

        // The next day the page is only opened again; once it is gone, the role closes.
        $this->travel(1)->day();
        $this->fakeThread([$this->hnPost('6001', 'Acme | Backend Engineer | Berlin <a href="https:&#x2F;&#x2F;acme.dev&#x2F;">acme.dev</a>')], []);
        $this->runPages();
        $this->assertCount(2, $this->ai->calls);
        $this->assertSame('closed', $item->fresh()->state);
        $this->assertSame('closed', $role->fresh()->status);
    }

    public function test_pages_that_are_not_real_jobs_are_rejected_with_the_reason(): void
    {
        $this->fakeThread([
            $this->hnPost('6002', 'Hiring now <a href="https:&#x2F;&#x2F;easy-money.example&#x2F;jobs&#x2F;1">apply</a>'),
            $this->hnPost('6003', 'I am looking for work <a href="https:&#x2F;&#x2F;me.example&#x2F;">portfolio</a>'),
            $this->hnPost('6004', 'Beta | Engineer <a href="https:&#x2F;&#x2F;beta.example&#x2F;careers&#x2F;gone">role</a>'),
            $this->hnPost('6005', 'Gamma | Engineer <a href="https:&#x2F;&#x2F;www.linkedin.com&#x2F;jobs&#x2F;view&#x2F;1">LinkedIn</a>'),
        ], [
            'https://easy-money.example/jobs/1' => '<main><p>'.$this->body.' Pay a 50 dollar fee to apply.</p></main>',
            'https://me.example/' => '<main><p>'.$this->body.'</p></main>',
        ]);
        $this->ai->answers = [
            'https://easy-money.example/jobs/1' => ['verdict' => 'not_legit', 'reason' => 'It asks candidates to pay a fee.'],
            'https://me.example/' => ['verdict' => 'not_job', 'reason' => 'A person looking for work.'],
        ];

        $pages = $this->runPages();

        $errors = $pages->items()->pluck('error', 'source_url');
        $this->assertSame([
            'https://beta.example/careers/gone' => 'The linked page could not be read: Source returned HTTP 404.',
            'https://easy-money.example/jobs/1' => 'Not a real job: It asks candidates to pay a fee.',
            'https://me.example/' => 'No job on the linked page: A person looking for work.',
        ], $errors->sortKeys()->all());
        $this->assertTrue($pages->items()->get()->every(fn ($item) => $item->state === 'rejected'));
    }

    public function test_a_failed_ai_check_is_tried_again_later(): void
    {
        $this->fakeThread([$this->hnPost('6006', 'Delta | Engineer <a href="https:&#x2F;&#x2F;delta.example&#x2F;jobs&#x2F;7">role</a>')], [
            'https://delta.example/jobs/7' => '<main><h1>Engineer</h1><p>'.$this->body.'</p></main>',
        ]);
        $this->ai->answers = ['https://delta.example/jobs/7' => 'fail'];

        $item = $this->runPages()->items()->sole();

        $this->assertSame('pending', $item->state);
        $this->assertSame('The AI check failed, so the page is read later.', $item->error);
    }

    public function test_the_seeded_source_reads_hacker_news_links_for_review(): void
    {
        $this->seed(ImportSourceSeeder::class);
        $source = ImportSource::where('key', 'linked-job-pages')->sole();
        $this->assertSame(['pages', 'review', [], true], [$source->provider, $source->publication_mode, $source->allowed_domains, $source->isApproved()]);
    }

    private function hnPost(string $id, string $text): array
    {
        return ['objectID' => $id, 'parent_id' => 5000, 'created_at' => now()->subDays(2)->toIso8601String(), 'comment_text' => $text.'<p>'.$this->body.'</p>'];
    }

    private function fakeThread(array $posts, array $pages): void
    {
        [$this->posts, $this->pages] = [$posts, $pages];
        $board = ImportSource::firstOrCreate(['key' => 'hn-who-is-hiring'], ['name' => 'Hacker News', 'provider' => 'hn',
            'url' => 'https://hn.algolia.com/api/v1/search_by_date?tags=story,author_whoishiring&hitsPerPage=5', 'allowed_domains' => ['hn.algolia.com', 'news.ycombinator.com'],
            'permission_status' => 'approved', 'permission_notes' => 'Test source.', 'publication_mode' => 'leads', 'enabled' => true,
            'cadence_hours' => 24, 'request_delay_ms' => 0, 'max_requests' => 100, 'settings' => ['scope' => 'all']]);
        app(OpportunityImporter::class)->run($board->fresh(), recheck: true);
    }

    private function runPages(): ImportSource
    {
        $pages = ImportSource::firstOrCreate(['key' => 'linked-job-pages'], ['name' => 'Linked pages', 'provider' => 'pages', 'url' => 'https://news.ycombinator.com/',
            'allowed_domains' => [], 'permission_status' => 'approved', 'permission_notes' => 'Test source.', 'publication_mode' => 'review', 'enabled' => true,
            'cadence_hours' => 24, 'request_delay_ms' => 0, 'max_requests' => 100, 'settings' => ['scope' => 'all']]);
        app(OpportunityImporter::class)->run($pages->fresh(), recheck: true);
        return $pages;
    }
}

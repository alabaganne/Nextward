<?php

namespace App\Ai;

use App\Ai\Agents\SourceScout;
use App\Ai\Tools\SuggestSource;
use App\Importing\SourceHttp;
use App\Models\ImportSource;
use Illuminate\Support\Str;
use Throwable;

// Asks the AI to search the web for new job boards, then checks each suggestion before saving it for admin review.
class SourceDiscovery
{
    private const API = [
        'greenhouse' => 'https://boards-api.greenhouse.io/v1/boards/%s/jobs',
        'lever' => 'https://api.lever.co/v0/postings/%s?mode=json',
        'ashby' => 'https://api.ashbyhq.com/posting-api/job-board/%s',
    ];

    public array $usage = ['input_tokens' => 0, 'output_tokens' => 0, 'web_searches' => 0];

    public function __construct(private OpportunityClassifier $classifier) {}

    public function enabled(): bool
    {
        return $this->classifier->enabled();
    }

    // Returns one entry per suggestion: [status, name, message].
    public function run(int $max): array
    {
        $existing = ImportSource::orderBy('key')->get(['key', 'url'])->map(fn ($source) => $source->key.' '.$source->url)->implode("\n");
        $suggest = new SuggestSource($this, $max);
        $response = (new SourceScout($suggest))->prompt($this->prompt($max, $existing), provider: $this->classifier->provider(), model: $this->classifier->model('discovery'));
        $this->usage = [
            'input_tokens' => $response->usage->inputTokens,
            'output_tokens' => $response->usage->outputTokens,
            'web_searches' => $response->steps->sum(fn ($step) => collect($step->providerToolCalls)->whereIn('type', ['web_search_call', 'server_tool_use'])->count()),
        ];
        return $suggest->results;
    }

    // Checks one suggestion against the board itself and saves it disabled, waiting for approval in Imports.
    public function store(array $input): array
    {
        $provider = $input['provider'] ?? '';
        $company = Str::limit(trim((string) ($input['company'] ?? '')), 120, '');
        $board = trim((string) ($input['board'] ?? ''));
        if ($company === '') { return ['skipped', $board, 'Rejected: the company name is missing.']; }
        if (isset(self::API[$provider])) {
            $board = strtolower($board);
            if (! preg_match('/^[a-z0-9][a-z0-9_-]{1,80}$/', $board)) { return ['skipped', $board, 'Rejected: give only the board token from the board URL.']; }
            $key = $provider.'-'.$board;
            $url = sprintf(self::API[$provider], $board);
        } elseif ($provider === 'sitemap') {
            $host = strtolower((string) parse_url($board, PHP_URL_HOST));
            if (! str_starts_with($board, 'https://') || ! $host) { return ['skipped', $board, 'Rejected: give the full https sitemap URL.']; }
            if (str_ends_with($host, '.tn')) { return ['skipped', $board, 'Rejected: Nextward no longer reads Tunisian job sites.']; }
            $key = 'site-'.Str::slug(preg_replace('/^www\./', '', $host));
            $url = $board;
        } else {
            return ['skipped', $board, 'Rejected: unknown provider.'];
        }
        $name = $company.' ('.($provider === 'sitemap' ? 'site' : ucfirst($provider)).')';
        if (ImportSource::where('key', $key)->orWhere('url', $url)->orWhere('url', $url.'?content=true')->exists()) {
            return ['skipped', $name, 'Already a source, no need to suggest it.'];
        }

        $apiHost = (string) parse_url($url, PHP_URL_HOST);
        $probe = new ImportSource(['allowed_domains' => [$apiHost], 'request_delay_ms' => 0, 'max_requests' => 4]);
        try {
            $body = (new SourceHttp($probe, microtime(true) + 30))->get($url);
        } catch (Throwable $e) {
            return ['skipped', $name, 'Rejected: the board could not be read ('.Str::limit($e->getMessage(), 120).').'];
        }

        if ($provider === 'sitemap') {
            if (! preg_match('/<(urlset|sitemapindex)\b/', $body)) { return ['skipped', $name, 'Rejected: that URL is not a sitemap.']; }
            $jobs = substr_count($body, '<loc>');
            $domains = array_values(array_unique([$apiHost, preg_replace('/^www\./', '', $apiHost)]));
            $settings = ['scope' => 'all', 'job_path_prefix' => $this->prefix($input['job_path_prefix'] ?? '')];
        } else {
            $data = json_decode($body, true);
            $records = $provider === 'lever' ? $data : ($data['jobs'] ?? null);
            if (! is_array($records)) { return ['skipped', $name, 'Rejected: the board answer is not a job list.']; }
            $jobs = count($records);
            if ($jobs === 0) { return ['skipped', $name, 'Rejected: the board has no open jobs right now.']; }
            $links = array_filter(array_map(fn ($job) => parse_url($job['absolute_url'] ?? $job['hostedUrl'] ?? $job['jobUrl'] ?? '', PHP_URL_HOST), $records));
            $domains = array_values(array_unique([$apiHost, ...$links]));
            $settings = ['scope' => 'reachable', 'authoritative_employer' => true] + ($provider === 'greenhouse' ? [] : ['employer_name' => $company]);
            $url = $provider === 'greenhouse' ? $url.'?content=true' : $url;
        }

        ImportSource::create([
            'key' => $key, 'name' => $name, 'provider' => $provider === 'sitemap' ? 'jsonld' : $provider, 'url' => $url,
            'allowed_domains' => array_slice($domains, 0, 20), 'permission_status' => 'pending', 'enabled' => false,
            'permission_notes' => 'Suggested by '.$this->classifier->label().' on '.now()->toDateString().': '.Str::limit(trim((string) ($input['why'] ?? '')), 500).' Check the site terms before approving.',
            'publication_mode' => 'automatic', 'settings' => array_filter($settings, fn ($value) => $value !== null),
            'cadence_hours' => 24, 'request_delay_ms' => $provider === 'sitemap' ? 1500 : 1000, 'max_requests' => $provider === 'sitemap' ? 150 : 20,
        ]);
        return ['added', $name, "Saved for review: {$jobs} ".($provider === 'sitemap' ? 'links in the sitemap.' : 'open jobs on the board.')];
    }

    private function prefix(string $prefix): ?string
    {
        $prefix = trim($prefix);
        return $prefix !== '' && str_starts_with($prefix, '/') ? Str::limit($prefix, 200, '') : null;
    }

    private function prompt(int $max, string $existing): string
    {
        return <<<PROMPT
Find up to {$max} new places where Nextward can read IT jobs and internships abroad: software engineering, web and mobile development, and data science and AI.

Good finds:
- Companies that hire for these roles remotely worldwide, or in Europe, North America, the Gulf or Asia with relocation or visa help, and publish their jobs on Greenhouse, Lever or Ashby. Give the board token from the board URL: boards.greenhouse.io/<token>, job-boards.greenhouse.io/<token>, jobs.lever.co/<token>, or jobs.ashbyhq.com/<token>.
- Employer career sites outside Tunisia whose job pages carry JobPosting structured data. Give the https URL of the sitemap that lists the job pages, and the path that every job page starts with (for example /jobs/).

Do not suggest Tunisian job sites or sites on a .tn domain.

Use web search to confirm each find is real and currently hiring. Call suggest_source once per find; it checks the board and tells you whether it was saved. Do not suggest anything already in this list:
{$existing}
PROMPT;
    }
}

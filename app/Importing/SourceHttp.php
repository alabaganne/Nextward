<?php

namespace App\Importing;

use App\Models\ImportSource;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class SourceHttp
{
    public int $requests = 0;
    // The last URL read, after redirects.
    public ?string $url = null;
    public static ?\Closure $resolveUsing = null;
    private array $robots = [];
    private float $lastRequest = 0;

    public function __construct(private ImportSource $source, private ?float $deadline = null) {}

    public static function resolveUrl(string $base, string $url): string
    {
        return (string) UriResolver::resolve(new Uri($base), new Uri($url));
    }

    public function get(string $url): string
    {
        $parts = $this->validate($url);
        $origin = $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
        if (! array_key_exists($origin, $this->robots)) {
            // Follow-up batches run every few minutes, so one fetch of robots.txt per hour is enough.
            $this->robots[$origin] = Cache::remember('importer:robots:'.$origin, 3600, fn () => $this->robots($origin));
        }
        if (! $this->allowedByRobots($url, $this->robots[$origin])) {
            throw new SourceRequestException('Source robots rules disallow this URL.');
        }

        return $this->request($url);
    }

    public static function skipped(string $host): bool
    {
        foreach (config('importer.linked_pages.skip_hosts', []) as $skip) {
            if ($host === $skip || str_ends_with($host, '.'.$skip)) { return true; }
        }
        return false;
    }

    public function validate(string $url): array
    {
        $parts = parse_url($url);
        if (! is_array($parts) || ! in_array($parts['scheme'] ?? '', ['http', 'https'], true)
            || isset($parts['user']) || isset($parts['pass'])
            || (isset($parts['port']) && ! in_array($parts['port'], [80, 443], true))) {
            throw new SourceRequestException('Only public HTTP(S) source URLs are allowed.');
        }
        $host = strtolower(rtrim($parts['host'] ?? '', '.'));
        // Linked job pages can be on any public site, except the ones Nextward never reads.
        if ($this->source->provider === 'pages') {
            if (self::skipped($host)) { throw new SourceRequestException('Nextward does not read this site.'); }
        } elseif (! in_array($host, $this->source->allowed_domains ?? [], true)) {
            throw new SourceRequestException('URL host is outside the source allowlist.');
        }
        if (filter_var($host, FILTER_VALIDATE_IP) || $host === 'localhost' || ! str_contains($host, '.')) {
            throw new SourceRequestException('IP addresses and local hosts are not allowed.');
        }
        $parts['host'] = $host;
        return $parts;
    }

    private function request(string $url, int $redirects = 0): string
    {
        $parts = $this->validate($url);
        $url = (string) (new Uri($url))->withHost($parts['host']);
        if ($redirects > 4) {
            throw new SourceRequestException('Too many source redirects.');
        }
        if ($this->requests >= $this->source->max_requests) {
            throw new SourceBudgetException('Source request budget reached.');
        }
        $delay = max(0, ($this->source->request_delay_ms / 1000) - (microtime(true) - $this->lastRequest));
        if ($this->deadline && microtime(true) + $delay >= $this->deadline) {
            throw new SourceBudgetException('Run time budget reached.');
        }

        $addresses = self::$resolveUsing ? (self::$resolveUsing)($parts['host']) : array_values(array_filter(array_map(
            fn ($record) => $record['ip'] ?? $record['ipv6'] ?? null, dns_get_record($parts['host'], DNS_A | DNS_AAAA) ?: []
        )));
        if (! $addresses) {
            throw new SourceRequestException('Source host did not resolve.');
        }
        foreach ($addresses as $address) {
            if (! filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_GLOBAL_RANGE)) {
                throw new SourceRequestException('Source host resolves to a nonpublic address.');
            }
        }

        if ($delay > 0) {
            usleep((int) ($delay * 1000000));
        }
        $this->requests++;
        $this->lastRequest = microtime(true);
        $port = $parts['port'] ?? ($parts['scheme'] === 'https' ? 443 : 80);
        $address = str_contains($addresses[0], ':') ? '['.$addresses[0].']' : $addresses[0];
        $maximum = config('importer.max_response_bytes');
        $options = [
            'allow_redirects' => false,
            'on_headers' => function ($response) use ($maximum) {
                if ((int) $response->getHeaderLine('Content-Length') > $maximum) {
                    throw new SourceRequestException('Source response exceeds the size limit.');
                }
            },
            'progress' => function ($total, $downloaded) use ($maximum) {
                if ($downloaded > $maximum) {
                    throw new SourceRequestException('Source response exceeds the size limit.');
                }
            },
        ];
        if (! extension_loaded('curl')) {
            throw new SourceRequestException('The curl extension is required for pinned public source requests.');
        }
        // Pin the checked address and skip any proxy, so the request goes to the host we validated.
        $options['curl'] = [CURLOPT_RESOLVE => [$parts['host'].':'.$port.':'.$address]];
        $options['proxy'] = '';
        $response = Http::withUserAgent(config('importer.user_agent'))
            ->connectTimeout(5)->timeout(20)->withOptions($options)->get($url);

        if ($response->redirect()) {
            $target = self::resolveUrl($url, $response->header('Location'));
            $this->validate($target);
            // Every redirected page is checked against its own host's rules.
            return $this->getRedirect($target, $redirects + 1);
        }
        if (! $response->successful()) {
            $retry = $response->header('Retry-After');
            $seconds = ctype_digit((string) $retry) ? (int) $retry : max(0, (strtotime((string) $retry) ?: time()) - time());
            throw new SourceRequestException('Source returned HTTP '.$response->status().'.', $response->status(), $seconds);
        }
        if (strlen($response->body()) > $maximum) {
            throw new SourceRequestException('Source response exceeds the size limit.');
        }
        $this->url = $url;
        return $response->body();
    }

    private function getRedirect(string $url, int $redirects): string
    {
        $parts = $this->validate($url);
        $origin = $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
        // Redirects of robots.txt are bounded too; other redirects require known rules.
        if (! str_ends_with(parse_url($url, PHP_URL_PATH) ?: '', '/robots.txt') && ! isset($this->robots[$origin])) {
            $this->robots[$origin] = $this->robots($origin, $redirects);
        }
        if (isset($this->robots[$origin]) && ! $this->allowedByRobots($url, $this->robots[$origin])) {
            throw new SourceRequestException('Redirect is disallowed by robots rules.');
        }
        return $this->request($url, $redirects);
    }

    private function robots(string $origin, int $redirects = 0): string
    {
        try {
            return $this->request($origin.'/robots.txt', $redirects);
        } catch (SourceRequestException $e) {
            // RFC 9309: a 4xx robots.txt means no rules apply; rate limits and server errors do not.
            if ($e->status >= 400 && $e->status < 500 && $e->status !== 429) {
                return '';
            }
            throw $e;
        }
    }

    public function allowedByRobots(string $url, string $text): bool
    {
        $groups = []; $agents = []; $rules = []; $hadRule = false;
        foreach (preg_split('/\R/', $text) as $line) {
            $line = trim(explode('#', $line, 2)[0]);
            if (! str_contains($line, ':')) { continue; }
            [$key, $value] = array_map('trim', explode(':', $line, 2));
            $key = strtolower($key);
            if ($key === 'user-agent') {
                if ($hadRule) { $groups[] = [$agents, $rules]; $agents = []; $rules = []; $hadRule = false; }
                $agents[] = strtolower($value);
            } elseif (in_array($key, ['allow', 'disallow'], true) && $agents) {
                $hadRule = true;
                if ($value !== '') { $rules[] = [$key, $value]; }
            }
        }
        $groups[] = [$agents, $rules];
        // Keep honoring the former bot name while sources update their rules.
        $specific = array_filter($groups, fn ($group) => (bool) array_intersect(['nextwardbot', 'internlybot'], $group[0]));
        $groups = $specific ?: array_filter($groups, fn ($group) => in_array('*', $group[0], true));
        $path = (parse_url($url, PHP_URL_PATH) ?: '/').(($q = parse_url($url, PHP_URL_QUERY)) ? '?'.$q : '');
        $longest = -1; $allowed = true;
        foreach ($groups as [, $rules]) {
            foreach ($rules as [$kind, $rule]) {
                $pattern = str_replace(['\\*', '\\$'], ['.*', '$'], preg_quote($rule, '~'));
                $length = strlen(str_replace(['*', '$'], '', $rule));
                if (preg_match('~^'.$pattern.'~', $path) && ($length > $longest || ($length === $longest && $kind === 'allow'))) {
                    $longest = $length; $allowed = $kind === 'allow';
                }
            }
        }
        return $allowed;
    }
}

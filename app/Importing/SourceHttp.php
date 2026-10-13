<?php

namespace App\Importing;

use App\Models\ImportSource;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Illuminate\Support\Facades\Http;

class SourceHttp
{
    public int $requests = 0;
    private array $robots = [];
    private float $lastRequest = 0;

    public function __construct(private ImportSource $source) {}

    public static function resolveUrl(string $base, string $url): string
    {
        return (string) UriResolver::resolve(new Uri($base), new Uri($url));
    }

    public function get(string $url): string
    {
        $parts = $this->validate($url);
        $origin = $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
        if (! array_key_exists($origin, $this->robots)) {
            try {
                $this->robots[$origin] = $this->request($origin.'/robots.txt');
            } catch (SourceRequestException $e) {
                if ($e->status !== 404) {
                    throw $e;
                }
                $this->robots[$origin] = '';
            }
        }
        if (! $this->allowedByRobots($url, $this->robots[$origin])) {
            throw new SourceRequestException('Source robots rules disallow this URL.');
        }

        return $this->request($url);
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
        if (! in_array($host, $this->source->allowed_domains ?? [], true)) {
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
        if ($redirects > 4 || $this->requests >= $this->source->max_requests) {
            throw new SourceRequestException('Source request budget reached.');
        }

        $records = dns_get_record($parts['host'], DNS_A | DNS_AAAA) ?: [];
        $addresses = array_values(array_filter(array_map(fn ($record) => $record['ip'] ?? $record['ipv6'] ?? null, $records)));
        if (! $addresses) {
            throw new SourceRequestException('Source host did not resolve.');
        }
        foreach ($addresses as $address) {
            if (! filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_GLOBAL_RANGE)) {
                throw new SourceRequestException('Source host resolves to a nonpublic address.');
            }
        }

        $delay = max(0, ($this->source->request_delay_ms / 1000) - (microtime(true) - $this->lastRequest));
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
        $options['curl'] = [CURLOPT_RESOLVE => [$parts['host'].':'.$port.':'.$address], CURLOPT_PROXY => ''];
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
        return $response->body();
    }

    private function getRedirect(string $url, int $redirects): string
    {
        $parts = $this->validate($url);
        $origin = $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
        // Redirects of robots.txt are bounded too; other redirects require known rules.
        if (! str_ends_with(parse_url($url, PHP_URL_PATH) ?: '', '/robots.txt') && ! isset($this->robots[$origin])) {
            try {
                $this->robots[$origin] = $this->request($origin.'/robots.txt', $redirects);
            } catch (SourceRequestException $e) {
                if ($e->status !== 404) { throw $e; }
                $this->robots[$origin] = '';
            }
        }
        if (isset($this->robots[$origin]) && ! $this->allowedByRobots($url, $this->robots[$origin])) {
            throw new SourceRequestException('Redirect is disallowed by robots rules.');
        }
        return $this->request($url, $redirects);
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

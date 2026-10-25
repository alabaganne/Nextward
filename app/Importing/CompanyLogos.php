<?php

namespace App\Importing;

use App\Models\Company;
use GuzzleHttp\Psr7\Uri;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

// Copies outside company logos to the public disk, so pages never load images from other sites.
class CompanyLogos
{
    public const MAX_BYTES = 1024 * 1024;
    private const TYPES = [IMAGETYPE_PNG => 'png', IMAGETYPE_JPEG => 'jpg', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp'];

    // The address the last download ended on, after redirects.
    public ?string $lastUrl = null;

    // Keeps the last good copy when the download or the image check fails.
    public function fetch(Company $company): bool
    {
        $company->forceFill(['logo_fetched_at' => now()])->save();
        try {
            $body = $this->download((string) $company->logo_url);
        } catch (\Throwable) {
            return false;
        }
        $size = @getimagesizefromstring($body);
        $extension = self::TYPES[$size[2] ?? 0] ?? null;
        if (! $extension || $size[0] < 16 || $size[1] < 16) {
            return false;
        }

        $old = $company->logo_path;
        $path = 'company-logos/imported/'.$company->id.'-'.substr(sha1($body), 0, 16).'.'.$extension;
        Storage::disk('public')->put($path, $body);
        $company->forceFill(['logo_path' => $path])->save();
        if ($old && $old !== $path) {
            Storage::disk('public')->delete($old);
        }
        return true;
    }

    // Fetches a public URL of at most $max bytes, following up to 3 redirects and never reaching a private address.
    public function download(string $url, int $redirects = 0, int $max = self::MAX_BYTES): string
    {
        $parts = parse_url($url) ?: [];
        $host = strtolower(rtrim($parts['host'] ?? '', '.'));
        if (! in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true) || isset($parts['user']) || isset($parts['pass'])
            || (isset($parts['port']) && ! in_array($parts['port'], [80, 443], true))
            || filter_var($host, FILTER_VALIDATE_IP) || ! str_contains($host, '.')) {
            throw new RuntimeException('Not a public URL.');
        }
        $addresses = SourceHttp::$resolveUsing ? (SourceHttp::$resolveUsing)($host) : array_values(array_filter(array_map(
            fn ($record) => $record['ip'] ?? $record['ipv6'] ?? null, dns_get_record($host, DNS_A | DNS_AAAA) ?: []
        )));
        if (! $addresses || array_filter($addresses, fn ($address) => ! filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_GLOBAL_RANGE))) {
            throw new RuntimeException('Host is not public.');
        }
        if (! extension_loaded('curl')) {
            throw new RuntimeException('The curl extension is required.');
        }

        $port = $parts['port'] ?? (strtolower($parts['scheme']) === 'https' ? 443 : 80);
        $address = str_contains($addresses[0], ':') ? '['.$addresses[0].']' : $addresses[0];
        $url = (string) (new Uri($url))->withHost($host);
        $response = Http::withUserAgent(config('importer.user_agent'))->connectTimeout(5)->timeout(15)->withOptions([
            'allow_redirects' => false,
            // Pin the checked address and skip any proxy, so the request goes to the host we checked.
            'curl' => [CURLOPT_RESOLVE => [$host.':'.$port.':'.$address]],
            'proxy' => '',
            'on_headers' => function ($response) use ($max) {
                if ((int) $response->getHeaderLine('Content-Length') > $max) {
                    throw new RuntimeException('File is too large.');
                }
            },
            'progress' => function ($total, $downloaded) use ($max) {
                if ($downloaded > $max) {
                    throw new RuntimeException('File is too large.');
                }
            },
        ])->get($url);

        if ($response->redirect()) {
            if ($redirects >= 3) {
                throw new RuntimeException('Too many redirects.');
            }
            return $this->download(SourceHttp::resolveUrl($url, $response->header('Location')), $redirects + 1, $max);
        }
        if (! $response->successful() || strlen($response->body()) > $max) {
            throw new RuntimeException('Download failed.');
        }
        $this->lastUrl = $url;
        return $response->body();
    }
}

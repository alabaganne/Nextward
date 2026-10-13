<?php

namespace App\Indexing;

use App\Models\Internship;
use App\Support\PublicSeo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;

class GoogleIndexing
{
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const PUBLISH_URL = 'https://indexing.googleapis.com/v3/urlNotifications:publish';
    private ?string $token = null;
    private int $tokenExpiresAt = 0;

    public function queue(Internship $opportunity): void
    {
        if (!Schema::hasTable('indexing_notifications')) return;
        if ($opportunity->merged_into_id) {
            DB::table('indexing_notifications')->where('internship_id', $opportunity->id)
                ->whereIn('status', ['pending', 'queued'])->update(['status' => 'superseded', 'updated_at' => now()]);
            return;
        }

        $url = $this->canonical($opportunity);
        DB::transaction(function () use ($opportunity, $url) {
            Internship::whereKey($opportunity->id)->lockForUpdate()->first();
            $previous = DB::table('indexing_notifications')->where('url', $url)->latest('id')->first();
            $eligible = $this->eligible($opportunity);
            if (!$eligible && !$previous) return;
            $type = $opportunity->isOpen() ? 'URL_UPDATED' : 'URL_DELETED';
            if (!$eligible && $previous?->type === $type && in_array($previous->status, ['pending', 'queued', 'sent'], true)) return;
            $this->enqueue($opportunity->id, $url, $type);
        });
    }

    public function deleted(Internship $opportunity): void
    {
        if (!Schema::hasTable('indexing_notifications')) return;
        $url = $this->canonical($opportunity);
        if ($this->eligible($opportunity) || DB::table('indexing_notifications')->where('url', $url)->exists()) {
            $this->enqueue(null, $url, 'URL_DELETED');
        }
    }

    public function reconcile(): void
    {
        // Time-based expiry and database cascade deletions do not emit model updates.
        DB::table('indexing_notifications')->where('type', 'URL_UPDATED')
            ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('indexing_notifications as newer')
                ->whereColumn('newer.url', 'indexing_notifications.url')->whereColumn('newer.id', '>', 'indexing_notifications.id'))
            ->orderBy('id')->chunkById(100, function ($rows) {
            foreach ($rows as $row) {
                if (DB::table('indexing_notifications')->where('url', $row->url)->where('id', '>', $row->id)->exists()) continue;
                $opportunity = $row->internship_id ? Internship::find($row->internship_id) : null;
                if (!$opportunity) {
                    $this->enqueue(null, $row->url, 'URL_DELETED');
                } elseif (!$opportunity->isOpen() && !$opportunity->merged_into_id) {
                    $this->queue($opportunity);
                }
            }
        });
    }

    public function eligible(Internship $opportunity): bool
    {
        return collect(PublicSeo::opportunity($opportunity)['structured_data'] ?? [])
            ->contains(fn ($entry) => ($entry['@type'] ?? null) === 'JobPosting');
    }

    public function send(int $id): void
    {
        if (!config('importer.indexing_enabled')) {
            DB::table('indexing_notifications')->where('id', $id)->where('status', 'queued')->update(['status' => 'pending', 'updated_at' => now()]);
            return;
        }

        $pending = DB::table('indexing_notifications')->find($id);
        if (!$pending) return;
        Cache::lock('internly:indexing:url:'.hash('sha256', $pending->url), 90)->get(function () use ($id) {
            $row = DB::table('indexing_notifications')->find($id);
            if (!$row || !in_array($row->status, ['pending', 'queued'], true)) return;
            if ($row->available_at && \Illuminate\Support\Carbon::parse($row->available_at)->isFuture()) return;
            if (DB::table('indexing_notifications')->where('url', $row->url)->where('id', '>', $id)->exists()) {
                $this->update($id, ['status' => 'superseded']);
                return;
            }
            $opportunity = $row->internship_id ? Internship::find($row->internship_id) : null;
            if ($opportunity && ($opportunity->merged_into_id || $this->canonical($opportunity) !== $row->url)) {
                $this->update($id, ['status' => 'superseded']);
                if (!$opportunity->merged_into_id) $this->queue($opportunity);
                return;
            }
            $type = $opportunity && $opportunity->isOpen() ? 'URL_UPDATED' : 'URL_DELETED';
            if ($type === 'URL_UPDATED' && !$this->eligible($opportunity)
                && !DB::table('indexing_notifications')->where('url', $row->url)->where('status', 'sent')->exists()) {
                $this->update($id, ['status' => 'superseded']);
                return;
            }
            try {
                $this->validateUrl($row->url);
                $quotaKey = 'internly:indexing:daily';
                $token = $this->accessToken();
                $reserved = Cache::lock('internly:indexing:quota', 10)->get(function () use ($quotaKey) {
                    if (RateLimiter::tooManyAttempts($quotaKey, max(1, (int) config('importer.indexing_daily_limit', 200)))) return false;
                    RateLimiter::hit($quotaKey, 86400);
                    return true;
                });
                if (!$reserved) {
                    $this->update($id, ['status' => 'pending', 'available_at' => now()->addSeconds(max(60, RateLimiter::availableIn($quotaKey))), 'error' => 'Daily submission limit reached.']);
                    return;
                }
                $response = Http::withToken($token)->acceptJson()->asJson()->connectTimeout(5)->timeout(20)
                    ->withoutRedirecting()->post(self::PUBLISH_URL, ['url' => $row->url, 'type' => $type]);
                $attempts = $row->attempts + 1;
                if ($response->successful()) {
                    $this->update($id, ['type' => $type, 'status' => 'sent', 'attempts' => $attempts, 'sent_at' => now(), 'error' => null]);
                    return;
                }
                $retryable = $response->status() === 429 || $response->serverError();
                $retryAfter = $response->header('Retry-After');
                $delay = is_string($retryAfter) && ctype_digit($retryAfter) ? (int) $retryAfter : min(86400, 300 * (2 ** min($attempts, 8)));
                if (is_string($retryAfter) && !ctype_digit($retryAfter) && strtotime($retryAfter) !== false) $delay = max(60, strtotime($retryAfter) - time());
                $this->update($id, [
                    'type' => $type, 'status' => $retryable && $attempts < 8 ? 'pending' : 'failed', 'attempts' => $attempts,
                    'available_at' => now()->addSeconds(max(60, min($delay, 172800))),
                    'error' => 'Google Indexing API returned HTTP '.$response->status().'. Check ownership, credentials, or quota.',
                ]);
            } catch (\Throwable $exception) {
                // Do not persist response bodies, JWT assertions, tokens, or credentials.
                $attempts = $row->attempts + 1;
                $this->update($id, ['status' => $attempts < 8 ? 'pending' : 'failed', 'attempts' => $attempts,
                    'available_at' => now()->addMinutes(min(1440, 5 * (2 ** min($attempts, 8)))),
                    'error' => 'Indexing delivery failed. Verify private credentials, public APP_URL, and Google connectivity.']);
            }
        });
    }

    private function enqueue(?int $opportunityId, string $url, string $type): void
    {
        DB::transaction(function () use ($opportunityId, $url, $type) {
            $pending = DB::table('indexing_notifications')->where('url', $url)->where('status', 'pending')->lockForUpdate()->latest('id')->first();
            if ($pending) {
                $this->update($pending->id, ['internship_id' => $opportunityId, 'type' => $type, 'attempts' => 0, 'available_at' => now(), 'error' => null]);
                return;
            }
            DB::table('indexing_notifications')->where('url', $url)->where('status', 'queued')->update(['status' => 'superseded', 'updated_at' => now()]);
            DB::table('indexing_notifications')->insert([
                'internship_id' => $opportunityId, 'url' => $url, 'type' => $type, 'status' => 'pending',
                'attempts' => 0, 'available_at' => now(), 'created_at' => now(), 'updated_at' => now(),
            ]);
        });
    }

    private function update(int $id, array $data): void
    {
        DB::table('indexing_notifications')->where('id', $id)->update($data + ['updated_at' => now()]);
    }

    private function canonical(Internship $opportunity): string
    {
        return rtrim((string) config('app.url'), '/').'/opportunities/'.$opportunity->id.'-'.($opportunity->slug ?: \Illuminate\Support\Str::slug($opportunity->title) ?: 'opportunity');
    }

    private function validateUrl(string $url): void
    {
        $parts = parse_url($url);
        $host = $parts['host'] ?? '';
        $configured = parse_url((string) config('app.url'));
        if (($parts['scheme'] ?? '') !== 'https' || $host !== ($configured['host'] ?? null)
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])
            || !str_contains($host, '.') || filter_var($host, FILTER_VALIDATE_IP)
            || preg_match('/\.(localhost|local|test|invalid)$/i', $host)
            || !str_starts_with($parts['path'] ?? '', '/opportunities/')) {
            throw new \RuntimeException('Indexing requires a public HTTPS APP_URL.');
        }
    }

    private function accessToken(): string
    {
        if ($this->token && $this->tokenExpiresAt > time() + 60) return $this->token;
        $configuredPath = config('importer.google_credentials');
        $path = is_string($configuredPath) ? realpath($configuredPath) : false;
        $public = realpath(public_path());
        $publicStorage = realpath(storage_path('app/public'));
        if (!$path || !is_file($path) || !is_readable($path) || filesize($path) > 65536
            || ($public && str_starts_with($path, $public.DIRECTORY_SEPARATOR))
            || ($publicStorage && str_starts_with($path, $publicStorage.DIRECTORY_SEPARATOR))) {
            throw new \RuntimeException('Configure a private service-account credentials file.');
        }
        $credentials = json_decode(file_get_contents($path), true, 10, JSON_THROW_ON_ERROR);
        if (($credentials['type'] ?? '') !== 'service_account' || !filter_var($credentials['client_email'] ?? '', FILTER_VALIDATE_EMAIL)
            || !is_string($credentials['private_key'] ?? null)) {
            throw new \RuntimeException('Invalid service-account credentials.');
        }
        $key = openssl_pkey_get_private($credentials['private_key']);
        $details = $key ? openssl_pkey_get_details($key) : false;
        if (!$details || $details['type'] !== OPENSSL_KEYTYPE_RSA || $details['bits'] < 2048) {
            throw new \RuntimeException('A valid RSA service-account key is required.');
        }
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        if (is_string($credentials['private_key_id'] ?? null)) $header['kid'] = $credentials['private_key_id'];
        $claims = ['iss' => $credentials['client_email'], 'scope' => 'https://www.googleapis.com/auth/indexing',
            'aud' => self::TOKEN_URL, 'iat' => time(), 'exp' => time() + 3600];
        $unsigned = $this->base64(json_encode($header, JSON_THROW_ON_ERROR)).'.'.$this->base64(json_encode($claims, JSON_THROW_ON_ERROR));
        if (!openssl_sign($unsigned, $signature, $key, OPENSSL_ALGO_SHA256)) {
            throw new \RuntimeException('Could not sign service-account assertion.');
        }
        $response = Http::asForm()->acceptJson()->connectTimeout(5)->timeout(20)->withoutRedirecting()->post(self::TOKEN_URL, [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer', 'assertion' => $unsigned.'.'.$this->base64($signature),
        ]);
        if (!$response->successful() || !is_string($response->json('access_token'))) {
            throw new \RuntimeException('Google OAuth token request failed.');
        }
        $this->token = $response->json('access_token');
        $this->tokenExpiresAt = time() + min(3600, max(60, (int) $response->json('expires_in', 3600)));
        return $this->token;
    }

    private function base64(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}

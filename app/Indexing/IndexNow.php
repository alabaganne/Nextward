<?php

namespace App\Indexing;

use Illuminate\Support\Facades\Http;

// Tells Bing, Yandex, Seznam and Naver about new or changed URLs (https://www.indexnow.org).
class IndexNow
{
    private const ENDPOINT = 'https://api.indexnow.org/indexnow';

    public function enabled(): bool
    {
        return strlen((string) config('services.indexnow.key')) >= 8;
    }

    public function submit(array $urls): int
    {
        $host = parse_url(config('app.url'), PHP_URL_HOST);
        $urls = array_values(array_unique(array_filter($urls, fn ($url) => is_string($url) && parse_url($url, PHP_URL_HOST) === $host)));
        if (!$this->enabled() || !$urls) return 0;
        foreach (array_chunk($urls, 10000) as $chunk) {
            Http::timeout(20)->acceptJson()->post(self::ENDPOINT, [
                'host' => $host,
                'key' => config('services.indexnow.key'),
                'keyLocation' => rtrim(config('app.url'), '/').'/indexnow-key.txt',
                'urlList' => $chunk,
            ])->throw();
        }
        return count($urls);
    }
}

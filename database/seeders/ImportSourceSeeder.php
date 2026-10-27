<?php

namespace Database\Seeders;

use App\Models\ImportSource;
use Illuminate\Database\Seeder;

class ImportSourceSeeder extends Seeder
{
    private const BOARD_NOTE = 'Public job board API the employer publishes so its jobs can be shown on other sites. We show the facts, a plain-text copy of the description and a link to apply on the employer site. Checked on 2026-10-15.';

    public function run(): void
    {
        foreach ($this->sources() as $source) {
            // Keep any changes an admin made to a source that already exists.
            ImportSource::firstOrCreate(['key' => $source['key']], $source + [
                'permission_status' => 'approved', 'publication_mode' => 'automatic', 'enabled' => true, 'cadence_hours' => 24,
            ]);
        }
    }

    private function sources(): array
    {
        $greenhouse = [
            'canonical' => ['job-boards.greenhouse.io'], 'affirm' => ['job-boards.greenhouse.io'], 'figma' => ['boards.greenhouse.io'],
            'duolingo' => ['careers.duolingo.com'], 'gitlab' => ['job-boards.greenhouse.io'], 'grafanalabs' => ['job-boards.greenhouse.io'],
            'mozilla' => ['job-boards.greenhouse.io'], 'wikimedia' => ['job-boards.greenhouse.io'], 'discord' => ['job-boards.greenhouse.io'],
            'robinhood' => ['boards.greenhouse.io'], 'airbnb' => ['careers.airbnb.com'],
            // Hire fully remote across EMEA or worldwide, or help with visas and relocation in Europe.
            'automatticcareers' => ['job-boards.greenhouse.io'], 'elastic' => ['job-boards.greenhouse.io'], 'vercel' => ['job-boards.greenhouse.io'],
            'getyourguide' => ['job-boards.greenhouse.io'], 'contentful' => ['job-boards.greenhouse.io'], 'adyen' => ['job-boards.greenhouse.io'],
            'n26' => ['job-boards.greenhouse.io'], 'wolt' => ['job-boards.greenhouse.io'], 'celonis' => ['job-boards.greenhouse.io'],
            // Y Combinator companies with offices in Europe or remote roles (ycombinator.com/jobs itself forbids scraping).
            'stripe' => ['stripe.com'], 'coinbase' => ['www.coinbase.com'], 'reddit' => ['job-boards.greenhouse.io'], 'dropbox' => ['jobs.dropbox.com'],
            'algolia' => ['job-boards.greenhouse.io'], 'fivetran' => ['www.fivetran.com'], 'flexport' => ['job-boards.greenhouse.io'],
            'scaleai' => ['job-boards.greenhouse.io'], 'mixpanel' => ['job-boards.greenhouse.io'],
        ];
        $lever = ['spotify' => 'Spotify', 'toptal' => 'Toptal', 'binance' => 'Binance'];
        $ashby = ['ramp' => 'Ramp', 'notion' => 'Notion', 'linear' => 'Linear', 'posthog' => 'PostHog', 'supabase' => 'Supabase', 'zapier' => 'Zapier', 'replit' => 'Replit',
            'n8n' => 'n8n', 'resend' => 'Resend', 'sanity' => 'Sanity',
            // Y Combinator companies.
            'clipboard' => 'Clipboard Health', 'docker' => 'Docker', 'webflow' => 'Webflow', 'vanta' => 'Vanta', 'infisical' => 'Infisical',
            'triggerdev' => 'Trigger.dev', 'lago' => 'Lago', 'photoroom' => 'Photoroom', 'encord' => 'Encord', 'kombo' => 'Kombo',
            'amplitude' => 'Amplitude', 'benchling' => 'Benchling', 'upflow' => 'Upflow'];
        $workable = ['huggingface' => 'Hugging Face'];
        $recruitee = ['bunq' => 'bunq'];

        $sources = [];
        foreach ($greenhouse as $token => $domains) {
            $sources[] = ['key' => 'greenhouse-'.$token, 'name' => ucfirst($token).' (Greenhouse)', 'provider' => 'greenhouse',
                'url' => "https://boards-api.greenhouse.io/v1/boards/{$token}/jobs?content=true", 'allowed_domains' => ['boards-api.greenhouse.io', ...$domains],
                'permission_notes' => self::BOARD_NOTE, 'settings' => ['scope' => 'reachable', 'authoritative_employer' => true],
                'request_delay_ms' => 1000, 'max_requests' => 20];
        }
        foreach ($lever as $token => $name) {
            $sources[] = ['key' => 'lever-'.$token, 'name' => $name.' (Lever)', 'provider' => 'lever',
                'url' => "https://api.lever.co/v0/postings/{$token}?mode=json", 'allowed_domains' => ['api.lever.co', 'jobs.lever.co'],
                'permission_notes' => self::BOARD_NOTE, 'settings' => ['employer_name' => $name, 'scope' => 'reachable', 'authoritative_employer' => true],
                'request_delay_ms' => 1000, 'max_requests' => 20];
        }
        foreach ($ashby as $token => $name) {
            $sources[] = ['key' => 'ashby-'.$token, 'name' => $name.' (Ashby)', 'provider' => 'ashby',
                'url' => "https://api.ashbyhq.com/posting-api/job-board/{$token}?includeCompensation=true", 'allowed_domains' => ['api.ashbyhq.com', 'jobs.ashbyhq.com'],
                'permission_notes' => self::BOARD_NOTE, 'settings' => ['employer_name' => $name, 'scope' => 'reachable', 'authoritative_employer' => true],
                'request_delay_ms' => 1000, 'max_requests' => 20];
        }
        foreach ($workable as $token => $name) {
            $sources[] = ['key' => 'workable-'.$token, 'name' => $name.' (Workable)', 'provider' => 'workable',
                'url' => "https://apply.workable.com/api/v1/widget/accounts/{$token}?details=true", 'allowed_domains' => ['apply.workable.com'],
                'permission_notes' => self::BOARD_NOTE, 'settings' => ['employer_name' => $name, 'scope' => 'reachable', 'authoritative_employer' => true],
                'request_delay_ms' => 1000, 'max_requests' => 20];
        }
        foreach ($recruitee as $token => $name) {
            $sources[] = ['key' => 'recruitee-'.$token, 'name' => $name.' (Recruitee)', 'provider' => 'recruitee',
                'url' => "https://{$token}.recruitee.com/api/offers", 'allowed_domains' => ["{$token}.recruitee.com"],
                'permission_notes' => self::BOARD_NOTE, 'settings' => ['employer_name' => $name, 'scope' => 'reachable', 'authoritative_employer' => true],
                'request_delay_ms' => 1000, 'max_requests' => 20];
        }
        // Remote job boards feed the owner's job leads only; their listings never show on the public site.
        $note = 'Remote job board read for private job leads only: listings are never published, shown to visitors or sent to other sites, and each lead links back to the board. Checked on 2026-10-25.';
        $boards = [
            ['himalayas', 'Himalayas', 'https://himalayas.app/jobs/api?limit=20', ['himalayas.app'], 6, 15, ['pages' => 10]],
            ['remoteok', 'Remote OK', 'https://remoteok.com/api', ['remoteok.com'], 12, 3, []],
            ['remotive', 'Remotive', 'https://remotive.com/api/remote-jobs?category=software-dev', ['remotive.com'], 24, 2, []],
            ['wwr-full-stack', 'We Work Remotely: full-stack', 'https://weworkremotely.com/categories/remote-full-stack-programming-jobs.rss', ['weworkremotely.com'], 12, 2, []],
            ['wwr-front-end', 'We Work Remotely: front-end', 'https://weworkremotely.com/categories/remote-front-end-programming-jobs.rss', ['weworkremotely.com'], 12, 2, []],
            ['wwr-back-end', 'We Work Remotely: back-end', 'https://weworkremotely.com/categories/remote-back-end-programming-jobs.rss', ['weworkremotely.com'], 12, 2, []],
            ['hn-who-is-hiring', 'Hacker News: Who is hiring', 'https://hn.algolia.com/api/v1/search_by_date?tags=story,author_whoishiring&hitsPerPage=5', ['hn.algolia.com', 'news.ycombinator.com'], 24, 4, []],
        ];
        foreach ($boards as [$key, $name, $url, $domains, $cadence, $requests, $settings]) {
            $sources[] = ['key' => $key, 'name' => $name, 'provider' => explode('-', $key)[0], 'url' => $url,
                'allowed_domains' => $domains, 'permission_notes' => $note, 'publication_mode' => 'leads', 'cadence_hours' => $cadence,
                'settings' => $settings + ['scope' => 'all'], 'request_delay_ms' => 1500, 'max_requests' => $requests];
        }
        // The links in Hacker News "Who is hiring" posts: the AI opens each one and real jobs wait for review.
        $sources[] = ['key' => 'linked-job-pages', 'name' => 'Job pages linked from Hacker News', 'provider' => 'pages',
            'url' => 'https://news.ycombinator.com/', 'allowed_domains' => [], 'publication_mode' => 'review',
            'permission_notes' => 'Employer job pages linked from Hacker News "Who is hiring" posts. Only the employer page is shown, with a link to apply there; the post itself is never published. Each site\'s robots rules apply. Checked on 2026-10-27.',
            'settings' => ['scope' => 'all'], 'request_delay_ms' => 1000, 'max_requests' => 300];
        return $sources;
    }
}

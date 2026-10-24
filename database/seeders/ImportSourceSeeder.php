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
        ];
        $lever = ['spotify' => 'Spotify', 'toptal' => 'Toptal', 'binance' => 'Binance'];
        $ashby = ['ramp' => 'Ramp', 'notion' => 'Notion', 'linear' => 'Linear', 'posthog' => 'PostHog', 'supabase' => 'Supabase', 'zapier' => 'Zapier', 'replit' => 'Replit'];

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
        return $sources;
    }
}

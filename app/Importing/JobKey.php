<?php

namespace App\Importing;

use App\Models\CompanyAlias;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

// The keys that tell whether two listings are the same job: company, title, URL and employer ATS id.
class JobKey
{
    private const SUFFIXES = 'inc|incorporated|ltd|limited|llc|llp|gmbh|ag|sa|sas|sasu|sarl|suarl|spa|srl|bv|nv|plc|corp|corporation|co|oy|ab|as|pty|pte|kk|ug|group|groupe|holdings?';
    private const NOISE = 'senior|sr|junior|jr|mid|mid level|midlevel|intermediate|entry level|i|ii|iii|iv|1|2|3';
    private const MODES = '100 remote|fully remote|full remote|remote first|remote|hybrid|on site|onsite|in office|work from home|wfh|anywhere|worldwide|global';
    private const PLACES = 'emea|apac|latam|amer|americas|europe|eu|uk|us|usa|north america|south america|latin america|mena|africa|asia|cet|est|pst|utc|gmt|time ?zones?|based|only|timezone';

    public static function company(string $name): string
    {
        $key = self::name($name);
        return self::aliases()[$key] ?? $key;
    }

    // The company name without case, accents, punctuation or legal suffixes such as Inc, Ltd or GmbH.
    public static function name(string $name): string
    {
        $name = mb_strtolower(Str::ascii($name));
        $name = preg_replace('/[.,]/', ' ', $name);
        $name = preg_replace('/\b(?:'.self::SUFFIXES.')\b/', ' ', $name);
        return Str::squish(preg_replace('/[^a-z0-9]+/', ' ', $name));
    }

    // The title without seniority words, work modes, gender marks and location suffixes, so board copies match the employer's.
    public static function title(string $title, ?string $location = null): string
    {
        $title = mb_strtolower(Str::ascii($title));
        $title = preg_replace('~\b[hfmwdx]\s*/\s*[hfmwdx](?:\s*/\s*[hfmwdxn]{1,2})?\b|\ball genders\b~', ' ', $title);
        $places = self::placeWords($location);
        // Brackets that only hold a place or work mode go; "(Frontend)" stays.
        $title = preg_replace_callback('~\(([^)]*)\)|\[([^\]]*)\]~', fn ($m) => self::onlyPlace($m[1] ?: ($m[2] ?? ''), $places) ? ' ' : ' '.($m[1] ?: ($m[2] ?? '')).' ', $title);
        $parts = preg_split('~\s+[-|@/]\s+|\s*,\s*|\s+[–—]\s+~u', $title);
        while (count($parts) > 1 && self::onlyPlace(end($parts), $places)) { array_pop($parts); }
        $title = implode(' ', $parts);
        $title = preg_replace(['/\bfront[\s-]?end\b/', '/\bback[\s-]?end\b/', '/\bfull[\s-]?stack\b/', '/\bsr\./'], ['frontend', 'backend', 'fullstack', 'senior'], $title);
        $title = preg_replace('/[^a-z0-9+#]+/', ' ', $title);
        $title = preg_replace('/\b(?:'.self::MODES.'|'.self::NOISE.')\b/', ' ', ' '.$title.' ');
        return Str::squish($title);
    }

    public static function key(string $company, string $title, ?string $location = null): string
    {
        return sha1(self::company($company).'|'.self::title($title, $location));
    }

    // How close two normalized titles are, from 0 to 1, by the words they share.
    public static function similar(string $a, string $b): float
    {
        $a = array_unique(array_filter(explode(' ', $a))); $b = array_unique(array_filter(explode(' ', $b)));
        if (! $a || ! $b) { return 0.0; }
        return round(2 * count(array_intersect($a, $b)) / (count($a) + count($b)), 3);
    }

    // The posting URL without tracking: no query or fragment (but ATS job ids), no apply suffix, one Greenhouse host.
    public static function url(?string $url): ?string
    {
        if (! $url || ! ($parts = parse_url(trim($url))) || empty($parts['host'])) { return null; }
        $host = preg_replace('/^www\./', '', strtolower($parts['host']));
        $path = rtrim(preg_replace('~/+~', '/', $parts['path'] ?? ''), '/');
        $path = preg_replace('~/(?:apply|application|applications/new)$~i', '', $path);
        parse_str($parts['query'] ?? '', $query);
        if ($ats = self::ats($url)) {
            return match ($ats['provider']) {
                'greenhouse' => 'https://job-boards.greenhouse.io/'.$ats['slug'].'/jobs/'.$ats['id'],
                'lever' => 'https://jobs.lever.co/'.$ats['slug'].'/'.$ats['id'],
                'ashby' => 'https://jobs.ashbyhq.com/'.$ats['slug'].'/'.$ats['id'],
                'workable' => 'https://apply.workable.com/'.($ats['slug'] !== '' ? $ats['slug'].'/' : '').'j/'.$ats['id'],
                'recruitee' => 'https://'.$ats['slug'].'.recruitee.com/o/'.$ats['id'],
            };
        }
        $keep = array_intersect_key($query, array_flip(['gh_jid', 'jobId', 'job_id', 'jid', 'id']));
        return 'https://'.$host.$path.($keep ? '?'.http_build_query($keep) : '');
    }

    // The employer ATS behind a link: provider, board slug and job id, or null.
    public static function ats(?string $url): ?array
    {
        if (! $url) { return null; }
        $patterns = [
            'greenhouse' => '~(?:job-)?boards(?:\.eu)?\.greenhouse\.io/(?:embed/job_app\?(?:.*&)?for=)?([\w-]+)(?:/jobs/|.*[?&]token=)(\d+)~i',
            'greenhouse-api' => '~boards-api\.greenhouse\.io/v1/boards/([\w-]+)/jobs/(\d+)~i',
            'lever' => '~jobs\.(?:eu\.)?lever\.co/([\w.-]+)/([0-9a-f]{8}-[0-9a-f-]{27})~i',
            'ashby' => '~jobs\.ashbyhq\.com/([^/?#]+)/([0-9a-f]{8}-[0-9a-f-]{27})~i',
            'workable' => '~apply\.workable\.com/(?:(?!j/)([\w-]+)/)?j/([A-Z0-9]+)~i',
            'recruitee' => '~([\w-]+)\.recruitee\.com/o/([\w-]+)~i',
        ];
        foreach ($patterns as $provider => $pattern) {
            if (preg_match($pattern, $url, $m)) {
                return ['provider' => str_replace('-api', '', $provider), 'slug' => strtolower(rawurldecode($m[1])), 'id' => $m[2]];
            }
        }
        return null;
    }

    // The public board API for an ATS slug, as import sources store it.
    public static function boardUrl(string $provider, string $slug): ?string
    {
        return match ($provider) {
            'greenhouse' => 'https://boards-api.greenhouse.io/v1/boards/'.$slug.'/jobs?content=true',
            'lever' => 'https://api.lever.co/v0/postings/'.$slug.'?mode=json',
            'ashby' => 'https://api.ashbyhq.com/posting-api/job-board/'.$slug,
            'workable' => 'https://apply.workable.com/api/v1/widget/accounts/'.$slug.'?details=true',
            'recruitee' => 'https://'.$slug.'.recruitee.com/api/offers',
            default => null,
        };
    }

    // The hosts an import source for that board may read.
    public static function boardDomains(string $provider, string $slug): array
    {
        return match ($provider) {
            'greenhouse' => ['boards-api.greenhouse.io', 'job-boards.greenhouse.io', 'boards.greenhouse.io'],
            'lever' => ['api.lever.co', 'jobs.lever.co'],
            'ashby' => ['api.ashbyhq.com', 'jobs.ashbyhq.com'],
            'workable' => ['apply.workable.com'],
            'recruitee' => [$slug.'.recruitee.com'],
            default => [],
        };
    }

    public static function forget(): void
    {
        app()->forgetInstance('job-key.aliases');
    }

    private static function aliases(): array
    {
        if (! app()->bound('job-key.aliases')) {
            app()->instance('job-key.aliases', Schema::hasTable('company_aliases') ? CompanyAlias::pluck('name', 'alias')->all() : []);
        }
        return app('job-key.aliases');
    }

    private static function placeWords(?string $location): array
    {
        $words = preg_split('/[^a-z]+/', mb_strtolower(Str::ascii((string) $location)), -1, PREG_SPLIT_NO_EMPTY);
        return array_values(array_filter($words, fn ($word) => strlen($word) > 2));
    }

    private static function onlyPlace(string $text, array $places): bool
    {
        $text = Str::squish(preg_replace('/[^a-z0-9%]+/', ' ', $text));
        if ($text === '') { return true; }
        $known = '(?:\d+ ?%|'.self::MODES.'|'.self::PLACES.($places ? '|'.implode('|', array_map('preg_quote', $places)) : '').')';
        if (preg_match('/^(?:'.$known.'|or|and|in|of|the)(?: (?:'.$known.'|or|and|in|of|the))*$/', $text)) { return true; }
        $normalizer = new OpportunityNormalizer();
        return $normalizer->country($text) !== null || $normalizer->limitedToOtherRegions($text) && str_word_count($text) <= 3;
    }
}

<?php

namespace App\Importing;

use App\Ai\Agents\CompanyResearcher;
use App\Ai\OpportunityClassifier;
use App\Models\Company;
use App\Models\CompanyAlias;
use App\Models\OpportunitySource;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

// Finds a company's own website (with AI web search when it is on) and checks it: the site must open as a web page
// and show the company's name or link to its job board. Only then the company gets verified_at and the Verified badge.
class CompanyVerifier
{
    // Hosts that are never a company's own website.
    private const NOT_OWN_SITE = ['linkedin.com', 'facebook.com', 'x.com', 'twitter.com', 'instagram.com', 'youtube.com', 'tiktok.com',
        'github.com', 'medium.com', 'substack.com', 'wikipedia.org', 'crunchbase.com', 'glassdoor.com', 'indeed.com', 'wellfound.com',
        'angel.co', 'ycombinator.com', 'builtin.com', 'zoominfo.com', 'pitchbook.com', 'bloomberg.com', 'greenhouse.io', 'lever.co',
        'ashbyhq.com', 'workable.com', 'recruitee.com', 'smartrecruiters.com', 'myworkdayjobs.com', 'bamboohr.com', 'teamtailor.com',
        'himalayas.app', 'remoteok.com', 'remotive.com', 'weworkremotely.com', 'welcometothejungle.com', 'linktr.ee', 'notion.site', 'sites.google.com'];

    public function __construct(private OpportunityClassifier $classifier, private CompanyLogos $fetcher) {}

    // true when verified, false when this try failed, null when nothing could be checked (the AI is off or failed
    // and no website is known), so the try does not count.
    public function verify(Company $company): ?bool
    {
        if ($company->verified_at) { return true; }
        $boards = $this->boards($company);
        $facts = [];
        if ($this->classifier->enabled()) {
            try {
                $facts = (new CompanyResearcher)->prompt($this->prompt($company, $boards), provider: $this->classifier->provider(), model: $this->classifier->model('discovery'))->toArray();
            } catch (Throwable $e) {
                Log::warning('Company research failed.', ['company_id' => $company->id, 'error' => Str::limit($e->getMessage(), 300)]);
                if (! $company->website) { return null; }
            }
        }
        $sites = array_unique(array_filter([$this->site((string) $company->website), ! empty($facts['found']) ? $this->site((string) $facts['website']) : null]));
        if (! $sites) {
            return $facts ? $this->failed($company, 'No website found.') : null;
        }
        $reason = 'The website could not be opened.';
        foreach ($sites as $site) {
            try {
                // Home pages with inline scripts often pass 1 MB.
                $page = $this->fetcher->download($site, max: 5 * 1024 * 1024);
            } catch (Throwable) {
                continue;
            }
            $final = $this->site((string) $this->fetcher->lastUrl) ?? $site;
            if (! preg_match('/<(?:html|title)\b/i', $page)) { $reason = 'The website did not answer with a web page.'; continue; }
            $name = $this->showsName($company, $page, $final);
            $ats = $this->linksBoard($page, $boards);
            if (! $name && ! $ats) { $reason = 'The website does not show the company name or link to its jobs.'; continue; }
            return $this->passed($company, $final, $page, $facts, ['name_on_site' => $name, 'links_job_board' => $ats]);
        }
        return $this->failed($company, $reason, implode(' ', $sites));
    }

    private function passed(Company $company, string $site, string $page, array $facts, array $checks): bool
    {
        $host = $this->host($site);
        // Another company already has this website: this one is the same company under another name.
        $other = Company::where('domain', $host)->where('id', '!=', $company->id)->whereNull('claimed_by_company_id')->first();
        if ($other && ! $company->opportunities()->exists()) {
            CompanyAlias::remember((string) $company->name, (string) $other->name, $other->id);
            $company->forceFill(['claimed_by_company_id' => $other->id, 'researched_at' => now(), 'research_attempts' => $company->research_attempts + 1])->save();
            $company = $other;
        }
        $text = fn ($value, int $max) => ($value = Str::squish((string) $value)) !== '' ? Str::limit($value, $max, '') : null;
        $url = fn ($value, string $pattern = '~^https://~i') => is_string($value) && preg_match($pattern, $value) && strlen($value) <= 500 ? $value : null;
        $hasLogo = $company->logo_url || $company->logo_path || $company->user?->image;
        $company->forceFill([
            'verified_at' => now(), 'researched_at' => now(), 'research_attempts' => $company->research_attempts + 1,
            'verification' => ['website' => $site, ...$checks, 'by' => $facts ? 'ai' : 'site',
                'sources' => array_slice(array_values(array_filter((array) ($facts['sources'] ?? []), 'is_string')), 0, 5), 'checked_at' => now()->toIso8601String()],
            // Facts fill empty fields only, so nothing an employer typed is replaced.
            'website' => $company->website ?: $site,
            'domain' => $company->domain ?: $host,
            'about' => $company->about ?: $text($facts['about'] ?? null, 1200),
            'location' => $company->location ?: $text($facts['headquarters'] ?? null, 120),
            'country_code' => $company->country_code ?: (preg_match('/^[A-Z]{2}$/', (string) ($facts['country_code'] ?? '')) ? $facts['country_code'] : null),
            'size' => $company->size ?: $text($facts['size'] ?? null, 20),
            'stage' => $company->stage ?: (in_array($facts['stage'] ?? null, ['startup', 'established'], true) ? $facts['stage'] : null),
            'founded_year' => $company->founded_year ?: (($year = (int) ($facts['founded_year'] ?? 0)) >= 1800 && $year <= now()->year ? $year : null),
            'linkedin_url' => $company->linkedin_url ?: $url($facts['linkedin_url'] ?? null, '~^https://(?:[a-z]{2,3}\.)?linkedin\.com/company/[\w.%-]+/?$~i'),
            'careers_url' => $company->careers_url ?: $url($facts['careers_url'] ?? null),
            'logo_url' => $hasLogo ? $company->logo_url : $this->icon($page, $site),
        ])->save();
        return true;
    }

    private function failed(Company $company, string $reason, ?string $site = null): bool
    {
        $company->forceFill(['researched_at' => now(), 'research_attempts' => $company->research_attempts + 1,
            'verification' => ['error' => $reason, 'website' => $site, 'checked_at' => now()->toIso8601String()]])->save();
        return false;
    }

    // The employer ATS boards this company's listings come from.
    private function boards(Company $company): array
    {
        return OpportunitySource::query()
            ->where(fn ($query) => $query->where('company_id', $company->id)->orWhereHas('opportunity', fn ($query) => $query->where('company_id', $company->id)))
            ->latest('id')->limit(20)->get(['application_url', 'source_url', 'normalized_data'])
            ->flatMap(fn ($item) => [$item->application_url, $item->source_url, $item->normalized_data['ats_url'] ?? null])
            ->map(fn ($url) => JobKey::ats($url))->filter()->map(fn ($ats) => $ats['provider'].':'.$ats['slug'])->unique()->values()->all();
    }

    private function prompt(Company $company, array $boards): string
    {
        $titles = $company->opportunities()->latest('id')->limit(3)->pluck('title')
            ->merge(OpportunitySource::where('company_id', $company->id)->latest('id')->limit(3)->get(['normalized_data'])->pluck('normalized_data.title'))
            ->filter()->unique()->take(3)->implode('; ');
        return implode("\n", array_filter([
            'Company: '.$company->name,
            $company->website ? 'Website given by the job listing: '.$company->website : null,
            $company->location || $company->country_code ? 'Place: '.trim($company->location.' '.$company->country_code) : null,
            $boards ? 'Job boards: '.implode(', ', array_map(fn ($board) => JobKey::boardUrl(...explode(':', $board, 2)) ?? $board, $boards)) : null,
            $titles ? 'Some of its job titles: '.$titles : null,
        ]));
    }

    private function site(string $url): ?string
    {
        $url = trim($url);
        if ($url !== '' && ! preg_match('~^https?://~i', $url)) { $url = 'https://'.$url; }
        $host = $this->host($url);
        if (! $host || ! str_contains($host, '.') || strlen($url) > 255) { return null; }
        foreach (self::NOT_OWN_SITE as $blocked) {
            if ($host === $blocked || str_ends_with($host, '.'.$blocked)) { return null; }
        }
        return $url;
    }

    private function host(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);
        return $host ? preg_replace('/^www\./', '', strtolower($host)) : null;
    }

    private function showsName(Company $company, string $page, string $site): bool
    {
        $name = JobKey::name((string) $company->name);
        if ($name === '') { return false; }
        preg_match('~<title[^>]*>(.*?)</title>~is', $page, $title);
        $body = preg_replace('~<(script|style)\b.*?</\1>~is', ' ', $page);
        $text = ' '.JobKey::name(html_entity_decode(strip_tags(($title[1] ?? '').' '.$body), ENT_QUOTES | ENT_HTML5)).' ';
        // "Hugging Face" on huggingface.co counts, and so does "Notion Labs" on notion.so when the page says Notion.
        $label = explode('.', (string) $this->host($site))[0];
        $first = explode(' ', $name)[0];
        return str_contains($text, ' '.$name.' ') || str_contains($text, ' '.JobKey::company((string) $company->name).' ')
            || $label === str_replace(' ', '', $name) || (strlen($first) >= 4 && $label === $first && str_contains($text, ' '.$first.' '));
    }

    private function linksBoard(string $page, array $boards): bool
    {
        if (! $boards || ! preg_match_all('~href\s*=\s*["\']([^"\']+)["\']~i', $page, $links)) { return false; }
        foreach ($links[1] as $link) {
            $ats = JobKey::ats(html_entity_decode($link));
            if ($ats && in_array($ats['provider'].':'.$ats['slug'], $boards, true)) { return true; }
            foreach ($boards as $board) {
                [$provider, $slug] = explode(':', $board, 2);
                $host = JobKey::boardDomains($provider, $slug);
                if (preg_match('~^https?://(?:'.implode('|', array_map('preg_quote', $host)).')/'.preg_quote($slug, '~').'(?:[/?#]|$)~i', html_entity_decode($link))) { return true; }
            }
        }
        return false;
    }

    // The site's touch icon or a PNG favicon; companies:logos stores a copy later.
    private function icon(string $page, string $site): ?string
    {
        preg_match_all('~<link\b[^>]*>~i', $page, $tags);
        $found = [];
        foreach ($tags[0] as $tag) {
            if (! preg_match('~\brel\s*=\s*["\']([^"\']+)["\']~i', $tag, $rel) || ! preg_match('~\bhref\s*=\s*["\']([^"\']+)["\']~i', $tag, $href)) { continue; }
            $rel = strtolower($rel[1]);
            $href = html_entity_decode($href[1]);
            if (str_contains($rel, 'apple-touch-icon')) { $found[0] ??= $href; }
            elseif (preg_match('/\bicon\b/', $rel) && ! preg_match('~\.(?:ico|svg)(?:\?|$)~i', $href)) { $found[1] ??= $href; }
        }
        ksort($found);
        $href = reset($found);
        if (! $href) { return null; }
        $url = SourceHttp::resolveUrl($site, $href);
        return str_starts_with($url, 'https://') && strlen($url) <= 2000 ? $url : null;
    }
}

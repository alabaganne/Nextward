<?php

namespace App\Importing;

use App\Models\ImportSource;
use App\Models\OpportunitySource;
use DOMDocument;
use DOMXPath;
use RuntimeException;

class SourceReader
{
    public function __construct(private SourceHttp $http) {}

    // Job boards whose jobs only show in the owner's job leads, never on the public site (see publication_mode "leads").
    public const BOARDS = ['himalayas', 'remoteok', 'remotive', 'wwr', 'hn'];
    // Providers read from the saved list payload, so a listing gone from the list is gone from the board.
    public const FROM_LIST = ['greenhouse', 'lever', 'ashby', 'workable', 'recruitee', ...self::BOARDS];

    public function discover(ImportSource $source): array
    {
        if ($source->provider === 'himalayas') { return $this->himalayas($source); }
        if ($source->provider === 'hn') { return $this->hackerNews($source); }
        if ($source->provider === 'pages') { return (new LinkedPages($this->http))->discover($source); }
        $body = $this->http->get($source->url);
        if ($source->provider === 'jsonld') {
            return $this->sitemap($source, $body);
        }
        $data = $source->provider === 'wwr' ? $this->rss($body) : json_decode($body, true, 64, JSON_THROW_ON_ERROR);
        $records = match ($source->provider) {
            'greenhouse', 'ashby', 'remotive' => $data['jobs'] ?? null,
            'workable' => isset($data['jobs']) ? array_map(fn ($job) => $job + ['_employer' => $data['name'] ?? null], $data['jobs']) : null,
            'recruitee' => $data['offers'] ?? null,
            // The first Remote OK item is the API terms, not a job.
            'remoteok' => array_is_list($data) ? array_values(array_filter($data, fn ($job) => isset($job['id'], $job['position']))) : null,
            'lever', 'wwr' => array_is_list($data) ? $data : null,
            default => null,
        };
        if (! is_array($records)) {
            throw new RuntimeException('The provider response does not contain a complete job array.');
        }
        $complete = ! in_array($source->provider, self::BOARDS, true);
        if ($source->provider === 'lever') {
            // An unpaginated documented endpoint returns the whole board; reject user-limited URLs.
            parse_str(parse_url($source->url, PHP_URL_QUERY) ?: '', $query);
            $complete = ! isset($query['limit']) && ! isset($query['skip']);
        }
        if ($source->provider === 'greenhouse' && isset($data['meta']['total'])) {
            $complete = count($records) === (int) $data['meta']['total'];
        }
        return $this->items($source, $records, $complete);
    }

    private function items(ImportSource $source, array $records, bool $complete): array
    {
        if (count($records) > config('importer.max_discoveries')) {
            throw new RuntimeException('The board exceeds the configured discovery limit.');
        }
        $items = [];
        foreach ($records as $record) {
            if ($source->provider === 'ashby' && ($record['isListed'] ?? true) === false) { continue; }
            $url = $record['absolute_url'] ?? $record['hostedUrl'] ?? $record['jobUrl'] ?? $record['careers_url'] ?? $record['guid'] ?? $record['link'] ?? $record['url'] ?? null;
            $id = $record['shortcode'] ?? $record['id'] ?? $record['guid'] ?? $record['link'] ?? $record['objectID'] ?? null;
            if (! $url || $id === null) { $complete = false; continue; }
            try {
                $this->http->validate($url);
            } catch (SourceRequestException) {
                $complete = false;
                continue;
            }
            $items[] = ['external_id' => (string) $id, 'source_url' => $url, 'payload' => $record];
        }
        return ['items' => $items, 'complete' => $complete, 'authoritative' => ! in_array($source->provider, self::BOARDS, true)];
    }

    // Himalayas lists the newest jobs first, 20 a page; a few pages per run cover what was posted since the last one.
    private function himalayas(ImportSource $source): array
    {
        $records = []; $cursor = null;
        for ($page = 0; $page < (int) ($source->settings['pages'] ?? 10); $page++) {
            $data = json_decode($this->http->get($source->url.($cursor ? (str_contains($source->url, '?') ? '&' : '?').'cursor='.rawurlencode($cursor) : '')), true, 64, JSON_THROW_ON_ERROR);
            $records = [...$records, ...($data['jobs'] ?? [])];
            $cursor = $data['nextCursor'] ?? null;
            if (! $cursor || ! ($data['jobs'] ?? [])) { break; }
        }
        return $this->items($source, $records, false);
    }

    // The latest "Ask HN: Who is hiring?" thread; each top-level comment is one company.
    private function hackerNews(ImportSource $source): array
    {
        $stories = json_decode($this->http->get($source->url), true, 64, JSON_THROW_ON_ERROR)['hits'] ?? [];
        $story = collect($stories)->first(fn ($hit) => str_starts_with((string) ($hit['title'] ?? ''), 'Ask HN: Who is hiring'));
        if (! $story) { throw new RuntimeException('No "Who is hiring" thread was found.'); }
        $id = (int) $story['objectID'];
        $comments = json_decode($this->http->get('https://hn.algolia.com/api/v1/search_by_date?tags=comment,story_'.$id.'&hitsPerPage=1000'), true, 64, JSON_THROW_ON_ERROR)['hits'] ?? [];
        $records = [];
        foreach ($comments as $comment) {
            if ((int) ($comment['parent_id'] ?? 0) !== $id || empty($comment['comment_text'])) { continue; }
            $first = trim(html_entity_decode(strip_tags(preg_split('~<p>~i', $comment['comment_text'])[0]), ENT_QUOTES | ENT_HTML5));
            $records[] = array_merge($comment, ['title' => $first, 'url' => 'https://news.ycombinator.com/item?id='.$comment['objectID']]);
        }
        return $this->items($source, $records, false);
    }

    private function rss(string $body): array
    {
        if (preg_match('/<!DOCTYPE|<!ENTITY/i', $body)) { throw new RuntimeException('External XML declarations are not accepted.'); }
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadXML($body, LIBXML_NONET);
        libxml_clear_errors(); libxml_use_internal_errors($previous);
        if (! $loaded) { throw new RuntimeException('The feed is not valid RSS.'); }
        $items = [];
        foreach ((new DOMXPath($document))->query('//item') as $node) {
            $item = [];
            foreach ($node->childNodes as $child) {
                if ($child->nodeType === XML_ELEMENT_NODE) { $item[$child->localName] = trim($child->textContent); }
            }
            $items[] = $item;
        }
        return $items;
    }

    private function sitemap(ImportSource $source, string $initial): array
    {
        $pending = [[$source->url, $initial]]; $visited = []; $items = []; $complete = true;
        while ($pending) {
            [$url, $body] = array_shift($pending);
            if (isset($visited[$url])) { continue; }
            $visited[$url] = true;
            if (count($visited) > config('importer.max_sitemaps')) {
                throw new RuntimeException('Sitemap limit reached; snapshot was not completed.');
            }
            try {
                $body ??= $this->http->get($url);
            } catch (SourceBudgetException) {
                // Keep what was found; the rest is read in a later cycle.
                $complete = false;
                break;
            }
            $withoutHtmlDoctype = preg_replace('/<!doctype\s+html\s*>/i', '', $body);
            if (preg_match('/<!DOCTYPE|<!ENTITY/i', $withoutHtmlDoctype)) {
                throw new RuntimeException('External XML declarations are not accepted.');
            }
            $document = new DOMDocument();
            $previous = libxml_use_internal_errors(true);
            $xml = $document->loadXML($body, LIBXML_NONET);
            libxml_clear_errors(); libxml_use_internal_errors($previous);
            $root = $xml ? $document->documentElement?->localName : null;
            if ($root === 'sitemapindex' || $root === 'urlset') {
                $xpath = new DOMXPath($document);
                foreach ($xpath->query('/*/*[local-name()="url" or local-name()="sitemap"]/*[local-name()="loc"]') as $node) {
                    $target = trim($node->textContent);
                    $this->http->validate($target);
                    if ($root === 'sitemapindex') {
                        $pending[] = [$target, null];
                    } else {
                        $prefix = $source->settings['job_path_prefix'] ?? null;
                        if ($prefix && ! str_starts_with(parse_url($target, PHP_URL_PATH) ?: '', $prefix)) { continue; }
                        $items[$target] = ['external_id' => hash('sha256', $target), 'source_url' => $target, 'payload' => null];
                    }
                }
            } elseif ($this->jobPosting($body)) {
                $items[$url] = ['external_id' => hash('sha256', $url), 'source_url' => $url, 'payload' => null];
            } else {
                $prefix = $source->settings['job_path_prefix'] ?? null;
                if (! $prefix) { throw new RuntimeException('A listing page requires an explicit job path prefix.'); }
                $document = $this->html($body);
                foreach ((new DOMXPath($document))->query('//a[@href]') as $link) {
                    $target = SourceHttp::resolveUrl($url, $link->getAttribute('href'));
                    if (! str_starts_with(parse_url($target, PHP_URL_PATH) ?: '', $prefix)) { continue; }
                    $this->http->validate($target);
                    $items[$target] = ['external_id' => hash('sha256', $target), 'source_url' => $target, 'payload' => null];
                }
            }
            if (count($items) > config('importer.max_discoveries')) {
                throw new RuntimeException('Discovery limit reached; source requires a narrower configuration.');
            }
        }
        return ['items' => array_values($items), 'complete' => $complete, 'authoritative' => false];
    }

    public function read(ImportSource $source, OpportunitySource $item): array
    {
        $data = $item->payload ?? [];
        if ($source->provider === 'pages') { return (new LinkedPages($this->http))->read($item); }
        if ($source->provider === 'jsonld') {
            $data = $this->jobPosting($this->http->get($item->source_url));
            if (! $data) { throw new RuntimeException('No valid JobPosting object found; review the source.'); }
            // Kept so the page can be parsed again without reading it.
            $item->payload = $data;
            $identifier = $data['identifier'] ?? null;
            $website = $data['hiringOrganization']['sameAs'] ?? $data['hiringOrganization']['url'] ?? null;
            // Boards often link the employer to their own company page, which is not the employer site.
            if (is_string($website) && in_array(strtolower((string) parse_url($website, PHP_URL_HOST)), $source->allowed_domains ?? [], true)) {
                $website = null;
            }
            return [
                'title' => $data['title'] ?? '', 'description' => $data['description'] ?? '',
                'employer' => $data['hiringOrganization']['name'] ?? null,
                'website' => $website,
                'posted_at' => $data['datePosted'] ?? null, 'expires_at' => $data['validThrough'] ?? null,
                'locations' => $this->list($data['jobLocation'] ?? []),
                'employment_types' => (array) ($data['employmentType'] ?? []),
                'work_type' => preg_grep('/^\s*telecommute\s*$/i', array_filter((array) ($data['jobLocationType'] ?? []), 'is_string')) ? 'remote' : null,
                'applicant_locations' => $this->list($data['applicantLocationRequirements'] ?? []),
                'salary' => $data['baseSalary'] ?? null, 'language' => $data['inLanguage'] ?? null,
                'application_url' => $data['url'] ?? $item->source_url,
                'requisition_id' => is_array($identifier) ? ($identifier['value'] ?? null) : $identifier,
            ];
        }
        if ($source->provider === 'greenhouse') {
            if (! isset($data['content'])) {
                $endpoint = strtok($source->url, '?');
                $data = json_decode($this->http->get(rtrim($endpoint, '/').'/'.rawurlencode($item->external_id)), true, 64, JSON_THROW_ON_ERROR);
            }
            return [
                'title' => $data['title'] ?? '', 'description' => $data['content'] ?? '',
                'employer' => $data['company_name'] ?? $source->settings['employer_name'] ?? $source->company?->display_name,
                'posted_at' => $data['first_published'] ?? null, 'expires_at' => $data['application_deadline'] ?? null,
                'location' => $data['location']['name'] ?? null, 'language' => $data['language'] ?? null,
                'application_url' => $data['absolute_url'] ?? $item->source_url,
                'requisition_id' => $data['requisition_id'] ?? null,
            ];
        }
        if ($source->provider === 'lever') {
            $description = $data['descriptionPlain'] ?? $data['description'] ?? '';
            foreach ($data['lists'] ?? [] as $list) { $description .= "\n".($list['text'] ?? '')."\n".($list['content'] ?? ''); }
            $description .= "\n".($data['additionalPlain'] ?? $data['additional'] ?? '');
            return [
                'title' => $data['text'] ?? '', 'description' => $description,
                'employer' => $source->settings['employer_name'] ?? $source->company?->display_name,
                'posted_at' => isset($data['createdAt']) ? gmdate('c', (int) ($data['createdAt'] / 1000)) : null,
                'location' => $data['categories']['location'] ?? null, 'country_code' => $data['country'] ?? null,
                'work_type' => $data['workplaceType'] ?? null, 'employment_types' => [$data['categories']['commitment'] ?? ''],
                'application_url' => $data['applyUrl'] ?? $item->source_url,
                'salary' => isset($data['salaryRange']) ? ['currency' => $data['salaryRange']['currency'] ?? null, 'value' => [
                    'minValue' => $data['salaryRange']['min'] ?? null, 'maxValue' => $data['salaryRange']['max'] ?? null,
                    'unitText' => $data['salaryRange']['interval'] ?? null,
                ]] : null,
            ];
        }
        if ($source->provider === 'ashby') {
            return [
                'title' => $data['title'] ?? '', 'description' => $data['descriptionPlain'] ?? $data['descriptionHtml'] ?? '',
                'employer' => $source->settings['employer_name'] ?? $source->company?->display_name,
                'posted_at' => $data['publishedAt'] ?? null,
                'location' => $data['location'] ?? null,
                'locations' => ! empty($data['address']) ? [['address' => $data['address']['postalAddress'] ?? $data['address']]] : [],
                // isRemote is also true for hybrid roles, so the workplace type wins when present.
                'work_type' => strtolower($data['workplaceType'] ?? '') ?: (($data['isRemote'] ?? false) ? 'remote' : null),
                'employment_types' => [$data['employmentType'] ?? ''],
                'application_url' => $data['applyUrl'] ?? $item->source_url,
            ];
        }
        if ($source->provider === 'workable') {
            return [
                'title' => $data['title'] ?? '', 'description' => $data['description'] ?? '',
                'employer' => $source->settings['employer_name'] ?? $data['_employer'] ?? $source->company?->display_name,
                'posted_at' => $data['published_on'] ?? $data['created_at'] ?? null,
                'location' => implode(', ', array_filter([$data['city'] ?? null, $data['state'] ?? null, $data['country'] ?? null])) ?: null,
                'locations' => array_map(fn ($place) => ['address' => array_filter(['addressLocality' => $place['city'] ?? null,
                    'addressRegion' => $place['region'] ?? null, 'addressCountry' => $place['countryCode'] ?? $place['country'] ?? null])], $data['locations'] ?? []),
                'work_type' => ($data['telecommuting'] ?? false) ? 'remote' : null,
                'employment_types' => [$data['employment_type'] ?? ''],
                'application_url' => $data['application_url'] ?? $data['url'] ?? $item->source_url,
            ];
        }
        if ($source->provider === 'recruitee') {
            return [
                'title' => $data['title'] ?? '', 'description' => ($data['description'] ?? '')."\n".($data['requirements'] ?? ''),
                'employer' => $data['company_name'] ?? $source->settings['employer_name'] ?? $source->company?->display_name,
                'posted_at' => $data['published_at'] ?? $data['created_at'] ?? null,
                'location' => $data['location'] ?? null, 'country_code' => $data['country_code'] ?? null,
                'work_type' => ! empty($data['remote']) ? 'remote' : (! empty($data['hybrid']) ? 'hybrid' : (! empty($data['on_site']) ? 'onsite' : null)),
                'employment_types' => [$data['employment_type_code'] ?? ''],
                'application_url' => $data['careers_apply_url'] ?? $data['careers_url'] ?? $item->source_url,
            ];
        }
        // Remote boards: every job is remote. Their "worldwide" or "all countries" label is a default, not the employer's
        // word, so only a real place limit is kept; who can apply comes from the employer's text.
        $limit = fn ($place) => is_string($place) && trim($place) !== '' && ! preg_match('/\b(?:worldwide|anywhere|global|all countries|any country)\b/i', $place) ? trim($place) : null;
        if ($source->provider === 'himalayas') {
            $places = array_values(array_filter((array) ($data['locationRestrictions'] ?? []), 'is_string'));
            return [
                'title' => $data['title'] ?? '', 'description' => $data['description'] ?? '', 'employer' => $data['companyName'] ?? null,
                'posted_at' => isset($data['pubDate']) ? gmdate('c', (int) $data['pubDate']) : null,
                'expires_at' => isset($data['expiryDate']) ? gmdate('c', (int) $data['expiryDate']) : null,
                'location' => $places ? implode(', ', $places) : null, 'applicant_locations' => $places,
                'work_type' => 'remote', 'employment_types' => [$data['employmentType'] ?? ''],
                'application_url' => $data['applicationLink'] ?? $item->source_url,
            ];
        }
        if ($source->provider === 'remoteok') {
            return [
                'title' => $data['position'] ?? '', 'description' => $data['description'] ?? '', 'employer' => $data['company'] ?? null,
                'posted_at' => $data['date'] ?? null, 'location' => $limit($data['location'] ?? null), 'work_type' => 'remote',
                'application_url' => $data['apply_url'] ?? $data['url'] ?? $item->source_url,
                'salary' => ! empty($data['salary_max']) ? ['currency' => 'USD', 'value' => ['minValue' => $data['salary_min'] ?? null, 'maxValue' => $data['salary_max'], 'unitText' => 'YEAR']] : null,
            ];
        }
        if ($source->provider === 'remotive') {
            return [
                'title' => $data['title'] ?? '', 'description' => $data['description'] ?? '', 'employer' => $data['company_name'] ?? null,
                'posted_at' => isset($data['publication_date']) ? $data['publication_date'].'Z' : null,
                'location' => $limit($data['candidate_required_location'] ?? null), 'work_type' => 'remote',
                'employment_types' => [str_replace('_', ' ', (string) ($data['job_type'] ?? ''))],
                'application_url' => $data['url'] ?? $item->source_url,
            ];
        }
        if ($source->provider === 'wwr') {
            [$employer, $title] = array_pad(explode(': ', (string) ($data['title'] ?? ''), 2), 2, '');
            return [
                'title' => $title, 'description' => $data['description'] ?? '', 'employer' => $employer,
                'posted_at' => $data['pubDate'] ?? null, 'expires_at' => $data['expires_at'] ?? null,
                'location' => $limit($data['region'] ?? null), 'work_type' => 'remote', 'employment_types' => [$data['type'] ?? ''],
                'application_url' => $data['link'] ?? $item->source_url,
            ];
        }
        if ($source->provider === 'hn') {
            // "Acme | Senior Frontend Engineer | Remote (EU) | Full-time | https://acme.com/jobs"
            $parts = array_values(array_filter(array_map('trim', explode('|', (string) ($data['title'] ?? '')))));
            $role = collect($parts)->slice(1)->first(fn ($part) => preg_match('/engineer|developer|programmer|designer|scientist|architect|devops|sre|lead|cto|full.?stack|front.?end|back.?end/i', $part)) ?? ($parts[1] ?? '');
            $places = array_filter($parts, fn ($part) => preg_match('/remote|on.?site|hybrid|in.office/i', $part));
            $text = (string) ($data['comment_text'] ?? '');
            preg_match_all('~https?://[^\s"<>]+~', html_entity_decode($text), $links);
            $links = array_map(fn ($link) => rtrim($link, '.,);'), $links[0]);
            $apply = collect($links)->first(fn ($link) => JobKey::ats($link)) ?? $links[0] ?? $item->source_url;
            return [
                'title' => $role, 'description' => $text, 'employer' => preg_replace('~\s*\(?https?://\S+\)?~', '', $parts[0] ?? ''),
                'posted_at' => $data['created_at'] ?? null, 'location' => $limit(implode(', ', $places)),
                'work_type' => preg_grep('/remote/i', $places) ? 'remote' : (preg_grep('/hybrid/i', $places) ? 'hybrid' : ($places ? 'onsite' : null)),
                'application_url' => $apply,
            ];
        }
        throw new RuntimeException('Unsupported provider.');
    }

    // Reads a board listing from its saved list payload, or returns null when that would need a request.
    public function saved(ImportSource $source, OpportunitySource $item): ?array
    {
        $saved = $item->payload && match ($source->provider) {
            'greenhouse' => isset($item->payload['content']), 'jsonld' => false, default => in_array($source->provider, self::FROM_LIST, true),
        };
        return $saved ? $this->read($source, $item) : null;
    }

    public function jobPosting(string $html): ?array
    {
        foreach ((new DOMXPath($this->html($html)))->query('//script[@type="application/ld+json"]') as $script) {
            $decoded = json_decode($script->textContent, true, 64);
            if (is_array($decoded) && ($found = $this->findJob($decoded))) { return $found; }
        }
        return null;
    }

    private function findJob(array $node, int $depth = 0): ?array
    {
        if ($depth > 20) { return null; }
        if (in_array('JobPosting', (array) ($node['@type'] ?? []), true)) { return $node; }
        foreach ($node as $child) {
            if (is_array($child) && ($found = $this->findJob($child, $depth + 1))) { return $found; }
        }
        return null;
    }

    private function html(string $html): DOMDocument
    {
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors(); libxml_use_internal_errors($previous);
        return $document;
    }

    private function list($value): array
    {
        return ! is_array($value) || ! $value ? [] : (array_is_list($value) ? $value : [$value]);
    }
}

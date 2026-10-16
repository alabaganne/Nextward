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

    public function discover(ImportSource $source): array
    {
        $body = $this->http->get($source->url);
        if ($source->provider === 'jsonld') {
            return $this->sitemap($source, $body);
        }
        $data = json_decode($body, true, 64, JSON_THROW_ON_ERROR);
        $records = match ($source->provider) {
            'greenhouse', 'ashby' => $data['jobs'] ?? null,
            'lever' => array_is_list($data) ? $data : null,
            default => null,
        };
        if (! is_array($records)) {
            throw new RuntimeException('The provider response does not contain a complete job array.');
        }
        $complete = true;
        if ($source->provider === 'lever') {
            // An unpaginated documented endpoint returns the whole board; reject user-limited URLs.
            parse_str(parse_url($source->url, PHP_URL_QUERY) ?: '', $query);
            $complete = ! isset($query['limit']) && ! isset($query['skip']);
        }
        if ($source->provider === 'greenhouse' && isset($data['meta']['total'])) {
            $complete = count($records) === (int) $data['meta']['total'];
        }
        if (count($records) > config('importer.max_discoveries')) {
            throw new RuntimeException('The board exceeds the configured discovery limit.');
        }
        $items = [];
        foreach ($records as $record) {
            if ($source->provider === 'ashby' && ($record['isListed'] ?? true) === false) { continue; }
            $url = $record['absolute_url'] ?? $record['hostedUrl'] ?? $record['jobUrl'] ?? null;
            $id = $record['id'] ?? null;
            if (! $url || $id === null) { $complete = false; continue; }
            try {
                $this->http->validate($url);
            } catch (SourceRequestException) {
                $complete = false;
                continue;
            }
            $items[] = ['external_id' => (string) $id, 'source_url' => $url, 'payload' => $record];
        }
        return ['items' => $items, 'complete' => $complete, 'authoritative' => true];
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
        if ($source->provider === 'jsonld') {
            $data = $this->jobPosting($this->http->get($item->source_url));
            if (! $data) { throw new RuntimeException('No valid JobPosting object found; review the source.'); }
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
        throw new RuntimeException('Unsupported provider.');
    }

    // Reads a board listing from its saved list payload, or returns null when that would need a request.
    public function saved(ImportSource $source, OpportunitySource $item): ?array
    {
        $saved = $item->payload && match ($source->provider) {
            'greenhouse' => isset($item->payload['content']), 'lever', 'ashby' => true, default => false,
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

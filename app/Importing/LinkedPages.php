<?php

namespace App\Importing;

use App\Ai\JobPageCheck;
use App\Models\ImportSource;
use App\Models\OpportunitySource;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

// Job pages linked from job board posts, such as the Hacker News "Who is hiring" thread (provider "pages"). Each link
// in a post is opened and the AI says whether it is a real job, following a link or two from a home or careers page.
// The listing is made from the employer's page only; the post itself is never published.
class LinkedPages
{
    // Paths that look like a job or careers page.
    private const JOB_PATH = '~/(?:jobs?|careers?|apply|positions?|openings?|roles?|hiring|join|vacanc|work-with-us|o/|j/)~i';

    public function __construct(private SourceHttp $http) {}

    public function discover(ImportSource $source): array
    {
        if (! app(JobPageCheck::class)->enabled()) { throw new RuntimeException('Linked job pages need the AI; turn it on first.'); }
        $items = [];
        foreach (ImportSource::whereIn('provider', config('importer.linked_pages.boards'))->whereNotNull('last_discovered_at')->get() as $board) {
            // Only posts still on the board: for Hacker News, this month's thread.
            foreach ($board->items()->where('last_discovered_at', '>=', $board->last_discovered_at)->get() as $post) {
                foreach ($this->links($post) as $url) {
                    $items[JobKey::url($url) ?? $url] ??= ['external_id' => hash('sha256', $url), 'source_url' => $url, 'payload' => ['post' => [
                        'item_id' => $post->id, 'url' => $post->source_url, 'created_at' => $post->payload['created_at'] ?? null,
                        'text' => (new OpportunityNormalizer())->text((string) ($post->payload['comment_text'] ?? '')),
                    ]]];
                }
            }
        }
        return ['items' => array_values($items), 'complete' => false, 'authoritative' => false];
    }

    public function read(OpportunitySource $item): array
    {
        // A page checked before is only opened again to see that it is still online.
        if ($saved = $item->payload['page'] ?? null) {
            try {
                $this->http->get($saved['page_check']['page_url']);
            } catch (SourceRequestException $e) {
                if ($e instanceof SourceBudgetException || in_array($e->status, [404, 410], true)) { throw $e; }
            }
            return $saved;
        }
        $check = app(JobPageCheck::class);
        if (! $check->enabled()) { throw new ListingRejected('The AI is off, so the page is read later.', later: true); }
        $post = (string) ($item->payload['post']['text'] ?? '');
        $url = $item->source_url; $seen = [];
        for ($hop = 0; ; $hop++) {
            try {
                $page = $this->page($this->http->get($url), $this->http->url ?? $url);
            } catch (SourceBudgetException $e) {
                throw $e;
            } catch (SourceRequestException $e) {
                throw new ListingRejected('The linked page could not be read: '.$e->getMessage());
            }
            try {
                $answer = $check->check($post, $page);
            } catch (Throwable $e) {
                Log::warning('Job page check failed.', ['url' => $page['url'], 'error' => Str::limit($e->getMessage(), 300)]);
                throw new ListingRejected('The AI check failed, so the page is read later.', later: true);
            }
            $seen[] = $page['url'];
            $next = $page['links'][$answer['next_link'] - 1][0] ?? null;
            if ($answer['verdict'] !== 'more' || ! $next || in_array($next, $seen, true) || $hop >= config('importer.linked_pages.hops')) { break; }
            $url = $next;
        }
        $reason = trim($answer['reason']) ?: 'no reason given.';
        if ($answer['verdict'] === 'not_legit') { throw new ListingRejected('Not a real job: '.$reason); }
        if ($answer['verdict'] !== 'job') { throw new ListingRejected('No job on the linked page: '.$reason); }
        $posting = $page['posting'] ?? [];
        $data = [
            'title' => $answer['title'] ?: (string) ($posting['title'] ?? ''),
            'employer' => $answer['employer'] ?: (string) ($posting['hiringOrganization']['name'] ?? ''),
            // The listing shows the employer's own words: the page's JobPosting, or else the page's main text.
            'description' => is_string($posting['description'] ?? null) && mb_strlen(strip_tags($posting['description'])) >= 200 ? $posting['description'] : $page['html'],
            'website' => $answer['employer_site'] ? parse_url($page['url'], PHP_URL_SCHEME).'://'.parse_url($page['url'], PHP_URL_HOST) : null,
            'posted_at' => $posting['datePosted'] ?? $item->payload['post']['created_at'] ?? null,
            'expires_at' => $posting['validThrough'] ?? null,
            'location' => $answer['location'] ?: null,
            'work_type' => $answer['work_type'] === 'unknown' ? null : $answer['work_type'],
            'employment_types' => $answer['employment_types'],
            'language' => is_string($posting['inLanguage'] ?? null) ? $posting['inLanguage'] : null,
            'application_url' => $page['url'],
            'page_check' => ['reason' => $reason, 'post_url' => $item->payload['post']['url'] ?? null, 'page_url' => $page['url'], 'model' => $answer['model'] ?? null],
        ];
        if ($data['title'] === '' || $data['employer'] === '') { throw new ListingRejected('The AI could not tell the role or the employer: '.$reason); }
        if (mb_strlen((new OpportunityNormalizer())->text($data['description'])) < 200) { throw new ListingRejected('The job page has too little text to show: '.$reason); }
        $item->payload = [...$item->payload, 'page' => $data];
        return $data;
    }

    // The links in a post that may lead to a job: job and careers pages when the post has some, else its first link.
    private function links(OpportunitySource $post): array
    {
        preg_match_all('~href="([^"]+)"~i', (string) ($post->payload['comment_text'] ?? ''), $found);
        $links = [];
        foreach ($found[1] as $href) {
            $url = html_entity_decode($href, ENT_QUOTES | ENT_HTML5);
            try {
                $this->http->validate($url);
            } catch (SourceRequestException) {
                continue;
            }
            $links[JobKey::url($url) ?? $url] ??= $url;
        }
        $jobs = array_filter($links, fn ($url) => JobKey::ats($url) || preg_match(self::JOB_PATH, (string) parse_url($url, PHP_URL_PATH)));
        return array_slice(array_values($jobs ?: array_slice($links, 0, 1)), 0, (int) config('importer.linked_pages.links_per_post'));
    }

    // The page's address, title, main text (and its HTML), JobPosting data and links, job-looking links first.
    private function page(string $html, string $url): array
    {
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors(); libxml_use_internal_errors($previous);
        $xpath = new DOMXPath($document);
        $posting = (new SourceReader($this->http))->jobPosting($html);
        $links = [];
        foreach ($xpath->query('//a[@href]') as $anchor) {
            $target = strtok(SourceHttp::resolveUrl($url, trim($anchor->getAttribute('href'))), '#');
            if ($target === $url || isset($links[$target])) { continue; }
            try {
                $this->http->validate($target);
            } catch (SourceRequestException) {
                continue;
            }
            $links[$target] = [$target, Str::squish($anchor->textContent) ?: $target];
        }
        usort($links, fn ($a, $b) => $this->jobLink($b) <=> $this->jobLink($a));
        foreach ($xpath->query('//script|//style|//noscript|//svg|//template|//iframe|//form|//nav|//header[not(ancestor::main or ancestor::article)]|//footer[not(ancestor::main or ancestor::article)]') as $node) {
            $node->parentNode?->removeChild($node);
        }
        $normalizer = new OpportunityNormalizer();
        $main = null;
        foreach ($xpath->query('//main|//article|//*[@role="main"]') as $node) {
            if (mb_strlen(trim($node->textContent)) >= 300) { $main = $node; break; }
        }
        $main ??= $xpath->query('//body')->item(0);
        $mainHtml = $main ? $document->saveHTML($main) : '';
        $text = $normalizer->text($mainHtml);
        // Pages built in the browser often carry their job only as JobPosting data.
        if ($posting && mb_strlen($text) < 300) { $text = $normalizer->text(((string) ($posting['title'] ?? ''))."\n".((string) ($posting['description'] ?? ''))); }
        return [
            'url' => $url, 'title' => Str::squish($xpath->query('//title')->item(0)?->textContent ?? ''),
            'html' => $mainHtml, 'text' => $text, 'posting' => $posting,
            'links' => array_slice($links, 0, 60),
        ];
    }

    private function jobLink(array $link): int
    {
        return (JobKey::ats($link[0]) || preg_match(self::JOB_PATH, (string) parse_url($link[0], PHP_URL_PATH)) ? 2 : 0)
            + (preg_match('/\b(?:jobs?|careers?|apply|open (?:roles|positions)|join us|hiring)\b/i', $link[1]) ? 1 : 0);
    }
}

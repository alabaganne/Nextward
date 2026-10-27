<?php

namespace App\Ai;

use App\Ai\Agents\JobPageChecker;
use Illuminate\Support\Str;

// Asks the AI whether a page linked from a job board post is a real job (see App\Importing\LinkedPages).
class JobPageCheck
{
    public function __construct(private OpportunityClassifier $classifier) {}

    public function enabled(): bool
    {
        return $this->classifier->enabled();
    }

    // $page holds url, title, text and links (a list of [url, text]); the answer's next_link counts from 1.
    public function check(string $post, array $page): array
    {
        $links = implode("\n", array_map(fn ($link, $i) => ($i + 1).'. '.Str::limit($link[1], 80).' → '.$link[0], $page['links'], array_keys($page['links'])));
        $prompt = implode("\n", [
            'The post:', Str::limit($post, 4000), '',
            'The page: '.$page['url'], 'Page title: '.($page['title'] ?: 'none'), '',
            'Page text:', Str::limit($page['text'], (int) config('importer.linked_pages.page_chars')) ?: '(no text)', '',
            'Page links:', $links ?: '(none)',
        ]);
        $response = (new JobPageChecker())->prompt($prompt, provider: $this->classifier->provider(), model: $this->classifier->model('review'));
        return [...$response->toArray(), 'model' => $response->meta->model ?? $this->classifier->model('review')];
    }
}

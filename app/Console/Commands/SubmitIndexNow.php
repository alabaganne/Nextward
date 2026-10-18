<?php

namespace App\Console\Commands;

use App\Indexing\IndexNow;
use App\Models\Internship;
use App\Support\Blog;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SubmitIndexNow extends Command
{
    protected $signature = 'seo:indexnow {--all : Submit every public page, not only recent changes}';
    protected $description = 'Send new and changed public URLs to IndexNow (Bing and others)';

    public function handle(IndexNow $indexNow): int
    {
        if (!$indexNow->enabled()) {
            $this->info('IndexNow is off. Set INDEXNOW_KEY to turn it on.');
            return self::SUCCESS;
        }
        $startedAt = now();
        $since = $this->option('all') ? null : Carbon::parse(Cache::get('seo:indexnow:last_run', now()->subDay()->toIso8601String()));
        $base = rtrim(config('app.url'), '/');

        $urls = [];
        $opportunities = $since
            ? Internship::published()->where(fn ($query) => $query->where('updated_at', '>=', $since)->orWhereHas('translations', fn ($query) => $query->where('updated_at', '>=', $since)))
            : Internship::available();
        $opportunities->select('id', 'slug', 'title', 'language')->with('translations:id,internship_id,locale,slug')->orderBy('id')->limit(10000)->get()->each(function ($row) use (&$urls) {
            foreach ($row->availableLocales() as $locale) {
                $urls[] = $row->publicUrl($locale);
            }
        });
        // Posts only carry a date, so remember which version of each one was sent.
        $sentPosts = $since ? Cache::get('seo:indexnow:posts', []) : [];
        foreach (Blog::LOCALES as $locale) {
            foreach (Blog::all($locale) as $post) {
                if ($post['draft'] || ($sentPosts[Blog::url($post)] ?? null) === $post['updated_at']) continue;
                $urls[] = Blog::url($post);
                $sentPosts[Blog::url($post)] = $post['updated_at'];
            }
        }
        if (!$since) {
            foreach (['/', '/opportunities', '/about', '/contact', '/blog'] as $path) {
                $urls[] = $base.$path;
                $urls[] = $base.($path === '/' ? '/fr' : '/fr'.$path);
            }
        }

        $count = $indexNow->submit($urls);
        Cache::forever('seo:indexnow:last_run', $startedAt->toIso8601String());
        Cache::forever('seo:indexnow:posts', $sentPosts);
        $this->info("Sent {$count} URLs to IndexNow.");
        return self::SUCCESS;
    }
}

<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\Extension\FrontMatter\FrontMatterExtension;
use League\CommonMark\Extension\FrontMatter\Output\RenderedContentWithFrontMatter;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\MarkdownConverter;

class Blog
{
    public const LOCALES = ['en', 'fr'];

    public static function all(string $locale = 'en'): array
    {
        $showDrafts = app()->environment('local');
        return collect(self::posts()[$locale] ?? [])
            ->filter(fn ($post) => ($showDrafts || !$post['draft']) && $post['published_at'] <= now()->toDateString())
            ->sortByDesc('published_at')->values()->all();
    }

    public static function find(string $locale, string $slug): ?array
    {
        return collect(self::all($locale))->firstWhere('slug', $slug);
    }

    public static function translation(array $post): ?array
    {
        if (!$post['translation']) return null;
        return collect(self::all($post['locale'] === 'fr' ? 'en' : 'fr'))->firstWhere('translation', $post['translation']);
    }

    public static function related(array $post, int $limit = 3): array
    {
        return collect(self::all($post['locale']))->where('slug', '!=', $post['slug'])
            ->sortByDesc(fn ($item) => (int) ($item['category'] === $post['category']))
            ->take($limit)->map(fn ($item) => self::summary($item))->values()->all();
    }

    public static function summary(array $post): array
    {
        return Arr::only($post, ['locale', 'slug', 'title', 'excerpt', 'category', 'audience', 'published_at', 'updated_at', 'read_time', 'cover', 'cover_alt', 'draft', 'path']);
    }

    public static function path(array $post): string
    {
        return ($post['locale'] === 'fr' ? '/fr' : '').'/blog/'.$post['slug'];
    }

    public static function url(array $post): string
    {
        return rtrim(config('app.url'), '/').self::path($post);
    }

    public static function posts(): array
    {
        $files = [];
        foreach (self::LOCALES as $locale) {
            $files = array_merge($files, glob(self::directory().'/'.$locale.'/*.md') ?: []);
        }
        $version = md5(implode('|', array_map(fn ($file) => $file.':'.filemtime($file), $files)));
        return Cache::rememberForever('blog.posts.'.$version, fn () => self::load($files));
    }

    public static function directory(): string
    {
        return resource_path('content/blog');
    }

    private static function load(array $files): array
    {
        $converter = self::converter();
        $posts = array_fill_keys(self::LOCALES, []);
        foreach ($files as $file) {
            $locale = basename(dirname($file));
            $source = file_get_contents($file);
            $result = $converter->convert($source);
            $meta = $result instanceof RenderedContentWithFrontMatter ? (array) $result->getFrontMatter() : [];
            $html = (string) $result->getContent();
            $body = preg_replace('/\A---\R.*?\R---\R/s', '', $source);
            preg_match_all('/<h2 id="([^"]+)">(.*?)<\/h2>/s', $html, $headings, PREG_SET_ORDER);
            $post = [
                'locale' => $locale,
                'slug' => basename($file, '.md'),
                'translation' => $meta['translation'] ?? null,
                'title' => $meta['title'] ?? basename($file, '.md'),
                'description' => $meta['description'] ?? '',
                'excerpt' => $meta['excerpt'] ?? ($meta['description'] ?? ''),
                'category' => $meta['category'] ?? null,
                'audience' => $meta['audience'] ?? 'all',
                'published_at' => self::date($meta['published_at'] ?? null) ?? date('Y-m-d', filemtime($file)),
                'updated_at' => self::date($meta['updated_at'] ?? null),
                'cover' => $meta['cover'] ?? null,
                'cover_alt' => $meta['cover_alt'] ?? '',
                'draft' => (bool) ($meta['draft'] ?? false),
                'faq' => array_values(array_filter((array) ($meta['faq'] ?? []), fn ($item) => !empty($item['q']) && !empty($item['a']))),
                'links' => array_values((array) ($meta['links'] ?? [])),
                'read_time' => max(1, (int) round(count(preg_split('/\s+/u', trim(strip_tags($html)))) / 220)),
                'toc' => array_map(fn ($heading) => ['id' => $heading[1], 'text' => html_entity_decode(strip_tags($heading[2]))], $headings),
                'html' => $html,
                'markdown' => trim($body),
            ];
            $post['updated_at'] ??= $post['published_at'];
            $post['path'] = self::path($post);
            $posts[$locale][] = $post;
        }
        return $posts;
    }

    private static function date(mixed $value): ?string
    {
        if ($value === null || $value === '') return null;
        return is_int($value) ? Carbon::createFromTimestamp($value)->toDateString() : Carbon::parse($value)->toDateString();
    }

    private static function converter(): MarkdownConverter
    {
        $environment = new Environment([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'heading_permalink' => ['insert' => 'none', 'apply_id_to_heading' => true, 'id_prefix' => '', 'min_heading_level' => 2, 'max_heading_level' => 3],
            'external_link' => ['internal_hosts' => [parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost'], 'open_in_new_window' => true, 'noopener' => 'external', 'noreferrer' => 'external'],
        ]);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new GithubFlavoredMarkdownExtension());
        $environment->addExtension(new FrontMatterExtension());
        $environment->addExtension(new HeadingPermalinkExtension());
        $environment->addExtension(new ExternalLinkExtension());
        return new MarkdownConverter($environment);
    }
}

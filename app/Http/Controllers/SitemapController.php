<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Opportunity;
use App\Support\Blog;

class SitemapController extends Controller
{
    private const CHUNK_SIZE = 10000;

    public function index()
    {
        $pages = (int) ceil(Opportunity::available()->count() / self::CHUNK_SIZE);
        $xml = '<?xml version="1.0" encoding="UTF-8"?><sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        $xml .= '<sitemap><loc>'.e(rtrim(config('app.url'), '/').'/sitemaps/pages.xml').'</loc></sitemap>';
        if (Company::publicIndexable()->exists()) {
            $xml .= '<sitemap><loc>'.e(rtrim(config('app.url'), '/').'/sitemaps/companies.xml').'</loc></sitemap>';
        }
        for ($page = 1; $page <= $pages; $page++) {
            $xml .= '<sitemap><loc>'.e(rtrim(config('app.url'), '/').'/sitemaps/opportunities-'.$page.'.xml').'</loc></sitemap>';
        }
        return response($xml.'</sitemapindex>')->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function pages()
    {
        $base = rtrim(config('app.url'), '/');
        $paths = ['/', '/opportunities', '/companies', '/about', '/contact', '/privacy', '/terms', '/blog'];
        $paths = [...$paths, ...array_map(fn ($path) => $path === '/' ? '/fr' : '/fr'.$path, $paths)];
        foreach (['internships', 'jobs'] as $category) {
            $query = Opportunity::available()->searchFilters(['category' => $category]);
            if ($query->exists()) {
                $paths[] = '/opportunities/'.$category;
                $paths[] = '/fr/opportunities/'.$category;
            }
        }
        $rows = collect($paths)->map(fn ($path) => ['url' => $base.$path]);
        foreach (Blog::LOCALES as $locale) {
            foreach (Blog::all($locale) as $post) {
                if (!$post['draft']) $rows->push(['url' => Blog::url($post), 'modified' => $post['updated_at']]);
            }
        }
        return $this->urlset($rows);
    }

    public function companies()
    {
        $rows = Company::publicIndexable()->get();
        abort_if($rows->isEmpty(), 404);
        return $this->urlset($rows->flatMap(fn ($company) => array_map(fn ($locale) => ['url' => $company->publicUrl($locale), 'modified' => $company->updated_at?->toIso8601String()], ['en', 'fr'])));
    }

    public function opportunities(int $page)
    {
        abort_if($page < 1, 404);
        $rows = Opportunity::available()->select('id', 'slug', 'title', 'language', 'content_updated_at')
            ->with('translations:id,opportunity_id,locale,slug,updated_at')
            ->orderBy('id')->skip(($page - 1) * self::CHUNK_SIZE)->take(self::CHUNK_SIZE)->get();
        abort_if($rows->isEmpty(), 404);
        // Only languages the listing really has, each pointing at the other with hreflang.
        return $this->urlset($rows->flatMap(function ($row) {
            $locales = $row->availableLocales();
            $alternates = count($locales) > 1 ? array_map(fn ($locale) => ['lang' => $locale, 'url' => $row->publicUrl($locale)], $locales) : [];
            return array_map(fn ($locale) => [
                'url' => $row->publicUrl($locale),
                'modified' => collect([$row->content_updated_at, $row->translation($locale)?->updated_at])->filter()->max()?->toIso8601String(),
                'alternates' => $alternates,
            ], $locales);
        }));
    }

    private function urlset($rows)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">';
        foreach ($rows as $row) {
            $xml .= '<url><loc>'.e($row['url']).'</loc>'.(!empty($row['modified']) ? '<lastmod>'.e($row['modified']).'</lastmod>' : '');
            foreach ($row['alternates'] ?? [] as $alternate) {
                $xml .= '<xhtml:link rel="alternate" hreflang="'.e($alternate['lang']).'" href="'.e($alternate['url']).'"/>';
            }
            $xml .= '</url>';
        }
        return response($xml.'</urlset>')->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}

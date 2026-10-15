<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Internship;
use App\Support\Blog;

class SitemapController extends Controller
{
    private const CHUNK_SIZE = 10000;

    public function index()
    {
        $pages = (int) ceil(Internship::available()->count() / self::CHUNK_SIZE);
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
        $paths = ['/', '/for-students', '/for-professionals', '/opportunities', '/about', '/contact', '/blog'];
        $paths = [...$paths, ...array_map(fn ($path) => $path === '/' ? '/fr' : '/fr'.$path, $paths)];
        foreach (['internships', 'jobs', 'tunisia', 'remote-from-tunisia', 'relocation'] as $category) {
            $query = Internship::available()->searchFilters(['category' => $category]);
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
        return $this->urlset($rows->map(fn ($company) => ['url' => $company->public_url, 'modified' => $company->updated_at?->toIso8601String()]));
    }

    public function opportunities(int $page)
    {
        abort_if($page < 1, 404);
        $rows = Internship::available()->select('id', 'slug', 'title', 'content_updated_at')->orderBy('id')->skip(($page - 1) * self::CHUNK_SIZE)->take(self::CHUNK_SIZE)->get();
        abort_if($rows->isEmpty(), 404);
        return $this->urlset($rows->map(fn ($row) => ['url' => $row->public_url, 'modified' => $row->content_updated_at?->toIso8601String()]));
    }

    private function urlset($rows)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($rows as $row) {
            $xml .= '<url><loc>'.e($row['url']).'</loc>'.(!empty($row['modified']) ? '<lastmod>'.e($row['modified']).'</lastmod>' : '').'</url>';
        }
        return response($xml.'</urlset>')->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}

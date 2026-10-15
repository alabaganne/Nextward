<?php

namespace App\Http\Controllers;

use App\Support\Blog;
use App\Support\PublicSeo;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Inertia;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $locale = $this->locale($request);
        return Inertia::render('Blog/Index', [
            'posts' => array_map([Blog::class, 'summary'], Blog::all($locale)),
        ]);
    }

    public function show(Request $request, string $slug)
    {
        $post = Blog::find($this->locale($request), $slug);
        abort_unless($post, 404);
        $translation = Blog::translation($post);
        return Inertia::render('Blog/Show', [
            'post' => Arr::except($post, ['markdown']),
            'translation' => $translation ? $translation['path'] : null,
            'related' => Blog::related($post),
            'seo' => PublicSeo::post($post, $translation),
        ]);
    }

    public function markdown(Request $request, string $slug)
    {
        $post = Blog::find($this->locale($request), $slug);
        abort_unless($post, 404);
        $faq = collect($post['faq'])->map(fn ($item) => "### {$item['q']}\n\n{$item['a']}")->implode("\n\n");
        $text = "# {$post['title']}\n\n> {$post['description']}\n\n"
            .'Published '.$post['published_at'].', updated '.$post['updated_at'].' · '.Blog::url($post)."\n\n"
            .$post['markdown']."\n".($faq ? "\n## FAQ\n\n".$faq."\n" : '');
        return response($text)->header('Content-Type', 'text/markdown; charset=UTF-8')
            ->header('Link', '<'.Blog::url($post).'>; rel="canonical"')->header('X-Robots-Tag', 'noindex');
    }

    public function feed(Request $request)
    {
        $locale = $this->locale($request);
        $base = rtrim(config('app.url'), '/');
        $home = $base.($locale === 'fr' ? '/fr/blog' : '/blog');
        $posts = collect(Blog::all($locale))->reject(fn ($post) => $post['draft'])->take(30);
        $xml = '<?xml version="1.0" encoding="UTF-8"?><rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom"><channel>'
            .'<title>'.e(PublicSeo::copy($locale)['blog']['title']).'</title>'
            .'<link>'.e($home).'</link>'
            .'<description>'.e(PublicSeo::copy($locale)['blog']['description']).'</description>'
            .'<language>'.$locale.'</language>'
            .'<atom:link href="'.e($home.'/feed.xml').'" rel="self" type="application/rss+xml" />';
        foreach ($posts as $post) {
            $xml .= '<item><title>'.e($post['title']).'</title><link>'.e(Blog::url($post)).'</link><guid isPermaLink="true">'.e(Blog::url($post)).'</guid>'
                .'<pubDate>'.e(\Carbon\Carbon::parse($post['published_at'])->toRssString()).'</pubDate>'
                .($post['category'] ? '<category>'.e($post['category']).'</category>' : '')
                .'<description>'.e($post['description']).'</description></item>';
        }
        return response($xml.'</channel></rss>')->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }

    private function locale(Request $request): string
    {
        return $request->is('fr', 'fr/*') ? 'fr' : 'en';
    }
}

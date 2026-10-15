<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BlogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        config(['inertia.pages.paths' => [resource_path('js/Pages')]]);
    }

    public function test_blog_index_lists_posts_in_each_language()
    {
        $this->get('/blog')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Blog/Index')
            ->where('posts', fn ($posts) => collect($posts)->contains('slug', 'how-to-find-an-internship-as-a-student'))
            ->where('seo.alternates.1.url', url('/fr/blog')));
        $this->get('/fr/blog')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Blog/Index')
            ->where('locale', 'fr')
            ->where('posts', fn ($posts) => collect($posts)->contains('slug', 'trouver-un-stage-etudiant')));
    }

    public function test_post_page_has_article_schema_and_language_pair()
    {
        $this->get('/blog/how-to-find-an-internship-as-a-student')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Blog/Show')
            ->where('post.title', 'How to find an internship as a student')
            ->where('post.html', fn ($html) => str_contains($html, '<h2 id='))
            ->where('translation', '/fr/blog/trouver-un-stage-etudiant')
            ->where('seo.canonical', url('/blog/how-to-find-an-internship-as-a-student'))
            ->where('seo.robots', 'index, follow, max-image-preview:large')
            ->where('seo.alternates', fn ($alternates) => collect($alternates)->pluck('url', 'lang')->all() === [
                'en' => url('/blog/how-to-find-an-internship-as-a-student'),
                'fr' => url('/fr/blog/trouver-un-stage-etudiant'),
                'x-default' => url('/blog/how-to-find-an-internship-as-a-student'),
            ])
            ->where('seo.structured_data.0.@type', 'BlogPosting')
            ->where('seo.structured_data.0.dateModified', fn ($date) => !empty($date))
            ->where('seo.structured_data.0.image.0', fn ($image) => str_starts_with($image, 'http')));

        $this->get('/fr/blog/trouver-un-stage-etudiant')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('translation', '/blog/how-to-find-an-internship-as-a-student')
            ->where('seo.structured_data.0.inLanguage', 'fr'));
    }

    public function test_unknown_post_and_wrong_language_return_404()
    {
        $this->get('/blog/not-a-real-post')->assertNotFound();
        $this->get('/fr/blog/how-to-find-an-internship-as-a-student')->assertNotFound();
    }

    public function test_markdown_copy_and_rss_feed()
    {
        $this->get('/blog/how-to-find-an-internship-as-a-student.md')->assertOk()
            ->assertHeader('Content-Type', 'text/markdown; charset=UTF-8')
            ->assertHeader('Link', '<'.url('/blog/how-to-find-an-internship-as-a-student').'>; rel="canonical"')
            ->assertSee('# How to find an internship as a student', false);

        $feed = $this->get('/fr/blog/feed.xml')->assertOk()->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8');
        $xml = simplexml_load_string($feed->getContent());
        $this->assertSame('fr', (string) $xml->channel->language);
        $this->assertContains(url('/fr/blog/trouver-un-stage-etudiant'), collect($xml->channel->item)->map(fn ($item) => (string) $item->link)->all());
    }

    public function test_sitemap_lists_posts_with_last_modified_date()
    {
        $xml = $this->get('/sitemaps/pages.xml')->assertOk()->getContent();
        $this->assertStringContainsString('<loc>'.url('/fr/blog/trouver-un-stage-etudiant').'</loc><lastmod>', $xml);
        $this->assertStringContainsString('<loc>'.url('/fr/about').'</loc>', $xml);
    }
}

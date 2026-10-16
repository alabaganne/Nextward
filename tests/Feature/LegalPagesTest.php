<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_legal_pages_render_in_english_and_french()
    {
        $pages = [
            '/privacy' => ['Privacy', 'en', 'Privacy policy | Nextward'],
            '/terms' => ['Terms', 'en', 'Terms of use | Nextward'],
            '/fr/privacy' => ['Privacy', 'fr', 'Politique de confidentialité | Nextward'],
            '/fr/terms' => ['Terms', 'fr', 'Conditions d’utilisation | Nextward'],
        ];
        foreach ($pages as $uri => [$component, $locale, $title]) {
            $this->get($uri)->assertOk()->assertInertia(fn (Assert $page) => $page
                ->component($component)
                ->where('locale', $locale)
                ->where('seo.title', $title)
                ->where('seo.robots', 'index, follow, max-image-preview:large')
                ->where('seo.alternates.1.url', url('/fr'.preg_replace('#^/fr#', '', $uri))));
        }
    }

    public function test_sitemap_lists_legal_pages()
    {
        $xml = $this->get('/sitemaps/pages.xml')->assertOk()->getContent();
        foreach (['/privacy', '/terms', '/fr/privacy', '/fr/terms'] as $path) {
            $this->assertStringContainsString('<loc>'.url($path).'</loc>', $xml);
        }
    }
}

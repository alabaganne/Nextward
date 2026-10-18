<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Company;
use App\Models\Field;
use App\Models\Internship;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FrenchPagesTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Internship $internship;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        config(['inertia.pages.paths' => [resource_path('js/Pages')]]);
        $city = City::create(['name' => 'Tunis']);
        $field = Field::create(['name' => 'Web Development']);
        $this->company = Company::create(['name' => 'Atlas Cloud', 'city_id' => $city->id]);
        $this->internship = Internship::create([
            'title' => 'Backend intern',
            'description' => 'Internship used in tests.',
            'company_id' => $this->company->id,
            'field_id' => $field->id,
            'city_id' => $city->id,
            'closing_at' => today()->addMonth(),
        ]);
    }

    public function test_an_untranslated_french_page_shows_the_original_and_stays_out_of_search()
    {
        $en = url('/opportunities/'.$this->internship->id.'-backend-intern');
        $fr = url('/fr/opportunities/'.$this->internship->id.'-backend-intern');
        $this->assertSame($fr, $this->internship->publicUrl('fr'));

        $this->get($fr)->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Internships/Show')
            ->where('locale', 'fr')
            ->where('internship.public_url', $fr)
            ->where('internship.company.public_url', $this->company->publicUrl('fr'))
            ->where('internship.description', 'Internship used in tests.')
            ->where('internship.translated', false)
            ->where('internship.original', null)
            ->where('translation', '/opportunities/'.$this->internship->id.'-backend-intern')
            ->where('seo.title', 'Backend intern chez Atlas Cloud | Nextward')
            ->where('seo.canonical', $fr)
            ->where('seo.locale', 'fr_FR')
            ->where('seo.robots', 'noindex, follow')
            ->where('seo.alternates', []));

        $this->get($en)->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('locale', 'en')
            ->where('internship.public_url', $en)
            ->where('seo.canonical', $en)
            ->where('seo.robots', 'index, follow, max-image-preview:large'));
    }

    public function test_a_translated_listing_has_a_french_page_with_the_original_below()
    {
        $this->internship->update(['language' => 'en', 'posted_at' => now()]);
        $this->internship->translations()->create([
            'locale' => 'fr', 'title' => 'Stagiaire backend', 'description' => 'Stage utilisé dans les tests, avec assez de texte pour une vraie page.',
            'slug' => 'stagiaire-backend', 'source_hash' => $this->internship->sourceHash(),
        ]);
        $en = url('/opportunities/'.$this->internship->id.'-backend-intern');
        $fr = url('/fr/opportunities/'.$this->internship->id.'-stagiaire-backend');

        $this->get('/fr/opportunities/'.$this->internship->id.'-backend-intern')->assertStatus(301)->assertRedirect($fr);
        $this->get($fr)->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('internship.title', 'Stagiaire backend')
            ->where('internship.translated', true)
            ->where('internship.original.title', 'Backend intern')
            ->where('internship.original.description', 'Internship used in tests.')
            ->where('internship.original.label', 'anglais')
            ->where('seo.title', 'Stagiaire backend chez Atlas Cloud | Nextward')
            ->where('seo.canonical', $fr)
            ->where('seo.robots', 'index, follow, max-image-preview:large')
            ->where('seo.alternates', [['lang' => 'en', 'url' => $en], ['lang' => 'fr', 'url' => $fr], ['lang' => 'x-default', 'url' => $en]]));

        $this->get($en)->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('internship.title', 'Backend intern')
            ->where('internship.translated', false)
            ->where('seo.alternates.1.url', $fr));

        $this->get('/fr/opportunities?search=stagiaire')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('internships.data.0.title', 'Stagiaire backend'));
    }

    public function test_french_opportunity_with_wrong_slug_redirects_to_the_french_url()
    {
        $this->get('/fr/opportunities/'.$this->internship->id.'-old-title')
            ->assertStatus(301)->assertRedirect($this->internship->publicUrl('fr'));
    }

    public function test_french_list_cards_link_to_french_pages()
    {
        $this->get('/fr/opportunities')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('internships.data.0.public_url', $this->internship->publicUrl('fr')));
        $this->get('/fr/opportunities/internships')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('category', 'internships'));
    }

    public function test_french_company_page_is_in_french_with_language_pairs()
    {
        $fr = $this->company->publicUrl('fr');
        $this->assertSame(url('/fr/companies/'.$this->company->id.'-atlas-cloud'), $fr);

        $this->get($fr)->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Companies/Public')
            ->where('locale', 'fr')
            ->where('opportunities.0.public_url', $this->internship->publicUrl('fr'))
            ->where('translation', '/companies/'.$this->company->id.'-atlas-cloud')
            ->where('seo.title', 'Stages et emplois chez Atlas Cloud | Nextward')
            ->where('seo.canonical', $fr)
            ->where('seo.alternates.0.url', $this->company->public_url));
        $this->get('/fr/companies/'.$this->company->id.'-old-name')->assertStatus(301)->assertRedirect($fr);
    }

    public function test_sitemaps_list_french_opportunity_and_company_urls()
    {
        $xml = $this->get('/sitemaps/opportunities-1.xml')->assertOk()->getContent();
        $this->assertStringContainsString('<loc>'.$this->internship->public_url.'</loc>', $xml);
        $this->assertStringNotContainsString('/fr/opportunities/', $xml);

        $this->internship->translations()->create(['locale' => 'fr', 'title' => 'Stagiaire backend', 'description' => 'Stage utilisé dans les tests.', 'slug' => 'stagiaire-backend', 'source_hash' => 'x']);
        $this->internship->unsetRelation('translations');
        $xml = $this->get('/sitemaps/opportunities-1.xml')->assertOk()->getContent();
        $this->assertStringContainsString('<loc>'.$this->internship->publicUrl('fr').'</loc>', $xml);
        $this->assertStringContainsString('<xhtml:link rel="alternate" hreflang="fr" href="'.$this->internship->publicUrl('fr').'"/>', $xml);

        $xml = $this->get('/sitemaps/companies.xml')->assertOk()->getContent();
        $this->assertStringContainsString('<loc>'.$this->company->publicUrl('fr').'</loc>', $xml);
    }

    public function test_french_auth_pages_render_in_french_and_stay_out_of_search()
    {
        foreach (['/fr/login' => 'Auth/Login', '/fr/register' => 'Auth/Register', '/fr/forgot-password' => 'Auth/ForgotPassword'] as $uri => $component) {
            $this->get($uri)->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow')
                ->assertInertia(fn (Assert $page) => $page->component($component)->where('locale', 'fr'));
        }
    }

    public function test_french_auth_errors_are_in_french()
    {
        $this->from('/fr/login')->post('/fr/login', ['email' => 'nobody@example.com', 'password' => 'wrong-password'])
            ->assertRedirect('/fr/login')
            ->assertSessionHasErrors(['email' => 'Ces identifiants ne correspondent à aucun compte.']);
        $this->assertGuest();

        $this->from('/fr/register')->post('/fr/register', ['account_type' => 'student'])
            ->assertRedirect('/fr/register')
            ->assertSessionHasErrors(['name' => 'Le champ nom est obligatoire.']);

        $this->post('/login', ['email' => 'nobody@example.com', 'password' => 'wrong-password'])
            ->assertSessionHasErrors(['email' => 'These credentials do not match our records.']);
    }

    public function test_expired_session_message_follows_the_page_language()
    {
        \Illuminate\Support\Facades\Route::middleware('web')->post('/fr/expired', fn () => abort(419));
        \Illuminate\Support\Facades\Route::middleware('web')->post('/expired', fn () => abort(419));

        $this->from('/fr/login')->post('/fr/expired')->assertRedirect('/fr/login')
            ->assertSessionHas('toast.message', 'Votre session a expiré. Veuillez réessayer.');
        $this->from('/login')->post('/expired')->assertRedirect('/login')
            ->assertSessionHas('toast.message', 'Your session expired. Please try again.');
    }

    public function test_french_error_page_is_in_french()
    {
        config(['app.debug' => false]);
        $this->get('/fr/opportunities/999999-missing')->assertNotFound()->assertInertia(fn (Assert $page) => $page
            ->component('Error')
            ->where('status', 404)
            ->where('locale', 'fr'));
    }
}

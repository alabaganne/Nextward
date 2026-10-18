<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Company;
use App\Models\Field;
use App\Models\Opportunity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicCompanyTest extends TestCase
{
    use RefreshDatabase;

    private City $city;

    private Field $field;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        config(['inertia.pages.paths' => [resource_path('js/Pages')]]);
        $this->city = City::create(['name' => 'Tunis']);
        $this->field = Field::create(['name' => 'Web Development']);
    }

    public function test_guests_can_view_a_company_with_open_roles()
    {
        $company = Company::create(['name' => 'Atlas Cloud', 'website' => 'https://atlas.example', 'city_id' => $this->city->id]);
        $this->opportunity($company, 'Backend intern');

        $this->assertSame(url('/companies/'.$company->id.'-atlas-cloud'), $company->public_url);
        $this->get($company->public_url)->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Companies/Public')
            ->where('company.name', 'Atlas Cloud')
            ->has('opportunities', 1)
            ->where('seo.robots', 'index, follow, max-image-preview:large')
            ->where('seo.canonical', $company->public_url)
            ->where('seo.structured_data.0.@type', 'Organization'));
    }

    public function test_wrong_slug_redirects_to_the_canonical_url()
    {
        $company = Company::create(['name' => 'Atlas Cloud']);
        $this->get('/companies/'.$company->id.'-old-name')->assertStatus(301)->assertRedirect($company->public_url);
    }

    public function test_thin_company_pages_are_not_indexed()
    {
        $company = Company::create(['name' => 'Quiet Studio', 'about' => 'Short text.']);
        $this->get($company->public_url)->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('seo.robots', 'noindex, follow'));
    }

    public function test_claimed_company_redirects_to_the_claiming_company()
    {
        $owner = Company::create(['name' => 'Atlas Cloud']);
        $imported = Company::create(['name' => 'Atlas Cloud SARL']);
        $imported->forceFill(['claimed_by_company_id' => $owner->id])->save();
        $this->get($imported->public_url)->assertStatus(301)->assertRedirect($owner->public_url);
    }

    public function test_company_sitemap_only_lists_indexable_companies()
    {
        $hiring = Company::create(['name' => 'Atlas Cloud']);
        $this->opportunity($hiring, 'Backend intern');
        $profile = Company::create(['name' => 'Long Story', 'about' => str_repeat('We build software for schools. ', 12)]);
        $thin = Company::create(['name' => 'Quiet Studio']);

        $this->get('/sitemap.xml')->assertOk()->assertSee(url('/sitemaps/companies.xml'), false);
        $xml = $this->get('/sitemaps/companies.xml')->assertOk()->getContent();
        $this->assertStringContainsString($hiring->public_url, $xml);
        $this->assertStringContainsString($profile->public_url, $xml);
        $this->assertStringNotContainsString($thin->public_url, $xml);
    }

    private function opportunity(Company $company, string $title): Opportunity
    {
        return Opportunity::create([
            'title' => $title,
            'description' => 'Internship used in tests.',
            'company_id' => $company->id,
            'field_id' => $this->field->id,
            'city_id' => $this->city->id,
            'closing_at' => today()->addMonth(),
        ]);
    }
}

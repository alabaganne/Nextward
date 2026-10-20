<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Company;
use App\Models\Field;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicCompanyDirectoryTest extends TestCase
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

    public function test_guests_see_companies_hiring_now_with_their_latest_roles()
    {
        $atlas = Company::create(['name' => 'Atlas Cloud', 'city_id' => $this->city->id]);
        foreach (['First role', 'Second role', 'Third role', 'Fourth role'] as $title) {
            $this->opportunity($atlas, $title);
        }
        $quiet = Company::create(['name' => 'Quiet Labs']);
        (new Company(['name' => 'Merged Co']))->forceFill(['claimed_by_company_id' => $atlas->id])->save();
        $this->opportunity(Company::create(['name' => '']), 'Nameless role');

        $this->get('/companies')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Companies/Directory')
            ->where('companies.total', 1)
            ->where('companies.data.0.name', 'Atlas Cloud')
            ->where('companies.data.0.location', 'Tunis')
            ->where('companies.data.0.open_roles_count', 4)
            ->has('companies.data.0.roles', 3)
            ->where('companies.data.0.url', $atlas->publicUrl())
            ->where('seo.robots', 'index, follow, max-image-preview:large'));

        $this->get('/companies?show=all')->assertInertia(fn (Assert $page) => $page
            ->where('companies.total', 2)
            ->where('companies.data.1.name', $quiet->name)
            ->where('companies.data.1.open_roles_count', 0)
            ->where('seo.robots', 'noindex, follow'));
    }

    public function test_guests_can_search_and_filter_by_field()
    {
        $this->opportunity(Company::create(['name' => 'Atlas Cloud']), 'Backend intern');
        $data = Field::create(['name' => 'Data Science & AI']);
        $this->opportunity(Company::create(['name' => 'Carthage Data', 'location' => 'Sfax']), 'Data analyst', $data);

        $this->get('/companies?search=sfax')->assertInertia(fn (Assert $page) => $page
            ->where('companies.total', 1)->where('companies.data.0.name', 'Carthage Data'));
        $this->get('/companies?field='.$data->id)->assertInertia(fn (Assert $page) => $page
            ->where('companies.total', 1)->where('companies.data.0.name', 'Carthage Data')
            ->where('filters.field', $data->id)->has('fields', 2));
        $this->get('/companies?search=nothing-like-this')->assertInertia(fn (Assert $page) => $page->where('companies.total', 0));
    }

    public function test_the_french_directory_links_to_french_pages()
    {
        $company = Company::create(['name' => 'Atlas Cloud']);
        $role = $this->opportunity($company, 'Backend intern');

        $this->get('/fr/companies')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Companies/Directory')
            ->where('companies.data.0.url', $company->publicUrl('fr'))
            ->where('companies.data.0.roles.0.url', $role->publicUrl('fr')));
    }

    public function test_signed_in_users_get_the_public_directory_and_the_workspace_list_under_app()
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/companies')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Companies/Directory'));
        $this->actingAs($user)->get('/app/companies')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Companies/Index'));
        $this->actingAs($user)->get('/fr/companies')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Companies/Directory'));
    }

    public function test_the_home_page_shows_companies_hiring_now()
    {
        $this->opportunity(Company::create(['name' => 'Atlas Cloud']), 'Backend intern');
        Company::create(['name' => 'Quiet Labs']);

        $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('companies', 1)
            ->where('companies.0.name', 'Atlas Cloud')
            ->where('hiringCount', 1));
        $this->get('/sitemaps/pages.xml')->assertOk()->assertSee(url('/companies'), false);
    }

    private function opportunity(Company $company, string $title, ?Field $field = null): Opportunity
    {
        return Opportunity::create([
            'title' => $title,
            'description' => 'Role used in tests.',
            'company_id' => $company->id,
            'field_id' => ($field ?? $this->field)->id,
            'city_id' => $this->city->id,
            'closing_at' => today()->addMonth(),
        ]);
    }
}

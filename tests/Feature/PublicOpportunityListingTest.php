<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Company;
use App\Models\Field;
use App\Models\Opportunity;
use App\Models\Candidate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicOpportunityListingTest extends TestCase
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

    public function test_guests_only_see_opportunities_whose_closing_date_has_not_passed()
    {
        $this->travelTo(Carbon::parse('2026-10-13 23:30:00', 'UTC'));
        $today = today(config('importer.timezone', 'Africa/Tunis'));
        $company = $this->company('Atlas Cloud');
        $this->opportunity($company, 'Closes tomorrow', $today->copy()->addDay());
        $this->opportunity($company, 'Closes today', $today);
        $this->opportunity($company, 'Closed yesterday', $today->copy()->subDay());

        $this->get('/opportunities/internships')->assertInertia(fn (Assert $page) => $page
            ->component('Opportunities/Index')
            ->has('opportunities.data', 2)
            ->where('opportunities.data.0.is_open', true)
            ->where('opportunities.data.1.is_open', true)
            ->where('fields.0.opportunities_count', 2)
        );
    }

    public function test_browse_can_filter_by_several_fields_at_once()
    {
        $company = $this->company('Atlas Cloud');
        $design = Field::create(['name' => 'Design']);
        $finance = Field::create(['name' => 'Finance']);
        $this->opportunity($company, 'Web role', now()->addMonth());
        $this->opportunity($company, 'Design role', now()->addMonth())->update(['field_id' => $design->id]);
        $this->opportunity($company, 'Finance role', now()->addMonth())->update(['field_id' => $finance->id]);

        $this->get("/opportunities?fields[]={$this->field->id}&fields[]={$design->id}")->assertInertia(fn (Assert $page) => $page
            ->has('opportunities.data', 2)
            ->where('filters.fields', [(string) $this->field->id, (string) $design->id])
            ->where('opportunities.data', fn ($roles) => collect($roles)->pluck('title')->sort()->values()->all() === ['Design role', 'Web role'])
        );
    }

    public function test_the_board_is_empty_when_nothing_is_open()
    {
        $this->opportunity($this->company('Atlas Cloud'), 'Closed last month', now()->subMonth());

        $this->get('/opportunities/internships')->assertInertia(fn (Assert $page) => $page
            ->has('opportunities.data', 0)
            ->where('fields.0.opportunities_count', 0)
        );
    }

    public function test_open_worldwide_shows_roles_anyone_can_apply_to_and_never_ones_that_leave_tunisia_out()
    {
        $company = $this->company('Atlas Cloud');
        $this->opportunity($company, 'Anywhere role', now()->addMonth())->update(['applicant_scope' => 'anywhere']);
        $this->opportunity($company, 'Anywhere but sanctions', now()->addMonth())->update(['applicant_scope' => 'anywhere', 'excluded_countries' => ['IR', 'CU']]);
        $this->opportunity($company, 'Anywhere but Tunisia', now()->addMonth())->update(['applicant_scope' => 'anywhere', 'excluded_countries' => ['TN']]);
        $this->opportunity($company, 'US work rights needed', now()->addMonth())->update(['applicant_scope' => 'local', 'country_code' => 'US']);
        $this->opportunity($company, 'Not stated', now()->addMonth());

        $this->get('/opportunities?worldwide=1')->assertInertia(fn (Assert $page) => $page
            ->where('filters.worldwide', '1')
            ->where('opportunities.data', fn ($roles) => collect($roles)->pluck('title')->sort()->values()->all() === ['Anywhere but sanctions', 'Anywhere role'])
            ->where('opportunities.data', fn ($roles) => collect($roles)->every(fn ($role) => $role['applicant_scope'] === 'anywhere'))
        );
    }

    public function test_the_old_tunisia_pages_lead_to_the_matching_filters()
    {
        $this->get('/opportunities/tunisia?search=react')->assertStatus(301)->assertRedirect('/opportunities?search=react&country=TN');
        $this->get('/opportunities/remote-from-tunisia')->assertStatus(301)->assertRedirect('/opportunities?work_type=remote');
        $this->get('/fr/opportunities/relocation')->assertStatus(301)->assertRedirect('/fr/opportunities?relocation=1');
    }

    public function test_companies_still_see_their_own_closed_opportunities()
    {
        $company = $this->company('Atlas Cloud');
        $closed = $this->opportunity($company, 'Our closed role', now()->subDay());
        $otherCompany = $this->company('Other Co');
        $otherClosed = $this->opportunity($otherCompany, 'Their closed role', now()->subDay());

        $this->actingAs($company->user)->get(route('companies.show', $company))->assertInertia(fn (Assert $page) => $page
            ->component('Companies/Show')
            ->has('company.opportunities.data', 1)
            ->where('company.opportunities.data.0.id', $closed->id)
            ->where('company.opportunities.data.0.is_open', false)
        );
        $this->get(route('companies.show', $otherCompany))->assertInertia(fn (Assert $page) => $page
            ->has('company.opportunities.data', 0));
        $this->get(route('opportunities.edit', $closed))->assertOk();
        $this->get(route('opportunities.edit', $otherClosed))->assertForbidden();
        $this->get('/opportunities')->assertInertia(fn (Assert $page) => $page->has('opportunities.data', 0));
    }

    public function test_company_management_paginates_owned_drafts_and_closed_roles_without_exposing_them_to_other_accounts()
    {
        $company = $this->company('Atlas Cloud');
        $openRoles = collect(range(1, 10))->map(fn ($number) => $this->opportunity($company, 'Open role '.$number, now()->addWeek()));
        $closed = $this->opportunity($company, 'Closed role', now()->subDay());
        $draft = $this->opportunity($company, 'Draft job', null);
        $draft->update(['kind' => 'job', 'publication_status' => 'draft']);
        $otherCompany = $this->company('Other Co');
        $otherDraft = $this->opportunity($otherCompany, 'Other draft', null);
        $otherDraft->update(['publication_status' => 'draft']);

        $response = $this->actingAs($company->user)->get(route('companies.show', $company));
        $response->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Companies/Show')
                ->has('company.opportunities.data', 10)
                ->where('company.opportunities.meta.total', 12)
                ->where('company.opportunities.meta.last_page', 2)
                ->where('company.opportunities.data.0.id', $draft->id)
                ->where('company.opportunities.data.0.publication_status', 'draft')
                ->where('company.opportunities.data.1.id', $closed->id)
                ->where('company.opportunities.data.1.is_open', false)
                ->where('company.opportunities.links.next', route('companies.show', [$company, 'page' => 2])));
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->get(route('companies.show', [$company, 'page' => 2]))->assertInertia(fn (Assert $page) => $page
            ->has('company.opportunities.data', 2)
            ->where('company.opportunities.data.0.id', $openRoles[1]->id)
            ->where('company.opportunities.data.1.id', $openRoles[0]->id)
            ->where('company.opportunities.meta.current_page', 2));
        $this->get(route('opportunities.edit', $draft))->assertOk();
        $this->get(route('opportunities.edit', $otherDraft))->assertForbidden();
        $this->get($draft->public_url)->assertNotFound();
        $this->get('/opportunities')->assertInertia(fn (Assert $page) => $page
            ->has('opportunities.data', 10));

        $this->actingAs($otherCompany->user)->get(route('companies.show', $company))->assertInertia(fn (Assert $page) => $page
            ->has('company.opportunities.data', 10)
            ->where('company.opportunities.meta.total', 10)
            ->where('company.opportunities.data.0.id', $openRoles->last()->id));
        $this->actingAs($this->candidate())->get(route('companies.show', $company))->assertInertia(fn (Assert $page) => $page
            ->where('company.opportunities.meta.total', 10)
            ->where('company.opportunities.data.0.id', $openRoles->last()->id));
        $this->get(route('opportunities.edit', $draft))->assertForbidden();
        $this->get($draft->public_url)->assertNotFound();
    }

    public function test_candidates_cannot_apply_after_the_closing_date()
    {
        $company = $this->company('Atlas Cloud');
        $open = $this->opportunity($company, 'Open role', now()->addWeek());
        $closed = $this->opportunity($company, 'Closed role', now()->subDay());
        $candidate = $this->candidate();

        $this->actingAs($candidate)->get(route('applications.create', $open))->assertOk();
        $this->actingAs($candidate)->get(route('applications.create', $closed))->assertForbidden();
        $this->actingAs($candidate)->post(route('applications.store', $closed), [
            'cover_letter' => str_repeat('I would love to join your team. ', 10),
        ])->assertForbidden();

        $this->get(route('internships.show', $closed))->assertStatus(301)->assertRedirect($closed->public_url);
        $this->get($closed->public_url)->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Opportunities/Show')
            ->where('opportunity.is_open', false)
            ->where('seo.robots', 'noindex, follow')
            ->where('seo.structured_data', [])
        );
    }

    public function test_sitemap_skips_closed_opportunities()
    {
        $company = $this->company('Atlas Cloud');
        $open = $this->opportunity($company, 'Open role', now()->addWeek());
        $closed = $this->opportunity($company, 'Closed role', now()->subDay());

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee('<sitemapindex ', false)
            ->assertSee('/sitemaps/pages.xml<', false)
            ->assertSee('/sitemaps/opportunities-1.xml<', false);
        $this->get('/sitemaps/opportunities-1.xml')
            ->assertOk()
            ->assertSee('<urlset ', false)
            ->assertSee('<loc>'.$open->public_url.'</loc>', false)
            ->assertDontSee($closed->public_url, false)
            ->assertDontSee('/internships/', false);
    }

    public function test_explicitly_closed_roles_are_hidden_and_reject_applications_before_the_deadline()
    {
        $company = $this->company('Atlas Cloud');
        $opportunity = $this->opportunity($company, 'Closed early', now()->addWeek());
        $opportunity->update(['status' => 'closed']);

        $this->get('/opportunities')->assertInertia(fn (Assert $page) => $page
            ->has('opportunities.data', 0)
            ->where('fields.0.opportunities_count', 0));
        $this->get('/sitemap.xml')->assertDontSee('/sitemaps/opportunities-', false);
        $this->get('/sitemaps/opportunities-1.xml')->assertNotFound();

        $this->actingAs($this->candidate())
            ->get(route('applications.create', $opportunity))->assertForbidden();
        $this->post(route('applications.store', $opportunity), [
            'cover_letter' => str_repeat('I would love to join your team. ', 10),
        ])->assertForbidden();

        $this->actingAs($company->user)->get(route('companies.show', $company))
            ->assertInertia(fn (Assert $page) => $page
                ->has('company.opportunities.data', 1)
                ->where('company.opportunities.data.0.is_open', false));
    }

    public function test_home_page_receives_real_open_opportunities_and_jobs_without_social_proof_counts()
    {
        $company = $this->company('Atlas Cloud');
        $opportunity = $this->opportunity($company, 'Software Engineering Intern', now()->addWeek());
        $job = $this->opportunity($company, 'Senior Software Engineer', now()->addWeek());
        $job->update(['kind' => 'job']);
        $this->opportunity($company, 'Closed role', now()->subDay());

        $this->get('/')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Welcome')
                ->has('posts', 3)
                ->has('opportunities', 2)
                ->where('opportunities.0.id', $job->id)
                ->where('opportunities.0.kind', 'job')
                ->where('opportunities.1.id', $opportunity->id)
                ->where('opportunities.1.kind', 'internship')
                ->where('opportunities.1.company.name', 'Atlas Cloud')
                ->missing('companies_count')
                ->missing('candidates_count')
                ->missing('placements_count'));
    }

    private function company(string $name): Company
    {
        $company = Company::create(['website' => 'https://example.com', 'about' => 'A company used in tests.', 'city_id' => $this->city->id]);
        $company->user()->create(['name' => $name, 'email' => str($name)->slug().'@example.com', 'password' => 'password'])
            ->forceFill(['email_verified_at' => now()])->save();

        return $company->load('user');
    }

    private function candidate(): User
    {
        $candidate = Candidate::create(['field_id' => $this->field->id, 'city_id' => $this->city->id]);

        $user = $candidate->user()->create(['name' => 'Test Student', 'email' => 'student@example.com', 'password' => 'password']);
        $user->forceFill(['email_verified_at' => now()])->save();

        return $user;
    }

    private function opportunity(Company $company, string $title, $closingAt): Opportunity
    {
        return Opportunity::create([
            'title' => $title,
            'description' => 'Internship used in tests.',
            'company_id' => $company->id,
            'field_id' => $this->field->id,
            'city_id' => $this->city->id,
            'closing_at' => $closingAt,
        ]);
    }
}

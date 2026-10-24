<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Company;
use App\Models\Field;
use App\Models\Opportunity;
use App\Models\Candidate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PageSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Tests\Fixtures\DemoSeeder::class);
    }

    private function candidate(): User
    {
        return User::where('email', 'student@example.com')->firstOrFail();
    }

    public function test_public_pages_render(): void
    {
        foreach (['/', '/fr', '/about', '/contact', '/blog', '/fr/about', '/fr/contact', '/fr/blog', '/opportunities', '/opportunities/internships', '/opportunities/jobs', '/login', '/register'] as $uri) {
            $this->get($uri)->assertOk();
        }

        $this->get(\App\Support\Blog::all('en')[0]['path'])->assertOk();

        $opportunity = Opportunity::firstOrFail();
        $this->get('/internships')->assertStatus(301)->assertRedirect('/opportunities/internships');
        $this->get("/internships/{$opportunity->id}")
            ->assertStatus(301)
            ->assertRedirect($opportunity->public_url);
        $this->get($opportunity->public_url)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Opportunities/Show')
                ->has('opportunity.title')
                ->has('opportunity.company.name'));
    }

    public function test_the_old_candidate_and_professional_pages_redirect_to_the_home_page(): void
    {
        foreach (['/for-students' => '/', '/for-professionals' => '/', '/fr/for-students' => '/fr', '/fr/for-professionals' => '/fr'] as $uri => $home) {
            $this->get($uri)->assertStatus(301)->assertRedirect($home);
        }
    }

    public function test_home_page_shortcuts_link_to_working_searches_and_carry_the_faq(): void
    {
        $response = $this->get('/fr')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Welcome')->has('faqs', 7)->where('faqs.0.q', 'Faut-il un compte pour consulter les offres ?'));
        $props = $response->viewData('page')['props'];
        $this->assertNotEmpty($props['shortcuts']);
        foreach ($props['shortcuts'] as $shortcut) {
            $this->assertGreaterThan(0, $shortcut['count']);
            $this->get($shortcut['path'].($shortcut['query'] ? '?'.$shortcut['query'] : ''))->assertOk();
        }
        $this->assertContains('FAQPage', array_column($props['seo']['structured_data'], '@type'));
    }

    public function test_workspace_pages_render_for_candidates(): void
    {
        $candidate = $this->candidate();

        foreach (['/app/dashboard', '/app/opportunities', '/app/applications', '/app/likes', '/app/messages', '/app/companies', '/app/profile', '/app/settings'] as $uri) {
            $this->actingAs($candidate)->get($uri)->assertOk();
        }

        $application = Application::where('candidate_id', $candidate->userable->id)->firstOrFail();
        $this->actingAs($candidate)
            ->get("/app/applications/{$application->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Applications/Show')
                ->has('application.opportunity.title')
                ->has('application.company.name'));

        $company = Company::firstOrFail();
        $this->actingAs($candidate)->get("/app/companies/{$company->id}")->assertOk();
    }

    public function test_dashboard_receives_real_props(): void
    {
        $this->actingAs($this->candidate())
            ->get('/app/dashboard')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('opportunities_count', Opportunity::open()->count())
                ->has('applications'));
    }

    public function test_dashboard_count_and_application_parties_match_workspace_data(): void
    {
        $application = Application::firstOrFail();
        Opportunity::firstOrFail()->update(['status' => 'closed']);

        foreach ([$application->candidate->user, $application->company->user, User::where('email', 'admin@example.com')->firstOrFail()] as $user) {
            $this->actingAs($user)->get('/app/applications')
                ->assertInertia(fn (Assert $page) => $page
                    ->has('applications.data.0.company.id')
                    ->has('applications.data.0.company.name')
                    ->has('applications.data.0.candidate.id')
                    ->has('applications.data.0.candidate.name'));

            $this->get('/app/dashboard')
                ->assertInertia(fn (Assert $page) => $page
                    ->where('opportunities_count', Opportunity::open()->count()));
        }
    }

    public function test_dashboard_interview_counts_include_older_applications_and_exclude_other_accounts(): void
    {
        $candidate = $this->candidate();
        $opportunity = Opportunity::firstOrFail();
        $otherOpportunity = Opportunity::where('company_id', '<>', $opportunity->company_id)->firstOrFail();
        $otherCandidate = Candidate::where('id', '<>', $candidate->userable_id)->firstOrFail();
        Application::query()->delete();

        foreach (['interview', 'interview', 'submitted', 'submitted', 'submitted', 'submitted'] as $index => $status) {
            Application::create([
                'candidate_id' => $candidate->userable_id,
                'company_id' => $opportunity->company_id,
                'opportunity_id' => $opportunity->id,
                'cover_letter' => 'Application used to verify dashboard totals.',
                'status' => $status,
            ])->forceFill(['created_at' => now()->subDays(6 - $index)])->save();
        }
        Application::create([
            'candidate_id' => $otherCandidate->id,
            'company_id' => $otherOpportunity->company_id,
            'opportunity_id' => $otherOpportunity->id,
            'cover_letter' => 'Application belonging to other accounts.',
            'status' => 'interview',
        ]);

        foreach ([$candidate, $opportunity->company->user] as $user) {
            $this->actingAs($user)->get('/app/dashboard')
                ->assertInertia(fn (Assert $page) => $page
                    ->has('applications', 4)
                    ->where('applications', fn ($applications) => collect($applications)
                        ->every(fn ($application) => $application['status'] === 'submitted'))
                    ->where('applications_count', 6)
                    ->where('interviews_count', 2));
        }
    }

    public function test_opportunities_index_receives_filters_and_results(): void
    {
        $this->actingAs($this->candidate())
            ->get('/opportunities')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Opportunities/Index')
                ->has('opportunities.data')
                ->has('fields')
                ->has('countries')
                ->has('counts.open')
                ->has('counts.archived')
                ->missing('cities'));
    }

    public function test_opportunities_index_counts_open_and_archived_listings(): void
    {
        $open = Opportunity::available()->count();
        $archived = Opportunity::available()->latest('id')->firstOrFail();
        $archived->update(['status' => 'closed']);

        $this->get('/opportunities')->assertInertia(fn (Assert $page) => $page
            ->where('counts.open', $open - 1)
            ->where('counts.archived', Opportunity::published()->count() - ($open - 1)));
    }

    public function test_company_edit_uses_the_company_route_identifier(): void
    {
        $company = Company::with('user')->firstOrFail();

        $this->actingAs($company->user)
            ->get(route('companies.edit', $company))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Companies/Edit')
                ->where('company.id', $company->id));
    }

    public function test_company_and_admin_workspace_pages_render(): void
    {
        $company = User::where('email', 'company@example.com')->firstOrFail();
        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)->get('/app/candidates/manage')->assertOk();

        foreach ([$company, $admin] as $user) {
            foreach (['/app/dashboard', '/app/opportunities', '/app/applications', '/app/messages', '/app/companies', '/app/profile', '/app/settings'] as $uri) {
                $this->actingAs($user)->get($uri)->assertOk();
            }
        }
    }

    public function test_company_role_management_pages_receive_their_form_data(): void
    {
        $user = User::where('email', 'company@example.com')->firstOrFail();
        $opportunity = Opportunity::where('company_id', $user->userable_id)->firstOrFail();

        $this->actingAs($user)->get(route('opportunities.edit', $opportunity))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Opportunities/Edit')
                ->where('opportunity.id', $opportunity->id)
                ->has('opportunity.work_type')
                ->has('fields.0.id'));
        $this->get(route('opportunities.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Opportunities/Edit')
                ->has('fields.0.name'));
        $this->get(route('opportunities.applications.index', $opportunity))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Opportunities/Applications')
                ->where('opportunity.id', $opportunity->id)
                ->has('applications.data.0.candidate.name'));
        $this->get(route('settings'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings')
                ->where('settings.userable_type', 'company')
                ->where('settings.city_id', $user->userable->city_id)
                ->has('cities.0.name'));
    }

    public function test_admin_catalog_pages_and_edit_forms_receive_their_data(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $company = Company::firstOrFail();
        $candidate = Candidate::firstOrFail();
        $opportunity = Opportunity::firstOrFail();
        $field = Field::firstOrFail();
        $this->actingAs($admin);

        foreach ([
            'app.companies.index' => 'Companies/Index',
            'candidates.index' => 'Candidates/Index',
            'skills.index' => 'Skills/Index',
            'fields.index' => 'Fields/Index',
            'fields.create' => 'Fields/Edit',
        ] as $route => $component) {
            $this->get(route($route))->assertOk()
                ->assertInertia(fn (Assert $page) => $page->component($component));
        }

        foreach ([
            ['companies.edit', $company, 'Companies/Edit', 'company'],
            ['candidates.edit', $candidate, 'Candidates/Edit', 'candidate'],
            ['opportunities.edit', $opportunity, 'Opportunities/Edit', 'opportunity'],
            ['fields.edit', $field, 'Fields/Edit', 'field'],
        ] as [$route, $model, $component, $prop]) {
            $this->get(route($route, $model))->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component($component)
                    ->where("{$prop}.id", $model->id));
        }

        $this->get(route('companies.create'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Companies/Edit')
                ->has('cities.0.id'));
        $this->get(route('candidates.create'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Candidates/Edit')
                ->has('fields.0.id')
                ->has('cities.0.id'));

        // Posting requires a company profile so that ownership and city are known.
        $this->get(route('opportunities.create'))->assertForbidden();
    }

    public function test_application_detail_contact_data_is_available_to_both_parties_and_admins(): void
    {
        $application = Application::firstOrFail();
        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        foreach ([$application->candidate->user, $application->company->user, $admin] as $user) {
            $this->actingAs($user)->get(route('applications.show', $application))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('Applications/Show')
                    ->where('application.company.id', $application->company_id)
                    ->where('application.company.user_id', $application->company->user->id)
                    ->where('application.company.name', $application->company->user->name)
                    ->where('application.candidate.id', $application->candidate_id)
                    ->where('application.candidate.user_id', $application->candidate->user->id)
                    ->where('application.candidate.name', $application->candidate->user->name)
                    ->has('application.opportunity.field.name')
                    ->has('application.opportunity.city.name')
                    ->has('application.attachments'));
        }
    }
}

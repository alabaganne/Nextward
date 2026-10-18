<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Company;
use App\Models\Field;
use App\Models\Internship;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicInternshipListingTest extends TestCase
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

    public function test_guests_only_see_internships_whose_closing_date_has_not_passed()
    {
        $this->travelTo(Carbon::parse('2026-10-13 23:30:00', 'UTC'));
        $today = today(config('importer.timezone', 'Africa/Tunis'));
        $company = $this->company('Atlas Cloud');
        $this->internship($company, 'Closes tomorrow', $today->copy()->addDay());
        $this->internship($company, 'Closes today', $today);
        $this->internship($company, 'Closed yesterday', $today->copy()->subDay());

        $this->get('/opportunities/internships')->assertInertia(fn (Assert $page) => $page
            ->component('Internships/Index')
            ->has('internships.data', 2)
            ->where('internships.data.0.is_open', true)
            ->where('internships.data.1.is_open', true)
            ->where('fields.0.internships_count', 2)
            ->where('companies.0.internships_count', 2)
        );
    }

    public function test_browse_can_filter_by_several_fields_at_once()
    {
        $company = $this->company('Atlas Cloud');
        $design = Field::create(['name' => 'Design']);
        $finance = Field::create(['name' => 'Finance']);
        $this->internship($company, 'Web role', now()->addMonth());
        $this->internship($company, 'Design role', now()->addMonth())->update(['field_id' => $design->id]);
        $this->internship($company, 'Finance role', now()->addMonth())->update(['field_id' => $finance->id]);

        $this->get("/opportunities?fields[]={$this->field->id}&fields[]={$design->id}")->assertInertia(fn (Assert $page) => $page
            ->has('internships.data', 2)
            ->where('filters.fields', [(string) $this->field->id, (string) $design->id])
            ->where('internships.data', fn ($roles) => collect($roles)->pluck('title')->sort()->values()->all() === ['Design role', 'Web role'])
        );
    }

    public function test_the_board_is_empty_when_nothing_is_open()
    {
        $this->internship($this->company('Atlas Cloud'), 'Closed last month', now()->subMonth());

        $this->get('/opportunities/internships')->assertInertia(fn (Assert $page) => $page
            ->has('internships.data', 0)
            ->where('fields.0.internships_count', 0)
            ->has('companies', 0)
        );
    }

    public function test_companies_still_see_their_own_closed_internships()
    {
        $company = $this->company('Atlas Cloud');
        $closed = $this->internship($company, 'Our closed role', now()->subDay());
        $otherCompany = $this->company('Other Co');
        $otherClosed = $this->internship($otherCompany, 'Their closed role', now()->subDay());

        $this->actingAs($company->user)->get(route('companies.show', $company))->assertInertia(fn (Assert $page) => $page
            ->component('Companies/Show')
            ->has('company.internships.data', 1)
            ->where('company.internships.data.0.id', $closed->id)
            ->where('company.internships.data.0.is_open', false)
        );
        $this->get(route('companies.show', $otherCompany))->assertInertia(fn (Assert $page) => $page
            ->has('company.internships.data', 0));
        $this->get(route('internships.edit', $closed))->assertOk();
        $this->get(route('internships.edit', $otherClosed))->assertForbidden();
        $this->get('/opportunities')->assertInertia(fn (Assert $page) => $page->has('internships.data', 0));
    }

    public function test_company_management_paginates_owned_drafts_and_closed_roles_without_exposing_them_to_other_accounts()
    {
        $company = $this->company('Atlas Cloud');
        $openRoles = collect(range(1, 10))->map(fn ($number) => $this->internship($company, 'Open role '.$number, now()->addWeek()));
        $closed = $this->internship($company, 'Closed role', now()->subDay());
        $draft = $this->internship($company, 'Draft job', null);
        $draft->update(['kind' => 'job', 'publication_status' => 'draft']);
        $otherCompany = $this->company('Other Co');
        $otherDraft = $this->internship($otherCompany, 'Other draft', null);
        $otherDraft->update(['publication_status' => 'draft']);

        $response = $this->actingAs($company->user)->get(route('companies.show', $company));
        $response->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Companies/Show')
                ->has('company.internships.data', 10)
                ->where('company.internships.meta.total', 12)
                ->where('company.internships.meta.last_page', 2)
                ->where('company.internships.data.0.id', $draft->id)
                ->where('company.internships.data.0.publication_status', 'draft')
                ->where('company.internships.data.1.id', $closed->id)
                ->where('company.internships.data.1.is_open', false)
                ->where('company.internships.links.next', route('companies.show', [$company, 'page' => 2])));
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->get(route('companies.show', [$company, 'page' => 2]))->assertInertia(fn (Assert $page) => $page
            ->has('company.internships.data', 2)
            ->where('company.internships.data.0.id', $openRoles[1]->id)
            ->where('company.internships.data.1.id', $openRoles[0]->id)
            ->where('company.internships.meta.current_page', 2));
        $this->get(route('internships.edit', $draft))->assertOk();
        $this->get(route('internships.edit', $otherDraft))->assertForbidden();
        $this->get($draft->public_url)->assertNotFound();
        $this->get('/opportunities')->assertInertia(fn (Assert $page) => $page
            ->has('internships.data', 10));

        $this->actingAs($otherCompany->user)->get(route('companies.show', $company))->assertInertia(fn (Assert $page) => $page
            ->has('company.internships.data', 10)
            ->where('company.internships.meta.total', 10)
            ->where('company.internships.data.0.id', $openRoles->last()->id));
        $this->actingAs($this->student())->get(route('companies.show', $company))->assertInertia(fn (Assert $page) => $page
            ->where('company.internships.meta.total', 10)
            ->where('company.internships.data.0.id', $openRoles->last()->id));
        $this->get(route('internships.edit', $draft))->assertForbidden();
        $this->get($draft->public_url)->assertNotFound();
    }

    public function test_students_cannot_apply_after_the_closing_date()
    {
        $company = $this->company('Atlas Cloud');
        $open = $this->internship($company, 'Open role', now()->addWeek());
        $closed = $this->internship($company, 'Closed role', now()->subDay());
        $student = $this->student();

        $this->actingAs($student)->get(route('applications.create', $open))->assertOk();
        $this->actingAs($student)->get(route('applications.create', $closed))->assertForbidden();
        $this->actingAs($student)->post(route('applications.store', $closed), [
            'cover_letter' => str_repeat('I would love to join your team. ', 10),
        ])->assertForbidden();

        $this->get(route('internships.show', $closed))->assertStatus(301)->assertRedirect($closed->public_url);
        $this->get($closed->public_url)->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Internships/Show')
            ->where('internship.is_open', false)
            ->where('seo.robots', 'noindex, follow')
            ->where('seo.structured_data', [])
        );
    }

    public function test_sitemap_skips_closed_internships()
    {
        $company = $this->company('Atlas Cloud');
        $open = $this->internship($company, 'Open role', now()->addWeek());
        $closed = $this->internship($company, 'Closed role', now()->subDay());

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
        $internship = $this->internship($company, 'Closed early', now()->addWeek());
        $internship->update(['status' => 'closed']);

        $this->get('/opportunities')->assertInertia(fn (Assert $page) => $page
            ->has('internships.data', 0)
            ->where('fields.0.internships_count', 0));
        $this->get('/sitemap.xml')->assertDontSee('/sitemaps/opportunities-', false);
        $this->get('/sitemaps/opportunities-1.xml')->assertNotFound();

        $this->actingAs($this->student())
            ->get(route('applications.create', $internship))->assertForbidden();
        $this->post(route('applications.store', $internship), [
            'cover_letter' => str_repeat('I would love to join your team. ', 10),
        ])->assertForbidden();

        $this->actingAs($company->user)->get(route('companies.show', $company))
            ->assertInertia(fn (Assert $page) => $page
                ->has('company.internships.data', 1)
                ->where('company.internships.data.0.is_open', false));
    }

    public function test_home_page_receives_real_open_internships_and_jobs_without_social_proof_counts()
    {
        $company = $this->company('Atlas Cloud');
        $internship = $this->internship($company, 'Software Engineering Intern', now()->addWeek());
        $job = $this->internship($company, 'Senior Software Engineer', now()->addWeek());
        $job->update(['kind' => 'job']);
        $this->internship($company, 'Closed role', now()->subDay());

        $this->get('/')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Welcome')
                ->has('posts', 3)
                ->has('opportunities', 2)
                ->where('opportunities.0.id', $job->id)
                ->where('opportunities.0.kind', 'job')
                ->where('opportunities.1.id', $internship->id)
                ->where('opportunities.1.kind', 'internship')
                ->where('opportunities.1.company.name', 'Atlas Cloud')
                ->missing('companies_count')
                ->missing('students_count')
                ->missing('placements_count'));
    }

    private function company(string $name): Company
    {
        $company = Company::create(['website' => 'https://example.com', 'about' => 'A company used in tests.', 'city_id' => $this->city->id]);
        $company->user()->create(['name' => $name, 'email' => str($name)->slug().'@example.com', 'password' => 'password'])
            ->forceFill(['email_verified_at' => now()])->save();

        return $company->load('user');
    }

    private function student(): User
    {
        $student = Student::create(['field_id' => $this->field->id, 'city_id' => $this->city->id]);

        $user = $student->user()->create(['name' => 'Test Student', 'email' => 'student@example.com', 'password' => 'password']);
        $user->forceFill(['email_verified_at' => now()])->save();

        return $user;
    }

    private function internship(Company $company, string $title, $closingAt): Internship
    {
        return Internship::create([
            'title' => $title,
            'description' => 'Internship used in tests.',
            'company_id' => $company->id,
            'field_id' => $this->field->id,
            'city_id' => $this->city->id,
            'closing_at' => $closingAt,
        ]);
    }
}

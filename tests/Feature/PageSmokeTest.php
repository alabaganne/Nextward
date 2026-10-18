<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Company;
use App\Models\Field;
use App\Models\Internship;
use App\Models\Student;
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

    private function student(): User
    {
        return User::where('email', 'student@example.com')->firstOrFail();
    }

    public function test_public_pages_render(): void
    {
        foreach (['/', '/fr', '/about', '/contact', '/blog', '/fr/about', '/fr/contact', '/fr/blog', '/opportunities', '/opportunities/internships', '/opportunities/jobs', '/login', '/register'] as $uri) {
            $this->get($uri)->assertOk();
        }

        $this->get(\App\Support\Blog::all('en')[0]['path'])->assertOk();

        $internship = Internship::firstOrFail();
        $this->get('/internships')->assertStatus(301)->assertRedirect('/opportunities/internships');
        $this->get("/internships/{$internship->id}")
            ->assertStatus(301)
            ->assertRedirect($internship->public_url);
        $this->get($internship->public_url)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Internships/Show')
                ->has('internship.title')
                ->has('internship.company.name'));
    }

    public function test_the_old_student_and_professional_pages_redirect_to_the_home_page(): void
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

    public function test_workspace_pages_render_for_students(): void
    {
        $student = $this->student();

        foreach (['/dashboard', '/applications', '/likes', '/messages', '/companies', '/profile', '/settings'] as $uri) {
            $this->actingAs($student)->get($uri)->assertOk();
        }

        $application = Application::where('student_id', $student->userable->id)->firstOrFail();
        $this->actingAs($student)
            ->get("/applications/{$application->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Applications/Show')
                ->has('application.internship.title')
                ->has('application.company.name'));

        $company = Company::firstOrFail();
        $this->actingAs($student)->get("/companies/{$company->id}")->assertOk();
    }

    public function test_dashboard_receives_real_props(): void
    {
        $this->actingAs($this->student())
            ->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('internships_count', Internship::open()->count())
                ->has('applications'));
    }

    public function test_dashboard_count_and_application_parties_match_workspace_data(): void
    {
        $application = Application::firstOrFail();
        Internship::firstOrFail()->update(['status' => 'closed']);

        foreach ([$application->student->user, $application->company->user, User::where('email', 'admin@example.com')->firstOrFail()] as $user) {
            $this->actingAs($user)->get('/applications')
                ->assertInertia(fn (Assert $page) => $page
                    ->has('applications.data.0.company.id')
                    ->has('applications.data.0.company.name')
                    ->has('applications.data.0.student.id')
                    ->has('applications.data.0.student.name'));

            $this->get('/dashboard')
                ->assertInertia(fn (Assert $page) => $page
                    ->where('internships_count', Internship::open()->count()));
        }
    }

    public function test_dashboard_interview_counts_include_older_applications_and_exclude_other_accounts(): void
    {
        $student = $this->student();
        $internship = Internship::firstOrFail();
        $otherInternship = Internship::where('company_id', '<>', $internship->company_id)->firstOrFail();
        $otherStudent = Student::where('id', '<>', $student->userable_id)->firstOrFail();
        Application::query()->delete();

        foreach (['interview', 'interview', 'submitted', 'submitted', 'submitted', 'submitted'] as $index => $status) {
            Application::create([
                'student_id' => $student->userable_id,
                'company_id' => $internship->company_id,
                'internship_id' => $internship->id,
                'cover_letter' => 'Application used to verify dashboard totals.',
                'status' => $status,
            ])->forceFill(['created_at' => now()->subDays(6 - $index)])->save();
        }
        Application::create([
            'student_id' => $otherStudent->id,
            'company_id' => $otherInternship->company_id,
            'internship_id' => $otherInternship->id,
            'cover_letter' => 'Application belonging to other accounts.',
            'status' => 'interview',
        ]);

        foreach ([$student, $internship->company->user] as $user) {
            $this->actingAs($user)->get('/dashboard')
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
        $this->actingAs($this->student())
            ->get('/opportunities')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Internships/Index')
                ->has('internships.data')
                ->has('fields')
                ->has('cities'));
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

        $this->actingAs($admin)->get('/students')->assertOk();

        foreach ([$company, $admin] as $user) {
            foreach (['/dashboard', '/opportunities', '/applications', '/messages', '/companies', '/profile', '/settings'] as $uri) {
                $this->actingAs($user)->get($uri)->assertOk();
            }
        }
    }

    public function test_company_role_management_pages_receive_their_form_data(): void
    {
        $user = User::where('email', 'company@example.com')->firstOrFail();
        $internship = Internship::where('company_id', $user->userable_id)->firstOrFail();

        $this->actingAs($user)->get(route('internships.edit', $internship))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Internships/Edit')
                ->where('internship.id', $internship->id)
                ->has('internship.work_type')
                ->has('fields.0.id'));
        $this->get(route('internships.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Internships/Edit')
                ->has('fields.0.name'));
        $this->get(route('internships.applications.index', $internship))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Internships/Applications')
                ->where('internship.id', $internship->id)
                ->has('applications.data.0.student.name'));
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
        $student = Student::firstOrFail();
        $internship = Internship::firstOrFail();
        $field = Field::firstOrFail();
        $this->actingAs($admin);

        foreach ([
            'companies.index' => 'Companies/Index',
            'students.index' => 'Students/Index',
            'skills.index' => 'Skills/Index',
            'fields.index' => 'Fields/Index',
            'fields.create' => 'Fields/Edit',
        ] as $route => $component) {
            $this->get(route($route))->assertOk()
                ->assertInertia(fn (Assert $page) => $page->component($component));
        }

        foreach ([
            ['companies.edit', $company, 'Companies/Edit', 'company'],
            ['students.edit', $student, 'Students/Edit', 'student'],
            ['internships.edit', $internship, 'Internships/Edit', 'internship'],
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
        $this->get(route('students.create'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Students/Edit')
                ->has('fields.0.id')
                ->has('cities.0.id'));

        // Posting requires a company profile so that ownership and city are known.
        $this->get(route('internships.create'))->assertForbidden();
    }

    public function test_application_detail_contact_data_is_available_to_both_parties_and_admins(): void
    {
        $application = Application::firstOrFail();
        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        foreach ([$application->student->user, $application->company->user, $admin] as $user) {
            $this->actingAs($user)->get(route('applications.show', $application))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('Applications/Show')
                    ->where('application.company.id', $application->company_id)
                    ->where('application.company.user_id', $application->company->user->id)
                    ->where('application.company.name', $application->company->user->name)
                    ->where('application.student.id', $application->student_id)
                    ->where('application.student.user_id', $application->student->user->id)
                    ->where('application.student.name', $application->student->user->name)
                    ->has('application.internship.field.name')
                    ->has('application.internship.city.name')
                    ->has('application.attachments'));
        }
    }
}

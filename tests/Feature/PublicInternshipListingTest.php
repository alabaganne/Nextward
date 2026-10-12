<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Company;
use App\Models\Field;
use App\Models\Internship;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $company = $this->company('Atlas Cloud');
        $this->internship($company, 'Closes tomorrow', now()->addDay());
        $this->internship($company, 'Closes today', today());
        $this->internship($company, 'Closed yesterday', now()->subDay());

        $this->get('/internships')->assertInertia(fn (Assert $page) => $page
            ->component('Internships/Index')
            ->has('internships.data', 2)
            ->where('internships.data.0.is_open', true)
            ->where('internships.data.1.is_open', true)
            ->where('fields.0.internships_count', 2)
            ->where('companies.0.internships_count', 2)
        );
    }

    public function test_the_board_is_empty_when_nothing_is_open()
    {
        $this->internship($this->company('Atlas Cloud'), 'Closed last month', now()->subMonth());

        $this->get('/internships')->assertInertia(fn (Assert $page) => $page
            ->has('internships.data', 0)
            ->where('fields.0.internships_count', 0)
            ->where('companies.0.internships_count', 0)
        );
    }

    public function test_companies_still_see_their_own_closed_internships()
    {
        $company = $this->company('Atlas Cloud');
        $this->internship($company, 'Our closed role', now()->subDay());
        $this->internship($this->company('Other Co'), 'Their closed role', now()->subDay());

        $this->actingAs($company->user)->get('/internships')->assertInertia(fn (Assert $page) => $page
            ->has('internships.data', 1)
            ->where('internships.data.0.title', 'Our closed role')
            ->where('internships.data.0.is_open', false)
        );
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

        $this->get(route('internships.show', $closed))->assertInertia(fn (Assert $page) => $page
            ->component('Internships/Show')
            ->where('internship.is_open', false)
        );
    }

    public function test_sitemap_skips_closed_internships()
    {
        $company = $this->company('Atlas Cloud');
        $open = $this->internship($company, 'Open role', now()->addWeek());
        $closed = $this->internship($company, 'Closed role', now()->subDay());

        $this->get('/sitemap.xml')
            ->assertSee('/internships/'.$open->id.'<', false)
            ->assertDontSee('/internships/'.$closed->id.'<', false);
    }

    public function test_home_page_does_not_turn_records_into_social_proof()
    {
        $this->internship($this->company('Atlas Cloud'), 'Software Engineering Intern', now()->addWeek());

        $this->get('/')
            ->assertInertia(fn (Assert $page) => $page->component('Welcome')->has('posts', 3))
            ->assertDontSee('Atlas Cloud')
            ->assertDontSee('Software Engineering Intern');
    }

    private function company(string $name): Company
    {
        $company = Company::create(['website' => 'https://example.com', 'about' => 'A company used in tests.', 'city_id' => $this->city->id]);
        $company->user()->create(['name' => $name, 'email' => str($name)->slug().'@example.com', 'password' => 'password']);

        return $company->load('user');
    }

    private function student(): User
    {
        $student = Student::create(['field_id' => $this->field->id, 'city_id' => $this->city->id]);

        return $student->user()->create(['name' => 'Test Student', 'email' => 'student@example.com', 'password' => 'password']);
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

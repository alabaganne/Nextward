<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Opportunity;
use App\Models\SavedSearch;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AppPagesTest extends TestCase
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

    public function test_signed_in_pages_live_under_app_and_need_an_account()
    {
        foreach (['/app', '/app/dashboard', '/app/opportunities', '/app/companies', '/app/profile'] as $uri) {
            $this->get($uri)->assertRedirect('/login');
        }
        $this->actingAs($this->candidate())->get('/app')->assertRedirect('/app/dashboard');
        $this->actingAs($this->candidate())->get('/login')->assertRedirect('/app/dashboard');
    }

    public function test_opportunities_open_inside_app_without_being_indexed()
    {
        $role = Opportunity::available()->firstOrFail();
        $segment = basename(parse_url($role->public_url, PHP_URL_PATH));

        $this->actingAs($this->candidate())->get('/app/opportunities?search=a')->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertInertia(fn (Assert $page) => $page->component('Opportunities/Index')->where('seo.robots', 'noindex, nofollow')->where('seo.alternates', []));
        $this->actingAs($this->candidate())->get("/app/opportunities/{$segment}")->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Opportunities/Show')
                ->where('seo.robots', 'noindex, nofollow')
                ->where('seo.canonical', $role->public_url)
                ->where('translation', null));
        $this->actingAs($this->candidate())->get("/app/opportunities/{$role->id}")->assertStatus(301)->assertRedirect("/app/opportunities/{$segment}");
    }

    public function test_public_pages_stay_public_for_signed_in_users()
    {
        $this->actingAs($this->candidate())->get('/opportunities')->assertOk()
            ->assertHeaderMissing('X-Robots-Tag')
            ->assertInertia(fn (Assert $page) => $page->component('Opportunities/Index')->where('seo.robots', fn ($robots) => str_starts_with($robots, 'index')));
        $this->actingAs($this->candidate())->get('/companies')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Companies/Directory'));
    }

    public function test_old_workspace_links_lead_to_app()
    {
        $user = $this->candidate();
        $company = Company::firstOrFail();

        foreach ([
            '/dashboard' => '/app/dashboard',
            '/applications/5?tab=notes' => '/app/applications/5?tab=notes',
            '/billing/return/konnect?payment_ref=abc' => '/app/billing/return/konnect?payment_ref=abc',
            '/admin/imports/review' => '/app/admin/imports/review',
            '/candidates/manage' => '/app/candidates/manage',
            '/candidates/12/edit' => '/app/candidates/12/edit',
            "/companies/{$company->id}" => "/app/companies/{$company->id}",
            '/opportunities/create' => '/app/opportunities/create',
            '/opportunities/7/apply' => '/app/opportunities/7/apply',
            '/saved' => '/app/likes',
        ] as $old => $new) {
            $this->actingAs($user)->get($old)->assertStatus(301)->assertRedirect($new);
        }
        $this->get($company->public_url)->assertOk()->assertInertia(fn (Assert $page) => $page->component('Companies/Public'));
    }

    public function test_saved_searches_keep_skills_and_open_inside_app()
    {
        $skill = Skill::firstOrCreate(['name' => 'React']);
        $user = $this->candidate();

        $this->actingAs($user)->post('/app/saved-searches', [
            'name' => 'React roles', 'filters' => ['skills' => [(string) $skill->id], 'kind' => 'job'], 'frequency' => 'off', 'timezone' => 'Africa/Tunis',
        ])->assertRedirect(route('saved-searches.index'));

        $search = SavedSearch::where('user_id', $user->id)->latest('id')->firstOrFail();
        $this->assertEquals([$skill->id], $search->filters['skills']);
        $this->actingAs($user)->get('/app/saved-searches')->assertInertia(fn (Assert $page) => $page
            ->where('searches', fn ($searches) => collect($searches)->firstWhere('name', 'React roles')['browse_url'] === '/app/opportunities?kind=job&skills%5B0%5D='.$skill->id));
    }
}

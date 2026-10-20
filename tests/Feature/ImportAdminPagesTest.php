<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\ImportSource;
use App\Models\OpportunitySource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ImportAdminPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Tests\Fixtures\DemoSeeder::class);
    }

    private function admin(): User
    {
        return User::where('email', 'admin@example.com')->firstOrFail();
    }

    public function test_each_imports_page_has_its_own_screen(): void
    {
        $admin = $this->admin();
        $source = ImportSource::first();
        $listing = OpportunitySource::whereNotNull('opportunity_id')->value('opportunity_id');

        $this->actingAs($admin)->get('/app/admin/imports')->assertRedirect(route('imports.review'));
        foreach ([
            route('imports.sources.index') => 'Imports/Sources',
            route('imports.sources.create') => 'Imports/SourceForm',
            route('imports.sources.edit', $source) => 'Imports/SourceForm',
            route('imports.review') => 'Imports/Review',
            route('imports.reports') => 'Imports/Reports',
            route('imports.claims') => 'Imports/Claims',
            route('imports.runs') => 'Imports/Runs',
            route('imports.opportunities.show', $listing) => 'Imports/Moderate',
        ] as $url => $component) {
            $this->actingAs($admin)->get($url)->assertOk()->assertInertia(fn (Assert $page) => $page->component($component));
        }
    }

    public function test_the_review_queue_starts_with_listings_waiting_for_review(): void
    {
        $this->actingAs($this->admin())->get(route('imports.review'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('filters.state', 'review')
                ->has('items.data', OpportunitySource::where('state', 'review')->count())
                ->where('items.data.0.state', 'review')
                ->where('counts.review', OpportunitySource::where('state', 'review')->count()));

        $this->actingAs($this->admin())->get(route('imports.review', ['state' => 'all']))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('items.total', OpportunitySource::count()));
    }

    public function test_saving_a_source_from_its_edit_page_goes_back_to_sources(): void
    {
        $source = ImportSource::first();
        $data = $source->only(['key', 'name', 'provider', 'url', 'allowed_domains', 'permission_status', 'permission_notes', 'publication_mode', 'enabled', 'company_id', 'cadence_hours', 'request_delay_ms', 'max_requests', 'settings']);

        $this->actingAs($this->admin())->from(route('imports.sources.edit', $source))
            ->put(route('imports.sources.update', $source), ['name' => 'Renamed board'] + $data)
            ->assertRedirect(route('imports.sources.index'));
        $this->assertSame('Renamed board', $source->fresh()->name);

        $this->actingAs($this->admin())->from(route('imports.sources.index'))
            ->put(route('imports.sources.update', $source), ['enabled' => ! $source->enabled] + $data)
            ->assertRedirect(route('imports.sources.index'));
    }

    public function test_admins_see_what_is_waiting_in_the_sidebar(): void
    {
        $listing = OpportunitySource::whereNotNull('opportunity_id')->value('opportunity_id');
        DB::table('opportunity_reports')->insert(['opportunity_id' => $listing, 'reason' => 'closed', 'details' => 'The role is filled.', 'status' => 'open', 'created_at' => now(), 'updated_at' => now()]);

        $this->actingAs($this->admin())->get(route('imports.runs'))
            ->assertInertia(fn (Assert $page) => $page->where('badges.imports_review', OpportunitySource::where('state', 'review')->count())
                ->where('badges.reports', DB::table('opportunity_reports')->where('status', 'open')->count())
                ->where('badges.claims', DB::table('company_claims')->where('status', 'pending')->count()));
    }

    public function test_only_admins_can_open_imports_pages(): void
    {
        $this->get(route('imports.sources.index'))->assertRedirect('/login');

        $candidate = Candidate::create()->user()->create(['name' => 'Cand', 'email' => 'cand@example.com', 'password' => 'secret-password']);
        foreach (['imports.sources.index', 'imports.review', 'imports.reports', 'imports.claims', 'imports.runs'] as $name) {
            $this->actingAs($candidate)->get(route($name))->assertForbidden();
        }
    }
}

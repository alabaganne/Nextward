<?php

namespace Tests\Feature;

use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OpportunityActivityTest extends TestCase
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

    public function test_candidate_click_is_saved_once(): void
    {
        $candidate = $this->candidate();
        $opportunity = Opportunity::firstOrFail();

        $this->actingAs($candidate)->postJson("/app/opportunities/{$opportunity->id}/opened")->assertNoContent();
        $this->travel(5)->minutes();
        $this->actingAs($candidate)->postJson("/app/opportunities/{$opportunity->id}/opened")->assertNoContent();

        $rows = DB::table('opportunity_views')->where('candidate_id', $candidate->userable_id)->get();
        $this->assertCount(1, $rows);
        $this->assertSame(now()->format('Y-m-d H:i'), substr($rows[0]->viewed_at, 0, 16));
    }

    public function test_companies_and_guests_are_not_tracked(): void
    {
        $opportunity = Opportunity::firstOrFail();
        $company = User::where('email', 'company@example.com')->firstOrFail();

        $this->actingAs($company)->postJson("/app/opportunities/{$opportunity->id}/opened")->assertForbidden();
        $this->actingAs($company)->postJson('/app/activity/sync', ['views' => []])->assertForbidden();
        auth()->logout();
        $this->postJson("/app/opportunities/{$opportunity->id}/opened")->assertUnauthorized();
        $this->assertDatabaseCount('opportunity_views', 0);
    }

    public function test_sync_moves_guest_data_into_the_account_and_returns_everything(): void
    {
        $candidate = $this->candidate();
        $candidateId = $candidate->userable_id;
        [$viewed, $alreadyViewed] = Opportunity::where('publication_status', 'published')->take(2)->pluck('id');
        DB::table('opportunity_views')->insert(['candidate_id' => $candidateId, 'opportunity_id' => $alreadyViewed, 'viewed_at' => now()]);
        $alreadySaved = DB::table('likes')->where('candidate_id', $candidateId)->value('opportunity_id');
        $newSave = Opportunity::where('publication_status', 'published')->whereNull('merged_into_id')
            ->whereNotIn('id', DB::table('likes')->where('candidate_id', $candidateId)->pluck('opportunity_id'))->value('id');

        $response = $this->actingAs($candidate)->postJson('/app/activity/sync', [
            'views' => [$viewed => '2026-01-02T10:00:00Z', $alreadyViewed => '2020-01-01T00:00:00Z', 999999 => '2026-01-02T10:00:00Z'],
            'saves' => array_values(array_filter([$alreadySaved, $newSave, 999999])),
        ])->assertOk()->assertJsonPath('saves_added', 1);

        $views = $response->json('views');
        $this->assertEqualsCanonicalizing([$viewed, $alreadyViewed], array_keys($views));
        $this->assertStringStartsWith('2026-01-02T10:00:00', $views[$viewed]);
        $this->assertStringStartsWith(now()->format('Y-m-d'), $views[$alreadyViewed]);
        $this->assertContains($newSave, $response->json('saves'));
        $this->assertSame(1, DB::table('likes')->where(['candidate_id' => $candidateId, 'opportunity_id' => $newSave])->count());
        $this->assertDatabaseMissing('opportunity_views', ['opportunity_id' => 999999]);
    }

    public function test_sync_with_nothing_returns_the_account_data(): void
    {
        $candidate = $this->candidate();
        $opportunity = Opportunity::firstOrFail();
        DB::table('opportunity_views')->insert(['candidate_id' => $candidate->userable_id, 'opportunity_id' => $opportunity->id, 'viewed_at' => now()]);

        $this->actingAs($candidate)->postJson('/app/activity/sync', [])
            ->assertOk()
            ->assertJsonPath('saves_added', 0)
            ->assertJsonStructure(['views' => [$opportunity->id], 'saves']);
    }

    public function test_sync_clamps_future_dates_and_limits_size(): void
    {
        $candidate = $this->candidate();
        $opportunity = Opportunity::firstOrFail();

        $this->actingAs($candidate)->postJson('/app/activity/sync', ['views' => [$opportunity->id => '2099-01-01T00:00:00Z']])->assertOk();
        $this->assertStringStartsWith(now()->format('Y-m-d'), DB::table('opportunity_views')->value('viewed_at'));

        $this->actingAs($candidate)->postJson('/app/activity/sync', ['saves' => range(1, 501)])->assertUnprocessable();
    }
}

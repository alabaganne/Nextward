<?php

namespace Tests\Feature;

use App\Models\Internship;
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

    private function student(): User
    {
        return User::where('email', 'student@example.com')->firstOrFail();
    }

    public function test_candidate_click_is_saved_once(): void
    {
        $student = $this->student();
        $internship = Internship::firstOrFail();

        $this->actingAs($student)->postJson("/opportunities/{$internship->id}/opened")->assertNoContent();
        $this->travel(5)->minutes();
        $this->actingAs($student)->postJson("/opportunities/{$internship->id}/opened")->assertNoContent();

        $rows = DB::table('opportunity_views')->where('student_id', $student->userable_id)->get();
        $this->assertCount(1, $rows);
        $this->assertSame(now()->format('Y-m-d H:i'), substr($rows[0]->viewed_at, 0, 16));
    }

    public function test_companies_and_guests_are_not_tracked(): void
    {
        $internship = Internship::firstOrFail();
        $company = User::where('email', 'company@example.com')->firstOrFail();

        $this->actingAs($company)->postJson("/opportunities/{$internship->id}/opened")->assertForbidden();
        $this->actingAs($company)->postJson('/activity/sync', ['views' => []])->assertForbidden();
        auth()->logout();
        $this->postJson("/opportunities/{$internship->id}/opened")->assertUnauthorized();
        $this->assertDatabaseCount('opportunity_views', 0);
    }

    public function test_sync_moves_guest_data_into_the_account_and_returns_everything(): void
    {
        $student = $this->student();
        $studentId = $student->userable_id;
        [$viewed, $alreadyViewed] = Internship::where('publication_status', 'published')->take(2)->pluck('id');
        DB::table('opportunity_views')->insert(['student_id' => $studentId, 'internship_id' => $alreadyViewed, 'viewed_at' => now()]);
        $alreadySaved = DB::table('likes')->where('student_id', $studentId)->value('internship_id');
        $newSave = Internship::where('publication_status', 'published')->whereNull('merged_into_id')
            ->whereNotIn('id', DB::table('likes')->where('student_id', $studentId)->pluck('internship_id'))->value('id');

        $response = $this->actingAs($student)->postJson('/activity/sync', [
            'views' => [$viewed => '2026-01-02T10:00:00Z', $alreadyViewed => '2020-01-01T00:00:00Z', 999999 => '2026-01-02T10:00:00Z'],
            'saves' => array_values(array_filter([$alreadySaved, $newSave, 999999])),
        ])->assertOk()->assertJsonPath('saves_added', 1);

        $views = $response->json('views');
        $this->assertEqualsCanonicalizing([$viewed, $alreadyViewed], array_keys($views));
        $this->assertStringStartsWith('2026-01-02T10:00:00', $views[$viewed]);
        $this->assertStringStartsWith(now()->format('Y-m-d'), $views[$alreadyViewed]);
        $this->assertContains($newSave, $response->json('saves'));
        $this->assertSame(1, DB::table('likes')->where(['student_id' => $studentId, 'internship_id' => $newSave])->count());
        $this->assertDatabaseMissing('opportunity_views', ['internship_id' => 999999]);
    }

    public function test_sync_with_nothing_returns_the_account_data(): void
    {
        $student = $this->student();
        $internship = Internship::firstOrFail();
        DB::table('opportunity_views')->insert(['student_id' => $student->userable_id, 'internship_id' => $internship->id, 'viewed_at' => now()]);

        $this->actingAs($student)->postJson('/activity/sync', [])
            ->assertOk()
            ->assertJsonPath('saves_added', 0)
            ->assertJsonStructure(['views' => [$internship->id], 'saves']);
    }

    public function test_sync_clamps_future_dates_and_limits_size(): void
    {
        $student = $this->student();
        $internship = Internship::firstOrFail();

        $this->actingAs($student)->postJson('/activity/sync', ['views' => [$internship->id => '2099-01-01T00:00:00Z']])->assertOk();
        $this->assertStringStartsWith(now()->format('Y-m-d'), DB::table('opportunity_views')->value('viewed_at'));

        $this->actingAs($student)->postJson('/activity/sync', ['saves' => range(1, 501)])->assertUnprocessable();
    }
}

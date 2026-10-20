<?php

namespace Tests\Feature;

use App\Http\Resources\NotificationResource;
use App\Models\Application;
use App\Models\User;
use App\Notifications\ApplicationReviewed;
use App\Notifications\ApplicationSubmitted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Tests\Fixtures\DemoSeeder::class);
    }

    public function test_opening_a_notification_marks_it_read_and_follows_its_action(): void
    {
        $candidate = User::where('email', 'student@example.com')->first();
        $notification = $candidate->notifications()->whereNull('read_at')->first();

        $this->actingAs($candidate)
            ->post(route('notifications.store', $notification->id))
            ->assertRedirect($notification->data['action']);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_users_cannot_touch_someone_elses_notifications(): void
    {
        $candidate = User::where('email', 'student@example.com')->first();
        $company = User::where('email', 'company@example.com')->first();
        $notification = $candidate->notifications()->whereNull('read_at')->first();

        $this->actingAs($company)
            ->post(route('notifications.store', $notification->id))
            ->assertForbidden();

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_clear_deletes_only_the_current_users_notifications(): void
    {
        $candidate = User::where('email', 'student@example.com')->first();
        $company = User::where('email', 'company@example.com')->first();

        $this->actingAs($candidate)->delete(route('notifications.clear'))->assertRedirect();

        $this->assertSame(0, $candidate->notifications()->count());
        $this->assertGreaterThan(0, $company->notifications()->count());
    }

    public function test_a_get_request_cannot_clear_notifications(): void
    {
        $candidate = User::where('email', 'student@example.com')->first();
        $count = $candidate->notifications()->count();

        $this->actingAs($candidate)->get('/app/notifications/clear')->assertStatus(405);

        $this->assertGreaterThan(0, $count);
        $this->assertSame($count, $candidate->notifications()->count());
    }

    public function test_notification_bodies_escape_user_supplied_names_and_titles(): void
    {
        $application = Application::firstOrFail();
        $application->candidate->user->update(['name' => '<img src=x onerror=alert(1)>']);
        $application->company->user->update(['name' => '<b>Company</b>']);
        $application->opportunity->update(['title' => '<script>alert(1)</script>']);

        foreach ([new ApplicationSubmitted($application), new ApplicationReviewed($application)] as $notification) {
            $body = $notification->toArray(null)['body'];

            $this->assertStringNotContainsString('<img', $body);
            $this->assertStringNotContainsString('<b>', $body);
            $this->assertStringNotContainsString('<script>', $body);
            $this->assertStringContainsString('&lt;script&gt;', $body);
        }
    }

    public function test_historical_notification_html_is_exposed_as_plain_text_without_changing_storage(): void
    {
        $candidate = User::where('email', 'student@example.com')->firstOrFail();
        $notification = $candidate->notifications()->firstOrFail();
        $body = '<span>Old &amp; Co</span> <script>alert(1)</script><img src=x onerror=alert(2)> sent a reply.';
        $notification->update(['data' => [...$notification->data, 'body' => $body]]);

        $data = (new NotificationResource($notification))->resolve(request());

        $this->assertSame('Old & Co alert(1) sent a reply.', $data['data']['body_text']);
        $this->assertSame($body, $data['data']['body']);
        $this->assertSame($body, $notification->fresh()->data['body']);
        $this->assertArrayNotHasKey('body_text', $notification->fresh()->data);
    }
}

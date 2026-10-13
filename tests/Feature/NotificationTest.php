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
        $student = User::where('email', 'student@example.com')->first();
        $notification = $student->notifications()->whereNull('read_at')->first();

        $this->actingAs($student)
            ->post(route('notifications.store', $notification->id))
            ->assertRedirect($notification->data['action']);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_users_cannot_touch_someone_elses_notifications(): void
    {
        $student = User::where('email', 'student@example.com')->first();
        $company = User::where('email', 'company@example.com')->first();
        $notification = $student->notifications()->whereNull('read_at')->first();

        $this->actingAs($company)
            ->post(route('notifications.store', $notification->id))
            ->assertForbidden();

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_clear_deletes_only_the_current_users_notifications(): void
    {
        $student = User::where('email', 'student@example.com')->first();
        $company = User::where('email', 'company@example.com')->first();

        $this->actingAs($student)->get(route('notifications.clear'))->assertRedirect();

        $this->assertSame(0, $student->notifications()->count());
        $this->assertGreaterThan(0, $company->notifications()->count());
    }

    public function test_notification_bodies_escape_user_supplied_names_and_titles(): void
    {
        $application = Application::firstOrFail();
        $application->student->user->update(['name' => '<img src=x onerror=alert(1)>']);
        $application->company->user->update(['name' => '<b>Company</b>']);
        $application->internship->update(['title' => '<script>alert(1)</script>']);

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
        $student = User::where('email', 'student@example.com')->firstOrFail();
        $notification = $student->notifications()->firstOrFail();
        $body = '<span>Old &amp; Co</span> <script>alert(1)</script><img src=x onerror=alert(2)> sent a reply.';
        $notification->update(['data' => [...$notification->data, 'body' => $body]]);

        $data = (new NotificationResource($notification))->resolve(request());

        $this->assertSame('Old & Co alert(1) sent a reply.', $data['data']['body_text']);
        $this->assertSame($body, $data['data']['body']);
        $this->assertSame($body, $notification->fresh()->data['body']);
        $this->assertArrayNotHasKey('body_text', $notification->fresh()->data);
    }
}

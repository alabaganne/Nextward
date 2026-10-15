<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WorkspaceTest extends TestCase
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

    public function test_companies_list_includes_employers_without_an_account(): void
    {
        $imported = Company::doesntHave('user')->firstOrFail();

        $this->actingAs($this->student())->get('/companies')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Companies/Index')
                ->where('companies.data', fn ($companies) => collect($companies)->contains(fn ($company) => $company['id'] === $imported->id && $company['user_id'] === null)));
    }

    public function test_companies_can_be_searched_by_name(): void
    {
        $company = Company::has('user')->firstOrFail();

        $this->actingAs($this->student())->get('/companies?search='.urlencode($company->display_name))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.search', $company->display_name)
                ->where('companies.data.0.id', $company->id));
    }

    public function test_opening_a_conversation_marks_its_messages_read(): void
    {
        $student = $this->student();
        $sender = Company::has('user')->firstOrFail()->user;
        Message::create(['from_id' => $sender->id, 'to_id' => $student->id, 'text' => 'Hello']);

        $this->actingAs($student)->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page->where('badges.messages', fn ($count) => $count > 0));

        $this->actingAs($student)->get('/messages?user_id='.$sender->id)->assertOk();

        $this->assertSame(0, Message::where('from_id', $sender->id)->where('to_id', $student->id)->whereNull('read_at')->count());
    }

    public function test_signed_in_users_get_the_dashboard_counts(): void
    {
        $this->actingAs($this->student())->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('saved_count')
                ->has('applications_count'));
    }

    public function test_missing_pages_show_the_error_page_when_debug_is_off(): void
    {
        config(['app.debug' => false]);

        $this->get('/this-page-does-not-exist')
            ->assertNotFound()
            ->assertInertia(fn (Assert $page) => $page->component('Error')->where('status', 404));
    }
}

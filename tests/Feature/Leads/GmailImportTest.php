<?php

namespace Tests\Feature\Leads;

use App\Models\Candidate;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class GmailImportTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Candidate $candidate;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-25 06:00:00', 'UTC'));
        config(['services.google.client_id' => 'client-1', 'services.google.client_secret' => 'secret-1', 'services.google.redirect' => null]);
        $this->owner = User::factory()->create(['is_admin' => true]);
        $this->candidate = $this->owner->ensureCandidateProfile();
    }

    public function test_connecting_asks_for_read_only_access_and_keeps_the_token_encrypted(): void
    {
        $response = $this->actingAs($this->owner)->get(route('leads.gmail.connect'));
        $url = $response->headers->get('Location');
        $this->assertStringStartsWith('https://accounts.google.com/o/oauth2/v2/auth?', $url);
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        $this->assertSame(['https://www.googleapis.com/auth/gmail.readonly', 'offline'], [$query['scope'], $query['access_type']]);

        $this->actingAs($this->owner)->get(route('leads.gmail.callback', ['state' => 'wrong', 'code' => 'abc']))->assertRedirect(route('leads.index'));
        $this->assertNull($this->owner->fresh()->gmail_token);

        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'access-1', 'refresh_token' => 'refresh-1', 'scope' => 'https://www.googleapis.com/auth/gmail.readonly', 'expires_in' => 3599]),
            'gmail.googleapis.com/gmail/v1/users/me/profile' => Http::response(['emailAddress' => 'owner@gmail.com']),
        ]);
        $this->actingAs($this->owner)->get(route('leads.gmail.connect'));
        $this->actingAs($this->owner)->get(route('leads.gmail.callback', ['state' => session('gmail_state'), 'code' => 'abc']))->assertRedirect(route('leads.index'));

        $owner = $this->owner->fresh();
        $this->assertSame(['refresh-1', 'owner@gmail.com'], [$owner->gmail_token, $owner->gmail_email]);
        $this->assertNotSame('refresh-1', DB::table('users')->where('id', $owner->id)->value('gmail_token'));
        $this->assertArrayNotHasKey('gmail_token', $owner->toArray());
    }

    public function test_application_emails_become_leads_once_and_a_rejection_updates_the_same_lead(): void
    {
        $this->owner->forceFill(['gmail_token' => 'refresh-1', 'gmail_email' => 'owner@gmail.com'])->save();
        $doist = Lead::create(['candidate_id' => $this->candidate->id, 'company_name' => 'Doist', 'title' => 'Frontend Engineer']);
        $this->fakeGmail([
            'm1' => ['Thank you for applying for the Senior Frontend Engineer position at Vercel', 'Vercel Recruiting <no-reply@greenhouse.io>', 'We will review your application.', '2026-10-23 09:00'],
            'm2' => ['Update on your application to Vercel', 'Vercel <no-reply@greenhouse.io>', 'Unfortunately, we have decided to move forward with other candidates.', '2026-10-24 15:00'],
            'm3' => ['Thanks for applying to Doist!', 'Doist <talent@doist.com>', 'We received your application for the Frontend Engineer role.', '2026-10-24 10:00'],
            'm4' => ['New jobs for you: React Developer', 'LinkedIn Job Alerts <jobalerts-noreply@linkedin.com>', 'Apply now', '2026-10-24 11:00'],
            'm5' => ['Ala, your application was sent to Acme', 'LinkedIn <jobs-noreply@linkedin.com>', 'Your application was sent to Acme', '2026-10-24 12:00'],
        ]);

        $this->artisan('leads:gmail')->expectsOutputToContain('5 emails, 1 added, 2 merged, 2 not about an application')->assertSuccessful();

        $vercel = Lead::where('company_name', 'Vercel')->sole();
        $this->assertSame(['gmail', 'm1', 'rejected', '2026-10-23'], [$vercel->origin, $vercel->external_ref, $vercel->status, $vercel->applied_at->toDateString()]);
        $this->assertStringContainsString('From Gmail: Update on your application to Vercel', $vercel->notes);
        $this->assertSame(['applied', '2026-10-24'], [$doist->fresh()->status, $doist->fresh()->applied_at->toDateString()]);
        $this->assertSame(2, Lead::count());
        $this->assertNotNull($this->owner->fresh()->gmail_synced_at);

        $this->artisan('leads:gmail')->assertSuccessful();
        $this->assertSame(2, Lead::count());
        $this->assertSame(2, substr_count($vercel->fresh()->notes, 'From Gmail:'));
    }

    public function test_the_ai_reads_emails_the_patterns_could_not(): void
    {
        config(['importer.ai.enabled' => true, 'importer.ai.provider' => 'openai', 'ai.providers.openai.key' => 'test-key']);
        $this->owner->forceFill(['gmail_token' => 'refresh-1'])->save();
        $this->fakeGmail(['m5' => ['Ala, your application was sent to Acme', 'LinkedIn <jobs-noreply@linkedin.com>', 'Your application was sent to Acme Senior Frontend Engineer Acme Remote', '2026-10-24 12:00']], [
            'api.openai.com/v1/responses' => Http::response(['id' => 'resp_1', 'object' => 'response', 'status' => 'completed', 'model' => 'gpt-5.4-mini',
                'output' => [['type' => 'message', 'id' => 'msg_1', 'role' => 'assistant', 'status' => 'completed', 'content' => [['type' => 'output_text', 'annotations' => [],
                    'text' => json_encode(['emails' => [['id' => 'm5', 'kind' => 'applied', 'company' => 'Acme', 'role' => 'Senior Frontend Engineer']]])]]]],
                'usage' => ['input_tokens' => 10, 'output_tokens' => 10, 'total_tokens' => 20]]),
        ]);

        $this->artisan('leads:gmail')->assertSuccessful();

        $lead = Lead::sole();
        $this->assertSame(['Acme', 'Senior Frontend Engineer', 'applied'], [$lead->company_name, $lead->title, $lead->status]);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'api.openai.com') && ! str_contains($request->body(), 'refresh-1'));
    }

    public function test_disconnecting_revokes_the_token_and_only_admins_can_connect(): void
    {
        Http::fake(['oauth2.googleapis.com/revoke' => Http::response([])]);
        $this->owner->forceFill(['gmail_token' => 'refresh-1', 'gmail_email' => 'owner@gmail.com'])->save();

        $this->actingAs($this->owner)->delete(route('leads.gmail.disconnect'))->assertRedirect(route('leads.index'));

        $this->assertNull($this->owner->fresh()->gmail_token);
        Http::assertSent(fn ($request) => str_contains($request->url(), '/revoke') && $request['token'] === 'refresh-1');
        $this->actingAs(User::factory()->create())->get(route('leads.gmail.connect'))->assertForbidden();
    }

    private function fakeGmail(array $emails, array $more = []): void
    {
        $api = 'gmail.googleapis.com/gmail/v1/users/me';
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'access-1', 'expires_in' => 3599]),
            $api.'/messages/*' => fn ($request) => Http::response(['id' => $id = Str::between($request->url(), 'messages/', '?'), 'snippet' => $emails[$id][2],
                'internalDate' => (string) Carbon::parse($emails[$id][3], 'UTC')->getTimestampMs(),
                'payload' => ['headers' => [['name' => 'Subject', 'value' => $emails[$id][0]], ['name' => 'From', 'value' => $emails[$id][1]]]]]),
            $api.'/messages*' => Http::response(['messages' => array_map(fn ($id) => ['id' => $id, 'threadId' => $id], array_keys($emails))]),
            ...$more,
        ]);
    }
}

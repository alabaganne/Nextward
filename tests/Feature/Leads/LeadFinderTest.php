<?php

namespace Tests\Feature\Leads;

use App\Importing\OpportunityImporter;
use App\Importing\SourceHttp;
use App\Leads\LeadFinder;
use App\Leads\LeadImport;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\Field;
use App\Models\ImportSource;
use App\Models\Lead;
use App\Models\LeadContact;
use App\Models\LeadSkip;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LeadFinderTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Candidate $candidate;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-25 06:00:00', 'UTC'));
        SourceHttp::$resolveUsing = fn () => ['93.184.216.34'];
        Field::create(['name' => 'Web Development']);
        config(['importer.scope' => null]);
        $this->owner = User::factory()->create(['is_admin' => true]);
        $this->candidate = $this->owner->ensureCandidateProfile();
    }

    protected function tearDown(): void
    {
        SourceHttp::$resolveUsing = null;
        parent::tearDown();
    }

    public function test_the_same_job_on_himalayas_and_ashby_is_one_lead_with_the_ashby_date(): void
    {
        $job = 'https://jobs.ashbyhq.com/omega/0b5f1a2c-1111-2222-3333-444455556666';
        $text = 'We hire remotely across EMEA. You will build our React and TypeScript dashboard with Next.js. '.str_repeat('You will ship product features with a small team. ', 3);
        Http::fake([
            '*/robots.txt' => Http::response('', 404),
            'api.ashbyhq.com/*' => Http::response(['jobs' => [['id' => '0b5f1a2c-1111-2222-3333-444455556666', 'title' => 'Senior Frontend Engineer', 'location' => 'Remote',
                'workplaceType' => 'Remote', 'publishedAt' => now()->subDays(5)->toIso8601String(), 'jobUrl' => $job, 'applyUrl' => $job.'/application', 'descriptionPlain' => $text]]]),
            'himalayas.app/*' => Http::response(['nextCursor' => null, 'jobs' => [['title' => 'Senior Frontend Engineer', 'companyName' => 'Omega Inc.', 'locationRestrictions' => [],
                'description' => '<p>'.$text.' Apply here: <a href="'.$job.'">'.$job.'</a></p>', 'pubDate' => now()->subHours(3)->timestamp, 'employmentType' => 'Full Time',
                'applicationLink' => 'https://himalayas.app/companies/omega/jobs/senior-frontend-engineer', 'guid' => 'https://himalayas.app/companies/omega/jobs/senior-frontend-engineer']]]),
        ]);
        app(OpportunityImporter::class)->run($this->source('ashby-omega', 'ashby', 'https://api.ashbyhq.com/posting-api/job-board/omega', ['api.ashbyhq.com', 'jobs.ashbyhq.com'], 'automatic', ['employer_name' => 'Omega']));
        app(OpportunityImporter::class)->run($this->source('himalayas', 'himalayas', 'https://himalayas.app/jobs/api?limit=20', ['himalayas.app'], 'leads'));

        $stats = app(LeadFinder::class)->run($this->candidate);

        $this->assertSame(1, $stats['added']);
        $lead = Lead::sole();
        $this->assertSame(['Omega', 'Senior Frontend Engineer', 'ats', 'remote'], [$lead->company_name, $lead->title, $lead->date_source, $lead->eligibility]);
        $this->assertSame(now()->subDays(5)->toDateString(), $lead->posted_at->toDateString());
        $this->assertContains('Frontend role', $lead->reasons);
        $this->assertSame(0, LeadSkip::count());
        $this->assertSame(0, app(LeadFinder::class)->run($this->candidate)['added']);
    }

    public function test_reposts_are_left_out_with_the_first_posting_date(): void
    {
        $this->role('Acme', 'Frontend Engineer', ['first_posted_at' => now()->subDays(46), 'reposted_at' => now()->subDay(), 'posted_at' => now()->subDay()]);
        // The board shows today's date, but the employer board first posted it six weeks ago.
        $this->role('Beta', 'React Developer', ['first_posted_at' => now()->subDays(45), 'posted_at' => now(), 'date_source' => 'ats']);

        app(LeadFinder::class)->run($this->candidate);

        $this->assertSame(0, Lead::count());
        $this->assertSame(['repost', 'repost'], LeadSkip::orderBy('id')->pluck('reason')->all());
        $this->assertSame('First posted 9 Sep 2026, posted again 24 Oct 2026.', LeadSkip::where('company_name', 'Acme')->value('detail'));
    }

    public function test_a_worldwide_board_label_with_latam_only_text_is_left_out(): void
    {
        $this->role('Gamma', 'Senior React Developer', ['applicant_scope' => 'anywhere', 'remote_scope' => 'Worldwide',
            'description' => 'Fully remote. This role is only open to candidates based in LATAM. You will build React and TypeScript apps.']);
        $this->role('Delta', 'Frontend Engineer', ['location' => 'Remote (EMEA)', 'description' => 'You will build React and TypeScript apps for our customers.']);

        app(LeadFinder::class)->run($this->candidate);

        $skip = LeadSkip::sole();
        $this->assertSame(['Gamma', 'ineligible'], [$skip->company_name, $skip->reason]);
        $this->assertStringContainsString('only open to candidates based in LATAM', $skip->detail);
        $this->assertSame(['Delta', 'remote'], [Lead::sole()->company_name, Lead::sole()->eligibility]);
    }

    public function test_roles_at_companies_applied_to_through_jobs_json_are_hidden_with_the_applied_badge(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'jobs').'.json';
        file_put_contents($file, json_encode([['id' => 17, 'foundOn' => 'Himalayas', 'kind' => 'startup', 'company' => 'Tether Operations Limited', 'role' => 'Senior Frontend Engineer',
            'url' => 'https://jobs.ashbyhq.com/tether/1d2e3f4a-0000-1111-2222-333344445555', 'posted' => '2026-10-01', 'elig' => 'remote', 'eligLabel' => 'Remote, anywhere',
            'location' => 'Remote', 'pay' => '$90k', 'note' => 'Strong match', 'leadName' => 'Sam Rivera', 'leadTitle' => 'Engineering Manager',
            'leadUrl' => 'https://www.linkedin.com/in/sam-rivera', 'leadNote' => 'Runs the web team', 'mutual' => 'Karim', 'list' => 'Applied 10/10']]));

        $stats = (new LeadImport($this->candidate))->file($file, 'jobs.json');
        $this->role('Tether', 'Frontend Engineer (Remote)');
        $this->role('Tether', 'Backend Engineer', ['description' => 'Node.js, PostgreSQL and React services.']);
        app(LeadFinder::class)->run($this->candidate);

        $this->assertSame(['added' => 1, 'updated' => 0, 'skipped' => 0, 'contacts' => 1], $stats);
        $old = Lead::sole();
        $this->assertSame(['tracker', '17', 'applied', 'remote', 'Strong match'], [$old->origin, $old->external_ref, $old->status, $old->eligibility, $old->notes]);
        $this->assertSame(['Sam Rivera', 'Karim', 'tether'], [LeadContact::sole()->name, LeadContact::sole()->mutual, LeadContact::sole()->company_key]);
        $this->assertEqualsCanonicalizing(['applied', 'cooldown'], LeadSkip::pluck('reason')->all());
        $this->assertSame('You applied to Senior Frontend Engineer on 25 Oct 2026.', LeadSkip::where('reason', 'applied')->value('detail'));

        // Importing the same file again changes nothing.
        $this->assertSame(['added' => 0, 'updated' => 1, 'skipped' => 0, 'contacts' => 0], (new LeadImport($this->candidate))->file($file, 'jobs.json'));
        $this->assertSame(1, Lead::count());

        Lead::sole()->update(['digest_date' => today()]);
        $this->actingAs($this->owner)->get(route('leads.index'))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Leads/Index')->has('hidden', 2)->where('hidden.0.company_name', 'Tether')->has('leads.data.0.contacts', 1));
    }

    public function test_junior_staff_and_8_years_roles_are_left_out_and_the_day_is_capped_at_12(): void
    {
        $this->role('Low', 'Junior Frontend Developer');
        $this->role('High', 'Staff Software Engineer');
        $this->role('Long', 'Senior Frontend Engineer', ['min_years' => 8]);
        $this->role('Ops', 'IT Support Technician', ['description' => 'Help staff with laptops.']);
        foreach (range(1, 14) as $n) { $this->role("Company {$n}", 'Frontend Engineer', ['first_seen_at' => now()->subMinutes($n)]); }

        $stats = app(LeadFinder::class)->run($this->candidate);

        $this->assertSame(['added' => 12, 'skipped' => 6], $stats);
        $this->assertEqualsCanonicalizing(['seniority', 'seniority', 'years', 'low_score', 'cap', 'cap'], LeadSkip::pluck('reason')->all());
        $this->assertSame('Asks for 8+ years of experience.', LeadSkip::where('reason', 'years')->value('detail'));
        // A second run the same day adds nothing; the next day the two left over get their turn.
        $this->assertSame(0, app(LeadFinder::class)->run($this->candidate)['added']);
        $this->assertSame(2, app(LeadFinder::class)->run($this->candidate, today()->addDay())['added']);
    }

    public function test_one_job_can_only_be_saved_once_per_candidate(): void
    {
        Lead::create(['candidate_id' => $this->candidate->id, 'company_name' => 'Notion', 'title' => 'Senior Software Engineer, Frontend']);
        $this->expectException(QueryException::class);
        Lead::create(['candidate_id' => $this->candidate->id, 'company_name' => 'Notion Labs, Inc.', 'title' => 'Software Engineer - Frontend (Remote)', 'origin' => 'manual']);
    }

    public function test_opening_a_lead_never_moves_it_back_and_applying_on_nextward_marks_it_applied(): void
    {
        $applied = Lead::create(['candidate_id' => $this->candidate->id, 'company_name' => 'Acme', 'title' => 'Frontend Engineer', 'url' => 'https://acme.example/jobs/1',
            'status' => 'applied', 'applied_at' => '2026-10-20']);
        $this->actingAs($this->owner)->get(route('leads.open', $applied))->assertRedirect('https://acme.example/jobs/1');
        $this->assertSame(['applied', '2026-10-20'], [$applied->fresh()->status, $applied->fresh()->applied_at->toDateString()]);
        $this->assertNotNull($applied->fresh()->opened_at);

        $role = $this->role('Beta', 'React Developer', ['application_mode' => 'external', 'application_url' => 'https://beta.example/apply']);
        app(LeadFinder::class)->run($this->candidate);
        $lead = Lead::where('opportunity_id', $role->id)->sole();
        $this->actingAs($this->owner)->post(route('opportunities.opened', $role))->assertNoContent();
        $this->assertSame('opened', $lead->fresh()->status);
        $this->actingAs($this->owner)->post(route('applications.track', $role), ['applied_at' => '2026-10-25'])->assertRedirect();
        $this->assertSame(['applied', '2026-10-25'], [$lead->fresh()->status, $lead->fresh()->applied_at->toDateString()]);
        $this->assertNotNull($lead->fresh()->application_id);

        // By hand any status can be set, also an earlier one.
        $this->actingAs($this->owner)->put(route('leads.update', $lead), ['status' => 'to_apply'])->assertRedirect();
        $this->assertSame('to_apply', $lead->fresh()->status);
    }

    public function test_a_linkedin_export_merges_with_leads_for_the_same_job(): void
    {
        Lead::create(['candidate_id' => $this->candidate->id, 'company_name' => 'Notion', 'title' => 'Software Engineer, Frontend', 'status' => 'to_apply']);
        $file = tempnam(sys_get_temp_dir(), 'li');
        file_put_contents($file, "Application Date,Contact Email,Contact Phone Number,Company Name,Job Title,Job Url,Resume Name,Question And Answers\n"
            ."\"10/21/26, 3:15 PM\",me@example.com,,Notion Labs,Software Engineer - Frontend,https://www.linkedin.com/jobs/view/123,cv.pdf,\"Why us?\nBecause\"\n"
            ."\"10/22/26, 9:00 AM\",me@example.com,,Vercel,Frontend Engineer,https://www.linkedin.com/jobs/view/456,cv.pdf,\n");

        $stats = (new LeadImport($this->candidate))->file($file, 'Job Applications.csv');

        $this->assertSame(['added' => 1, 'updated' => 1, 'skipped' => 0, 'contacts' => 0], $stats);
        $notion = Lead::where('company_name', 'Notion')->sole();
        $this->assertSame(['applied', '2026-10-21'], [$notion->status, $notion->applied_at->toDateString()]);
        $this->assertSame(['linkedin', 'applied'], [Lead::where('company_name', 'Vercel')->value('origin'), Lead::where('company_name', 'Vercel')->value('status')]);
    }

    public function test_job_leads_are_for_admins_only(): void
    {
        $member = User::factory()->create();
        $this->actingAs($member)->get(route('leads.index'))->assertForbidden();
        $this->actingAs($this->owner)->post(route('leads.store'), ['company' => 'Wolt', 'title' => 'Frontend Engineer', 'status' => 'applied'])->assertRedirect();
        $this->assertSame(['manual', 'applied', today()->toDateString()], [Lead::sole()->origin, Lead::sole()->status, Lead::sole()->applied_at->toDateString()]);
    }

    public function test_a_suggested_contact_is_only_saved_when_kept_and_can_be_marked_contacted(): void
    {
        config(['importer.ai.enabled' => true, 'importer.ai.provider' => 'openai', 'ai.providers.openai.key' => 'test-key']);
        Http::fake(['api.openai.com/v1/responses' => Http::response(['id' => 'resp_1', 'object' => 'response', 'status' => 'completed', 'model' => 'gpt-5.5',
            'output' => [['type' => 'message', 'id' => 'msg_1', 'role' => 'assistant', 'status' => 'completed', 'content' => [['type' => 'output_text', 'annotations' => [],
                'text' => json_encode(['found' => true, 'name' => 'Lina Haddad', 'title' => 'Engineering Manager, Web', 'profile_url' => 'https://github.com/lina',
                    'source_url' => 'https://acme.example/blog/our-web-team', 'why' => 'Leads the web team the role joins.'])]]]],
            'usage' => ['input_tokens' => 10, 'output_tokens' => 10, 'total_tokens' => 20]])]);
        $lead = Lead::create(['candidate_id' => $this->candidate->id, 'company_name' => 'Acme', 'title' => 'Frontend Engineer']);

        $this->actingAs($this->owner)->postJson(route('leads.contacts.suggest', $lead))->assertOk()
            ->assertJsonPath('contact.name', 'Lina Haddad')->assertJsonPath('contact.profile_url', 'https://github.com/lina');
        $this->assertSame(0, LeadContact::count());

        $this->actingAs($this->owner)->post(route('leads.contacts.store', $lead), ['name' => 'Lina Haddad', 'title' => 'Engineering Manager, Web', 'source' => 'ai'])->assertRedirect();
        $contact = LeadContact::sole();
        $this->actingAs($this->owner)->put(route('leads.contacts.update', $contact), ['contacted' => true])->assertRedirect();
        $this->assertSame(['ai', 'acme'], [$contact->fresh()->source, $contact->fresh()->company_key]);
        $this->assertNotNull($contact->fresh()->contacted_at);
    }

    private function role(string $company, string $title, array $attributes = []): Opportunity
    {
        $company = Company::where('name', $company)->first() ?? Company::forceCreate(['name' => $company, 'import_identity' => 'test:'.$company]);
        return Opportunity::factory()->job()->create([
            'company_id' => $company->id, 'title' => $title, 'origin' => 'imported', 'work_type' => 'remote', 'location' => 'Remote', 'country_code' => null,
            'seniority' => 'senior', 'applicant_scope' => 'anywhere', 'description' => 'Remote from anywhere. You will build React, Next.js and TypeScript features.',
            'application_mode' => 'external', 'application_url' => 'https://'.str()->slug($company->name).'.example/jobs/'.str()->slug($title),
            'first_seen_at' => now()->subHour(), 'posted_at' => now()->subDay(), 'first_posted_at' => now()->subDay(), 'date_source' => 'ats', 'closing_at' => null, ...$attributes,
        ]);
    }

    private function source(string $key, string $provider, string $url, array $domains, string $mode, array $settings = []): ImportSource
    {
        return ImportSource::create(['key' => $key, 'name' => $key, 'provider' => $provider, 'url' => $url, 'allowed_domains' => $domains,
            'permission_status' => 'approved', 'permission_notes' => 'Test source.', 'publication_mode' => $mode, 'enabled' => true,
            'cadence_hours' => 24, 'request_delay_ms' => 0, 'max_requests' => 100, 'settings' => $settings]);
    }
}

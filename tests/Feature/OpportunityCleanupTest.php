<?php

namespace Tests\Feature;

use App\Importing\OpportunityImporter;
use App\Importing\OpportunityNormalizer;
use App\Importing\SourceHttp;
use App\Models\Company;
use App\Models\Field;
use App\Models\ImportSource;
use App\Models\Internship;
use App\Models\OpportunitySource;
use App\Models\Skill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpportunityCleanupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-15 10:00:00', 'UTC'));
        SourceHttp::$resolveUsing = fn () => ['93.184.216.34'];
    }

    protected function tearDown(): void
    {
        SourceHttp::$resolveUsing = null;
        parent::tearDown();
    }

    public function test_locations_have_no_repeats_prefixes_or_regions_that_disagree_with_the_city(): void
    {
        $place = fn (?string $city, ?string $region, string $country = 'TN') => $this->normalize(['locations' => [['@type' => 'Place',
            'address' => array_filter(['addressLocality' => $city, 'addressRegion' => $region, 'addressCountry' => $country])]]]);

        $this->assertSame(['Tunis, Tunisia', 'TN'], [$place('Tunis', 'Tunis')['location'], $place('Tunis', 'Tunis')['country_code']]);
        $this->assertSame('Ariana, Tunisia', $place("Gouvernorat de l'Ariana", 'Tunis')['location']);
        $this->assertSame('Charguia 1, Ariana, Tunisia', $place('Ariana - charguia 1', 'Ariana')['location']);
        $this->assertSame('Bizance, Tunisia', $place('Bizance', null)['location']);
        $this->assertSame('Sokra, Ariana, Tunisia', $place('Sokra', 'Ariana')['location']);
        $this->assertSame('Tunisia', $place(null, null)['location']);
        $this->assertSame('San Francisco, California, United States', $place('San Francisco', 'California', 'US')['location']);
        $this->assertSame(['addressLocality' => 'Ariana', 'addressCountry' => 'TN'], $place("Gouvernorat de l'Ariana", 'Tunis')['locations'][0]['address']);
        $this->assertSame('Tunis, Tunisia', $this->normalize(['location' => 'Tunis, Tunis, TN', 'country_code' => 'TN'])['location']);
    }

    public function test_html_blocks_and_list_items_keep_their_line_breaks(): void
    {
        $text = (new OpportunityNormalizer())->text('<p>We build apps and AI integrations.</p><h3>Key Responsibilities :</h3>'
            ."<ul><li><p>Design features</p></li>\n<li>Build   APIs</li><li></li></ul><div>Line one</div><div>Line two<br>Line three</div>"
            .'<table><tr><td>A</td><td>B</td></tr><tr><td>C</td></tr></table><p>&nbsp;</p><p></p><p>End &amp; more</p>');

        $this->assertSame("We build apps and AI integrations.\n\nKey Responsibilities :\n\n• Design features\n• Build APIs\nLine one\nLine two\nLine three\n\nA B\nC\n\nEnd & more", $text);
        $this->assertSame("Built AI integrations.\nKey Responsibilities :\n\nUse Node.Js at www.Keejob.com.",
            (new OpportunityNormalizer())->text("Built AI integrations.Key Responsibilities :\n\n\n\nUse Node.Js at www.Keejob.com."));
    }

    public function test_work_arrangement_is_only_set_from_clear_words(): void
    {
        $workType = fn (string $title, string $description = '', array $input = []) => $this->normalize(['title' => $title,
            'description' => $description.' '.str_repeat('Rejoignez notre équipe pour travailler sur des projets concrets. ', 3)] + $input)['work_type'];

        $this->assertSame('remote', $workType('Développeur PHP (Télétravail)'));
        $this->assertSame('remote', $workType('Developer', 'This job is 100% remote.'));
        $this->assertSame('remote', $workType('Développeur', 'Le travail à distance est la règle.'));
        $this->assertSame('hybrid', $workType('Développeur', 'Nous proposons un mode de travail hybride.'));
        $this->assertSame('hybrid', $workType('Software Engineer (Hybrid)'));
        $this->assertSame('onsite', $workType('Développeur', 'Poste en présentiel à Tunis.'));
        $this->assertSame('onsite', $workType('Technicien', 'Travail sur site à Sousse.'));
        $this->assertSame('onsite', $workType('Developer', 'This is an on-site position.'));
        $this->assertSame('onsite', $workType('Developer', 'The role is onsite in Tunis.'));
        $this->assertSame('unknown', $workType('Hybrid Mobile Developer', 'Nous créons des applications mobiles hybrides et des outils de maintenance à distance.'));
        $this->assertSame('hybrid', $workType('Développeur', 'Travail à distance possible.'));
        $this->assertSame('unknown', $workType('Commercial', 'Une organisation hybride et agile.'));
        $this->assertSame('hybrid', $workType('Developer', 'Remote all the way.', ['work_type' => 'hybrid']));

        $remote = $this->normalize(['location' => 'San Francisco, CA', 'description' => 'This role is fully remote. '.str_repeat('Build things with the team every day. ', 3)]);
        $this->assertSame(['remote', 'restricted'], [$remote['work_type'], $remote['remote_eligibility']]);
    }

    public function test_contract_types_use_the_values_the_filters_know(): void
    {
        $types = fn (array $values) => $this->normalize(['employment_types' => $values])['employment_types'];

        $this->assertSame(['CONTRACTOR'], $types(['contractor']));
        $this->assertSame(['FULL_TIME', 'TEMPORARY'], $types(['Full-time', 'CDD']));
        $this->assertSame(['FULL_TIME'], $types(['Permanent', 'Full-time: Remote', 'Permanent contract']));
        $this->assertSame(['CONTRACTOR', 'TEMPORARY', 'INTERN'], $types(['Full Time Contractor', 'Short Term', 'Stage']));
        $this->assertSame(['PART_TIME', 'FULL_TIME'], $types(['part time, FULL_TIME']));
        $this->assertSame([], $types(['Accelerator Program']));
    }

    public function test_board_pages_get_clean_places_arrangement_types_and_only_real_skills(): void
    {
        Skill::create(['name' => 'Product']);
        $tunis = ['@type' => 'Place', 'address' => ['@type' => 'PostalAddress', 'addressLocality' => 'Tunis', 'addressRegion' => 'Tunis', 'addressCountry' => 'TN']];
        Http::fake([
            '*/robots.txt' => Http::response('', 404),
            'www.tanitjobs.com/sitemap.xml' => Http::response($this->sitemap(['https://www.tanitjobs.com/job/51/ai/', 'https://www.tanitjobs.com/job/52/stage/'])),
            'www.tanitjobs.com/job/51/*' => Http::response($this->jobPage('https://www.tanitjobs.com/job/51/ai/', [
                'title' => 'Senior AI Full-Stack Developer', 'employmentType' => 'contractor',
                'description' => '<p>You will design and maintain applications and AI integrations.</p><h3>Key Responsibilities :</h3>'
                    .'<ul><li>Integrate LLMs and AI APIs.</li><li>Collaborate with product and technical teams.</li><li>Build with React and Node.js.</li></ul>'
                    .'<p>Le poste est en mode hybride.</p>',
                'jobLocation' => ['@type' => 'Place', 'address' => ['addressLocality' => "Gouvernorat de l'Ariana", 'addressRegion' => 'Tunis', 'addressCountry' => 'TN']],
            ])),
            'www.tanitjobs.com/job/52/*' => Http::response($this->jobPage('https://www.tanitjobs.com/job/52/stage/', [
                'title' => 'Stage Développeur Web', 'jobLocationType' => 'TELECOMMUTE', 'jobLocation' => $tunis,
            ])),
        ]);

        app(OpportunityImporter::class)->run($this->source(), 100);

        $developer = Internship::where('title', 'Senior AI Full-Stack Developer')->sole();
        $this->assertSame(['Ariana, Tunisia', 'TN', 'hybrid', ['CONTRACTOR']], [$developer->location, $developer->country_code, $developer->work_type, $developer->employment_types]);
        $this->assertStringContainsString("AI integrations.\n\nKey Responsibilities :\n\n• Integrate LLMs and AI APIs.\n• Collaborate", $developer->description);
        $this->assertEqualsCanonicalizing(['React', 'Node.js', 'LLM'], $developer->skills()->pluck('name')->all());
        $stage = Internship::where('title', 'Stage Développeur Web')->sole();
        $this->assertSame(['remote', 'Tunis, Tunisia'], [$stage->work_type, $stage->location]);
    }

    public function test_clean_shows_what_would_change_on_a_dry_run_and_then_saves_it(): void
    {
        $old = $this->savedListing([
            'title' => 'Senior AI Full-Stack Developer', 'location' => 'Tunis, Tunis, TN', 'employment_types' => ['contractor'],
            'locations' => [['city' => 'Tunis', 'location' => 'Tunis, Tunis, TN', 'country_code' => 'TN',
                'address' => ['addressLocality' => 'Tunis', 'addressRegion' => 'Tunis', 'addressCountry' => 'TN']]],
            'description' => "We build web apps and AI integrations.Key Responsibilities :\nIntegrate LLMs.\nCollaborate with product teams.\nLe poste est 100% en télétravail.",
        ]);
        $role = $old->internship;
        $role->skills()->attach(Skill::create(['name' => 'Product']));

        $this->artisan('opportunities:clean --dry-run')
            ->expectsOutputToContain('location: Tunis, Tunis, TN → Tunis, Tunisia')
            ->expectsOutputToContain('work_type: unknown → remote')
            ->expectsOutputToContain('employment_types: contractor → CONTRACTOR')
            ->expectsOutputToContain('skills: Product → LLM')
            ->expectsOutputToContain('1 listing and 1 skill list would change. Nothing was saved.')
            ->assertSuccessful();
        $this->assertSame(['Tunis, Tunis, TN', 'unknown'], [$role->fresh()->location, $old->fresh()->normalized_data['work_type']]);
        $this->assertSame(['Product'], $role->skills()->pluck('name')->all());

        $this->artisan('opportunities:clean')->expectsOutputToContain('1 listing and 1 skill list cleaned.')->assertSuccessful();

        $role = $role->fresh();
        $this->assertSame(['Tunis, Tunisia', 'remote', ['CONTRACTOR'], 'Tunis, Tunisia'], [$role->location, $role->work_type, $role->employment_types, $role->locations[0]['location']]);
        $this->assertStringStartsWith("We build web apps and AI integrations.\nKey Responsibilities :", $role->description);
        $this->assertSame('Tunis, Tunisia', $old->fresh()->normalized_data['location']);
        $this->assertSame(['LLM'], $role->skills()->pluck('name')->all());
        $this->artisan('opportunities:clean')->expectsOutputToContain('0 listings and 0 skill lists cleaned.')->assertSuccessful();
    }

    public function test_clean_reads_saved_board_payloads_again_and_keeps_moderator_edits(): void
    {
        Http::fake();
        $content = '<p>You will build and ship product features with the team every single week.</p><h2>What you will do</h2><ul><li>Ship features</li><li>Review code</li></ul>';
        $item = $this->savedListing([
            'title' => 'Backend Engineer', 'location' => 'Tunis, Tunis, Tunisia', 'locations' => [], 'employment_types' => [],
            'description' => "You will build and ship product features with the team every single week.What you will do\n• Ship features\n• Review code",
        ], ['provider' => 'greenhouse', 'key' => 'greenhouse-acme', 'url' => 'https://boards-api.greenhouse.io/v1/boards/acme/jobs?content=true'], [
            'id' => 7, 'title' => 'Backend Engineer', 'company_name' => 'Acme', 'location' => ['name' => 'Tunis, Tunis, Tunisia'],
            'content' => htmlspecialchars($content), 'absolute_url' => 'https://job-boards.greenhouse.io/acme/jobs/7', 'first_published' => now()->subDay()->toIso8601String(),
        ]);
        $item->internship->update(['location' => 'Lac II, Tunis', 'moderation_overrides' => ['location' => ['value' => 'Lac II, Tunis']]]);

        $this->artisan('opportunities:clean')->assertSuccessful();

        $role = $item->internship->fresh();
        $this->assertSame('Lac II, Tunis', $role->location);
        $this->assertSame("You will build and ship product features with the team every single week.\n\nWhat you will do\n\n• Ship features\n• Review code", $role->description);
        $this->assertSame('Tunis, Tunisia', $item->fresh()->normalized_data['location']);
        Http::assertNothingSent();
    }

    public function test_clean_brings_remote_facts_along_when_the_arrangement_changes(): void
    {
        $content = '<p>This role is fully remote. You will build and ship product features with the team every single week, from design to release.</p>';
        $item = $this->savedListing([
            'title' => 'Backend Engineer', 'location' => 'San Francisco, CA', 'locations' => [], 'employment_types' => [],
            'remote_eligibility' => 'unknown', 'eligibility_evidence' => ['relocation' => 'Relocation support is offered.'], 'relocation' => 'yes',
            'description' => 'This role is fully remote. You will build and ship product features with the team every single week, from design to release.',
        ], ['provider' => 'greenhouse', 'key' => 'greenhouse-acme', 'url' => 'https://boards-api.greenhouse.io/v1/boards/acme/jobs?content=true'], [
            'id' => 8, 'title' => 'Backend Engineer', 'company_name' => 'Acme', 'location' => ['name' => 'San Francisco, CA'],
            'content' => htmlspecialchars($content), 'absolute_url' => 'https://job-boards.greenhouse.io/acme/jobs/8', 'first_published' => now()->subDay()->toIso8601String(),
        ]);

        $this->artisan('opportunities:clean')->expectsOutputToContain('remote_eligibility: unknown → restricted')->assertSuccessful();

        $role = $item->internship->fresh();
        $this->assertSame(['remote', 'restricted'], [$role->work_type, $role->remote_eligibility]);
        $this->assertSame(['relocation' => 'Relocation support is offered.', 'remote' => 'Source location: San Francisco, CA'], $item->fresh()->normalized_data['eligibility_evidence']);
    }

    private function normalize(array $input): array
    {
        return (new OpportunityNormalizer())->normalize($input + [
            'title' => 'Software Engineer', 'description' => str_repeat('Build and run the platform with the team. ', 5),
            'employer' => 'Acme', 'application_url' => 'https://www.tanitjobs.com/job/1/x/',
        ]);
    }

    private function savedListing(array $data, array $source = [], ?array $payload = null): OpportunitySource
    {
        $source = $this->source($source);
        $data += ['employer' => 'Acme', 'kind' => 'job', 'country_code' => 'TN', 'work_type' => 'unknown', 'employment_types' => ['FULL_TIME'],
            'application_url' => 'https://www.tanitjobs.com/job/9/x/', 'posted_at' => now()->subDay()->toIso8601String()];
        $role = Internship::create(collect($data)->only(['title', 'description', 'kind', 'location', 'locations', 'country_code', 'work_type', 'employment_types', 'application_url', 'remote_eligibility', 'eligibility_evidence'])->all() + [
            'company_id' => Company::forceCreate(['name' => 'Acme'])->id, 'field_id' => Field::firstOrCreate(['name' => 'Other'])->id,
            'origin' => 'imported', 'application_mode' => 'external',
        ]);
        return OpportunitySource::create(['import_source_id' => $source->id, 'internship_id' => $role->id, 'external_id' => (string) ($payload['id'] ?? 'x1'),
            'source_url' => $data['application_url'], 'payload' => $payload, 'normalized_data' => $data, 'state' => 'published',
            'first_seen_at' => now()->subDay(), 'last_discovered_at' => now()->subDay(), 'last_verified_at' => now()->subDay()]);
    }

    private function source(array $attributes = []): ImportSource
    {
        $attributes += ['key' => 'tanitjobs', 'provider' => 'jsonld', 'url' => 'https://www.tanitjobs.com/sitemap.xml',
            'allowed_domains' => ['www.tanitjobs.com', 'boards-api.greenhouse.io', 'job-boards.greenhouse.io'], 'settings' => ['job_path_prefix' => '/job/']];
        return ImportSource::create($attributes + [
            'name' => $attributes['key'], 'permission_status' => 'approved', 'permission_notes' => 'Approved for this test source.',
            'publication_mode' => 'automatic', 'enabled' => true, 'cadence_hours' => 24, 'request_delay_ms' => 0, 'max_requests' => 100,
        ]);
    }

    private function sitemap(array $urls): string
    {
        $entries = implode('', array_map(fn ($url) => "<url><loc>{$url}</loc></url>", $urls));
        return '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.$entries.'</urlset>';
    }

    private function jobPage(string $url, array $posting): string
    {
        $posting += ['@context' => 'https://schema.org', '@type' => 'JobPosting', 'url' => $url,
            'description' => '<p>'.str_repeat('Rejoignez notre équipe pour travailler sur des projets concrets. ', 3).'</p>',
            'datePosted' => now()->subDays(2)->toDateString(), 'employmentType' => 'OTHER',
            'hiringOrganization' => ['@type' => 'Organization', 'name' => 'ACME SARL']];
        return '<html><head><script type="application/ld+json">'.json_encode($posting).'</script></head><body></body></html>';
    }
}

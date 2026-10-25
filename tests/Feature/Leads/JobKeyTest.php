<?php

namespace Tests\Feature\Leads;

use App\Importing\JobKey;
use App\Models\CompanyAlias;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobKeyTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_names_drop_legal_suffixes_and_follow_aliases(): void
    {
        $this->assertSame('notion labs', JobKey::name('Notion Labs, Inc.'));
        $this->assertSame('acme', JobKey::name('ACME GmbH'));
        $this->assertSame('tether', JobKey::company('Tether Operations Limited'));
        $this->assertSame(JobKey::company('Tether'), JobKey::company('Tether Operations Limited'));

        CompanyAlias::remember('Fly.io Inc', 'Fly');
        $this->assertSame('fly', JobKey::company('fly io'));
    }

    public function test_titles_drop_seniority_work_modes_and_place_suffixes_but_keep_the_team(): void
    {
        $base = JobKey::title('Frontend Engineer');
        $this->assertSame('frontend engineer', $base);
        $this->assertSame($base, JobKey::title('Senior Front-End Engineer (100% Remote) - EMEA'));
        $this->assertSame($base, JobKey::title('Sr. Frontend Engineer, Remote (US only)'));
        $this->assertSame($base, JobKey::title('Frontend Engineer - Berlin', 'Berlin, Germany'));
        $this->assertSame($base, JobKey::title('Frontend Engineer (m/w/d)'));
        $this->assertSame('software engineer frontend', JobKey::title('Software Engineer (Frontend)'));
        $this->assertSame('software engineer payments', JobKey::title('Software Engineer, Payments'));
        $this->assertNotSame(JobKey::title('Staff Frontend Engineer'), $base);
        $this->assertSame(JobKey::key('Notion Labs, Inc.', 'Senior Frontend Engineer (Remote)'), JobKey::key('Notion', 'Frontend Engineer'));
    }

    public function test_similar_titles_score_by_shared_words(): void
    {
        $this->assertSame(1.0, JobKey::similar('fullstack engineer react', 'react fullstack engineer'));
        $this->assertLessThan(0.85, JobKey::similar('software engineer', 'software engineer payments'));
        $this->assertGreaterThanOrEqual(0.85, JobKey::similar('fullstack software engineer typescript', 'fullstack software engineer typescript node'));
    }

    public function test_urls_lose_tracking_and_resolve_to_the_employer_ats(): void
    {
        $this->assertSame('https://job-boards.greenhouse.io/acme/jobs/123', JobKey::url('https://boards.greenhouse.io/acme/jobs/123?gh_src=abc&utm_source=himalayas'));
        $this->assertSame('https://job-boards.greenhouse.io/acme/jobs/123', JobKey::url('https://boards.greenhouse.io/embed/job_app?for=acme&token=123'));
        $this->assertSame('https://jobs.lever.co/acme/0b5f1a2c-1111-2222-3333-444455556666', JobKey::url('https://jobs.lever.co/acme/0b5f1a2c-1111-2222-3333-444455556666/apply?lever-origin=applied'));
        $this->assertSame('https://jobs.ashbyhq.com/acme/0b5f1a2c-1111-2222-3333-444455556666', JobKey::url('https://jobs.ashbyhq.com/acme/0b5f1a2c-1111-2222-3333-444455556666/application?utm_source=x'));
        $this->assertSame('https://acme.com/careers/5?gh_jid=5', JobKey::url('https://www.acme.com/careers/5/?gh_jid=5&ref=remoteok#apply'));
        $this->assertSame(['provider' => 'workable', 'slug' => 'acme', 'id' => 'A1B2C3'], JobKey::ats('https://apply.workable.com/acme/j/A1B2C3/'));
        $this->assertSame(['provider' => 'recruitee', 'slug' => 'acme', 'id' => 'senior-react-developer'], JobKey::ats('https://acme.recruitee.com/o/senior-react-developer'));
        $this->assertNull(JobKey::ats('https://remoteok.com/remote-jobs/123'));
    }
}

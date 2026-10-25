<?php

namespace Tests\Feature\Leads;

use App\Leads\Eligibility;
use Tests\TestCase;

class EligibilityTest extends TestCase
{
    private function job(array $data): array
    {
        return $data + ['work_type' => 'remote', 'location' => null, 'country_code' => null, 'applicant_scope' => 'unknown',
            'remote_eligibility' => 'unknown', 'eligible_countries' => [], 'excluded_countries' => [], 'relocation' => 'unknown',
            'visa_sponsorship' => 'unknown', 'eligibility_evidence' => [], 'description' => ''];
    }

    public function test_a_worldwide_board_label_loses_to_latam_only_in_the_employer_text(): void
    {
        $answer = Eligibility::classify($this->job([
            'location' => 'Worldwide', 'applicant_scope' => 'anywhere',
            'description' => 'We are a fully remote team. This role is only open to candidates based in LATAM. You will build React apps.',
        ]));
        $this->assertSame('excluded', $answer['class']);
        $this->assertStringContainsString('LATAM', $answer['quote']);
    }

    public function test_remote_roles_open_to_a_region_with_tunisia_are_remote(): void
    {
        $this->assertSame('remote', Eligibility::classify($this->job(['location' => 'Remote (EMEA)']))['class']);
        $this->assertSame('remote', Eligibility::classify($this->job(['description' => 'We hire anywhere in the world as contractors through Deel.']))['class']);
        $this->assertSame('remote', Eligibility::classify($this->job(['eligible_countries' => ['TN', 'MA'], 'applicant_scope' => 'listed']))['class']);
        $this->assertSame('remote', Eligibility::classify($this->job(['description' => 'Remote, Africa. Applicants from North Africa are welcome.']))['class']);
    }

    public function test_remote_roles_limited_elsewhere_are_excluded(): void
    {
        $us = Eligibility::classify($this->job(['description' => 'Remote role. You must be authorized to work in the US without sponsorship.']));
        $this->assertSame('excluded', $us['class']);
        $this->assertStringContainsString('authorized to work in the US', $us['quote']);
        $this->assertSame('excluded', Eligibility::classify($this->job(['location' => 'Remote - US or Canada']))['class']);
        $this->assertSame('excluded', Eligibility::classify($this->job(['description' => 'Remote (EU only). Our customers are worldwide.']))['class']);
        $this->assertSame('excluded', Eligibility::classify($this->job(['applicant_scope' => 'anywhere', 'excluded_countries' => ['TN']]))['class']);
        $this->assertSame('remote', Eligibility::classify($this->job(['description' => 'You must be based in Europe or Africa.']))['class']);
    }

    public function test_on_site_roles_abroad_need_a_visa_or_relocation_offer(): void
    {
        $berlin = Eligibility::classify($this->job(['work_type' => 'onsite', 'location' => 'Berlin, Germany', 'country_code' => 'DE',
            'description' => 'Join our Berlin office. We sponsor work visas and offer a relocation package.']));
        $this->assertSame('visa', $berlin['class']);
        $this->assertSame('visa', Eligibility::classify($this->job(['work_type' => 'hybrid', 'country_code' => 'NL', 'relocation' => 'yes']))['class']);
        $this->assertSame('excluded', Eligibility::classify($this->job(['work_type' => 'onsite', 'country_code' => 'DE', 'location' => 'Munich']))['class']);
        $this->assertSame('excluded', Eligibility::classify($this->job(['work_type' => 'hybrid', 'country_code' => 'GB',
            'description' => 'Hybrid in London. Unfortunately we cannot offer visa sponsorship.']))['class']);
    }

    public function test_nothing_said_is_unclear(): void
    {
        $this->assertSame('unclear', Eligibility::classify($this->job(['description' => 'Build our product with React and Node.']))['class']);
        $this->assertSame('unclear', Eligibility::classify($this->job(['work_type' => 'unknown', 'description' => 'Build our product.']))['class']);
    }
}

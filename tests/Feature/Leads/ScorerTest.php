<?php

namespace Tests\Feature\Leads;

use App\Leads\Scorer;
use Tests\TestCase;

class ScorerTest extends TestCase
{
    public function test_a_plain_software_engineer_title_is_full_stack_but_a_mobile_one_is_not(): void
    {
        $reasons = fn (string $title) => app(Scorer::class)->score($title, 'React and TypeScript', 'remote', now())[1];

        $this->assertContains('Full stack role', $reasons('Senior Software Engineer'));
        $this->assertNotContains('Full stack role', $reasons('Senior Software Engineer, Android (Contract)'));
        $this->assertContains('Frontend role', $reasons('Software Engineer, Frontend'));
    }
}

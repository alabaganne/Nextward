<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\CvPdf;
use App\Support\CvTemplates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CvRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_theme_shows_every_section_with_its_name_in_the_cv_language(): void
    {
        $pdf = app(CvPdf::class);

        foreach (array_keys(CvTemplates::TEMPLATES) as $template) {
            foreach (CvTemplates::LOCALES as $locale) {
                $labels = CvTemplates::labels($locale);
                $html = html_entity_decode($pdf->html($this->cv(), ['template' => $template, 'accent' => 'plum'], $locale), ENT_QUOTES);

                $this->assertStringContainsString("tpl-$template", $html);
                $this->assertStringContainsString('<html lang="'.$locale.'"', $html, "$template/$locale");
                $this->assertStringContainsString(CvTemplates::ACCENTS['plum']['value'], $html);
                $this->assertStringContainsString('Sami Ben Ali', $html);
                $this->assertStringContainsString('Built the billing API.', $html);
                $this->assertStringContainsString($labels['present'], $html, "$template/$locale");
                foreach (['experience', 'education', 'skills', 'projects', 'languages'] as $section) {
                    $this->assertStringContainsString($labels[$section], $html, "$template/$locale is missing $section");
                }
                // The two-column theme puts the summary under "Profile" and the contact in the side column.
                $this->assertStringContainsString($template === 'soren' ? $labels['profile'] : $labels['summary'], $html, "$template/$locale");
                if ($template === 'soren') {
                    $this->assertStringContainsString($labels['contact'], $html);
                }
            }
        }
    }

    public function test_unknown_styles_and_languages_fall_back_to_the_defaults(): void
    {
        $this->assertSame(CvTemplates::DEFAULT, CvTemplates::style(['template' => 'nope', 'accent' => ['x'], 'paper' => null]));
        $this->assertSame('en', CvTemplates::locale('ar'));

        $html = app(CvPdf::class)->html($this->cv(), null, 'ar');
        $this->assertStringContainsString('tpl-halden', $html);
        $this->assertStringContainsString('Experience', $html);
    }

    public function test_pdfs_get_fonts_inline_and_the_page_size(): void
    {
        $pdf = app(CvPdf::class);

        $print = $pdf->html($this->cv(), ['font' => 'serif', 'paper' => 'letter'], 'en', true);
        $this->assertStringContainsString('data:font/woff2;base64,', $print);
        $this->assertStringContainsString("font-family:'Newsreader'", $print);
        $this->assertStringContainsString('size: letter', $print);
        $this->assertStringNotContainsString('/fonts/cv/', $print);

        $preview = $pdf->html($this->cv(), ['font' => 'sans'], 'en');
        $this->assertStringContainsString('/fonts/cv/hanken-grotesk', $preview);
        $this->assertStringNotContainsString("font-family:'Newsreader'", $preview);

        $this->assertStringStartsWith('%PDF', $pdf->pdf($this->cv(), null, 'en'));
        $this->assertStringStartsWith('%PDF', $pdf->letterPdf($this->cv(), 'Dear team.', null, 'fr'));
    }

    public function test_the_preview_needs_a_login(): void
    {
        $this->post('/cv/preview', ['cv' => $this->cv()])->assertRedirect('/login');
        $this->postJson('/cv/preview', ['cv' => $this->cv()])->assertUnauthorized();

        $this->seed(\Tests\Fixtures\DemoSeeder::class);
        $user = User::where('email', 'student@example.com')->firstOrFail();

        $html = $this->actingAs($user)->post('/cv/preview', ['cv' => $this->cv(), 'locale' => 'de', 'style' => ['template' => 'atlas']])
            ->assertOk()->assertHeader('Content-Type', 'text/html; charset=UTF-8')->getContent();
        $this->assertStringContainsString('Berufserfahrung', $html);
        $this->assertStringContainsString('tpl-atlas', $html);

        $letter = $this->actingAs($user)->post('/cv/preview', ['cv' => $this->cv(), 'letter' => 'Madame, Monsieur,', 'locale' => 'fr'])->assertOk()->getContent();
        $this->assertStringContainsString('Madame, Monsieur,', $letter);

        $this->actingAs($user)->postJson('/cv/preview', ['cv' => $this->cv(), 'style' => ['template' => 'nope']])->assertStatus(422);
    }

    private function cv(): array
    {
        return [
            'name' => 'Sami Ben Ali',
            'contact' => ['sami@example.com', '+216 50 000 000', 'Tunis', 'https://sami.dev'],
            'headline' => 'Backend developer',
            'summary' => 'I build web apps.',
            'skills' => ['Laravel', 'Vue'],
            'experience' => [['title' => 'Developer', 'company' => 'Acme', 'location' => 'Tunis', 'start' => '2023-01', 'end' => '', 'current' => true, 'highlights' => ['Built the billing API.']]],
            'education' => [['school' => 'ISSAT Sousse', 'degree' => 'Engineering', 'field' => 'Software', 'start' => '2018', 'end' => '2023']],
            'projects' => [['name' => 'Nextward', 'summary' => 'Job board', 'url' => 'https://example.com']],
            'languages' => ['Arabic', 'French', 'English'],
        ];
    }
}

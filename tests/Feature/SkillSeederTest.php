<?php

namespace Tests\Feature;

use App\Importing\OpportunityNormalizer;
use App\Models\Skill;
use Database\Seeders\SkillSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SkillSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_catalogue_is_the_tagging_list_and_seeds_once(): void
    {
        Skill::create(['name' => 'Laravel']);

        $this->seed(SkillSeeder::class);
        $this->seed(SkillSeeder::class);

        $names = collect(config('importer.skills'))->map(fn ($pattern, $name) => is_int($name) ? $pattern : $name)->values();
        $this->assertSame($names->count(), Skill::count());
        $this->assertSame(1, Skill::where('name', 'Laravel')->count());
        $this->assertTrue(Skill::whereIn('name', ['Kubernetes', 'Flutter', 'Splunk', 'STM32'])->count() === 4);
        $this->assertFalse(Skill::where('name', 'AutoCAD')->exists());
    }

    public function test_the_catalogue_covers_web_and_mobile_and_has_no_duplicates_or_broken_patterns(): void
    {
        $names = [];
        foreach (config('importer.skills') as $name => $pattern) {
            if (is_int($name)) { [$name, $pattern] = [$pattern, preg_quote($pattern, '~')]; }
            $this->assertArrayNotHasKey(mb_strtolower($name), $names, "{$name} is listed twice.");
            $this->assertNotFalse(@preg_match('~(?<![\pL\pN])(?:'.$pattern.')(?![\pL\pN])~iu', ''), "{$name} has a broken pattern.");
            $names[mb_strtolower($name)] = $name;
        }
        foreach (['HTML', 'CSS', 'JavaScript', 'TypeScript', 'React', 'React Native', 'Vue', 'Angular', 'Next.js', 'Node.js', 'PHP', 'Laravel',
            'Tailwind CSS', 'Responsive Design', 'Web Accessibility', 'Flutter', 'Dart', 'Kotlin', 'Swift', 'SwiftUI', 'Jetpack Compose', 'Android',
            'iOS', 'Ionic', 'Expo', 'Xcode', 'Android Studio', 'C', 'R', 'Docker', 'Kubernetes', 'Power BI', 'Wireshark', 'Figma'] as $skill) {
            $this->assertArrayHasKey(mb_strtolower($skill), $names, "{$skill} is missing.");
        }
        // Stays well under the limit of values the AI can choose from.
        $this->assertLessThan(850, count($names));
    }

    public function test_keyword_tagging_finds_real_skills_and_ignores_look_alikes(): void
    {
        $skills = fn (string $text) => (new OpportunityNormalizer())->skills('Role', $text);

        $this->assertEqualsCanonicalizing(['HTML', 'CSS', 'JavaScript', 'React Native', 'Responsive Design', 'Git'],
            $skills('Build screens in React Native. HTML5, CSS3 and JavaScript, responsive design, Git.'));
        $this->assertEqualsCanonicalizing(['Python', 'C#', 'C++', 'SQL', 'C', 'R'], $skills('We use C, C++ and C#; analysts use Python, R and SQL.'));
        $this->assertSame([], $skills('Acme SAS recherche un Chef de projet. Bureaux à Palo Alto, California. (C) 2026 Acme. Microsoft(R) partner. '
            .'USB Type-C chargers. Niveau C1 en anglais. Our core data platform. Free Espresso and snacks.'));
    }
}

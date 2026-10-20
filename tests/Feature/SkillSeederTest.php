<?php

namespace Tests\Feature;

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
}

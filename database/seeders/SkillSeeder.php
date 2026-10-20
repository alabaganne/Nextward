<?php

namespace Database\Seeders;

use App\Models\Skill;
use Illuminate\Database\Seeder;

// The skills catalogue is the list listings are tagged with (importer.skills). Safe to run again.
class SkillSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('importer.skills', []) as $name => $pattern) {
            Skill::firstOrCreate(['name' => is_int($name) ? $pattern : $name]);
        }
    }
}

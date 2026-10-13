<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ApplicationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Demo data can only be seeded in local or testing environments.');
        }

        for ($index = 0; $index < 100; $index++) {
            $opportunity = \App\Models\Internship::available()->inRandomOrder()->first();
            if (! $opportunity) { break; }
            $candidate = \App\Models\Student::whereDoesntHave('applications', fn ($query) => $query->where('internships.id', $opportunity->id))->inRandomOrder()->first();
            if (! $candidate) { continue; }
            \App\Models\Application::factory()->create(['internship_id' => $opportunity->id, 'student_id' => $candidate->id]);
        }
    }
}

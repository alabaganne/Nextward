<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Field;
use App\Models\Candidate;
use App\Models\Resume;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

// The owner's candidate account, filled from the resume and alabaganne.com. Safe to run again.
class OwnerProfileSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Demo data can only be seeded in local or testing environments.');
        }

        $resume = 'resumes/ala-baganne-resume.pdf';
        Storage::disk('local')->put($resume, file_get_contents(database_path('seeders/files/ala-baganne-resume.pdf')));

        $fields = Field::whereIn('name', ['Software Engineering', 'Web Development'])->pluck('id');
        $profile = [
            'headline' => 'Full-Stack Software Engineer',
            'experience_years' => 5,
            'field_id' => $fields->first(),
            'city_id' => City::where('name', 'Monastir')->value('id'),
            'country_code' => 'TN',
            'location' => 'Monastir, Tunisia',
            'languages' => ['English', 'French', 'Arabic'],
            'website' => 'https://www.alabaganne.com',
            'about' => $this->about(),
            'preferences' => [
                'kinds' => ['job'],
                'countries' => [],
                'work_types' => ['remote', 'hybrid', 'onsite'],
                'fields' => $fields->all(),
                'skills' => [
                    'React', 'Next.js', 'TypeScript', 'JavaScript', 'Vue.js', 'AngularJS', 'Tailwind CSS', 'Node.js', 'Express.js',
                    'FastAPI', 'Python', 'Laravel', 'PHP', 'PostgreSQL', 'MySQL', 'Supabase', 'MongoDB', 'RAG', 'OpenAI API',
                    'Vertex AI', 'DSPy', 'AWS', 'Google Cloud', 'Docker', 'CI/CD', 'Jest', 'Cypress',
                ],
                'seniority' => ['mid', 'senior'],
                'include_unknown_eligibility' => false,
            ],
        ];

        $user = User::where('email', 'alabaganne9@gmail.com')->first();
        $candidate = $user?->userable instanceof Candidate ? $user->userable : new Candidate();
        $candidate->fill($profile)->save();

        $user ??= new User(['email' => 'alabaganne9@gmail.com']);
        $user->fill([
            'name' => 'Ala Baganne',
            'password' => Hash::make('Pa$$w0rd'),
            'phone_number' => '+216 50 101 959',
            'linkedin_profile_url' => 'https://www.linkedin.com/in/alabaganne',
        ]);
        $user->forceFill([
            'email_verified_at' => $user->email_verified_at ?? now(),
            'terms_accepted_at' => $user->terms_accepted_at ?? now(),
            'userable_type' => 'candidate',
            'userable_id' => $candidate->id,
        ])->save();

        Resume::updateOrCreate(['candidate_id' => $candidate->id, 'path' => $resume], [
            'name' => 'Ala Baganne Resume',
            'locale' => 'en',
            'source' => 'uploaded',
            'status' => 'ready',
            'file_name' => 'Ala_Baganne_Resume.pdf',
        ]);
        if (! $candidate->resumes()->where('is_main', true)->exists()) {
            Resume::where('candidate_id', $candidate->id)->where('path', $resume)->update(['is_main' => true]);
        }
    }

    private function about(): string
    {
        return <<<'TEXT'
Full-stack software engineer from Monastir, Tunisia. Since 2021 I have built web products for teams in the US, Europe and Asia, from the database and API to the interface, and I add AI features where they help.

- Retain Health (Boston, remote, Aug 2021 to now): full-stack work on RetainYourBrain, a healthcare platform with 25,000+ users. I built a dynamic form system and added automated tests with Jest, Cypress, Mocha and Supertest.
- Wequity (Brussels, remote, Oct 2025 to May 2026): Full-Stack and AI Engineer on NORA, an AI platform for legal documents in English, French and Dutch. I built a RAG knowledge base with source citations, a Smart Processing module with LLM APIs and DSPy, and background processing on Google Cloud Tasks and Pub/Sub.
- Upwork (Aug 2024 to now): Top Rated freelancer with seven 5-star contracts and a 100% Job Success Score.
- Internships at Satoripop (Sousse) and Realinflo (Hong Kong, remote).

Projects: MenuMate (menumate.net), QR-code menus and table orders for restaurants; Karta (kartatunisie.com), a gift card store for Tunisian customers; Internly, my end-of-studies internship platform built with Laravel, Vue and Inertia.

Education: Engineer's degree in Software Engineering (2024) and Bachelor's degree in Computer Science (2021), ISSAT Sousse.

GitHub: github.com/alabaganne
TEXT;
    }
}

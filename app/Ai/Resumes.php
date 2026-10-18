<?php

namespace App\Ai;

use App\Ai\Agents\ResumeReader;
use App\Models\Student;
use App\Support\ProfileHistory;
use Illuminate\Support\Str;
use Laravel\Ai\Files\Document;

// Turns a candidate's resume PDF into profile suggestions. Nothing is saved here.
class Resumes
{
    public function __construct(private OpportunityClassifier $ai) {}

    public function enabled(): bool
    {
        return $this->ai->enabled();
    }

    public function read(Student $student): array
    {
        $response = (new ResumeReader)->prompt(
            'Fill in the profile from the attached resume.',
            attachments: [Document::fromStorage($student->resume_path, 'local')],
            provider: $this->ai->provider(),
            model: $this->ai->model('review'),
        );

        return $this->clean($response->toArray());
    }

    private function clean(array $data): array
    {
        $text = fn ($value, int $max = 255) => Str::limit(trim((string) ($value ?? '')), $max, '');
        $url = fn ($value) => filter_var($value = $text($value), FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $value) ? $value : '';
        $words = fn ($values, int $max) => collect(is_array($values) ? $values : [])->map(fn ($value) => $text($value, 80))
            ->filter()->unique(fn ($value) => mb_strtolower($value))->take($max)->values()->all();
        $country = strtoupper($text($data['country_code'] ?? '', 2));

        return [
            'headline' => $text($data['headline'] ?? ''),
            'about' => $text($data['about'] ?? '', 2000),
            'experience_years' => max(0, min(80, (int) ($data['experience_years'] ?? 0))),
            'location' => $text($data['location'] ?? ''),
            'country_code' => preg_match('/^[A-Z]{2}$/', $country) ? $country : '',
            'phone_number' => $text($data['phone_number'] ?? '', 40),
            'website' => $url($data['website'] ?? ''),
            'linkedin_profile_url' => $url($data['linkedin_profile_url'] ?? ''),
            'languages' => $words($data['languages'] ?? [], 20),
            'skills' => $words($data['skills'] ?? [], 30),
            'experience' => array_slice(ProfileHistory::clean('experience', $data['experience'] ?? []), 0, 30),
            'education' => array_slice(ProfileHistory::clean('education', $data['education'] ?? []), 0, 20),
            'projects' => collect(ProfileHistory::clean('projects', $data['projects'] ?? []))
                ->map(fn ($project) => [...$project, 'url' => $url($project['url'])])->take(20)->values()->all(),
        ];
    }
}

<?php

namespace App\Ai;

use App\Ai\Agents\ApplicationKitWriter;
use App\Models\ApplicationKit;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Ai\Files\Document;

// Writes the CV and cover letter of a kit from the candidate's profile, resume and the role.
class ApplicationKits
{
    public function __construct(private OpportunityClassifier $ai) {}

    public function enabled(): bool
    {
        return $this->ai->enabled();
    }

    public function write(ApplicationKit $kit): array
    {
        $student = $kit->student()->with('user', 'field', 'city', 'skills')->firstOrFail();
        $role = $kit->internship()->with('company.user', 'skills', 'translations')->firstOrFail();
        $resume = $student->resume_path && Storage::disk('local')->exists($student->resume_path);

        $response = (new ApplicationKitWriter($kit->locale))->prompt(
            $this->brief($student, $role, $kit->locale, $resume),
            attachments: $resume ? [Document::fromStorage($student->resume_path, 'local')] : [],
            provider: $this->ai->provider(),
            model: $this->ai->model('letter'),
        );
        $data = $response->toArray();
        $user = $student->user;
        $contact = array_values(array_filter([$user?->email, $user?->phone_number, $student->location ?: $student->city?->name, $student->website, $user?->linkedin_profile_url]));

        return [
            'cv' => ['name' => (string) $user?->name, 'contact' => $contact, ...$this->cleanCv($data['cv'] ?? [])],
            'cover_letter' => trim((string) ($data['cover_letter'] ?? '')),
            'model' => $response->meta->model ?? $this->ai->model('letter'),
            'input_tokens' => (int) ($response->usage->inputTokens ?? 0),
            'output_tokens' => (int) ($response->usage->outputTokens ?? 0),
        ];
    }

    // Keeps the CV in one known shape, whatever the AI sent.
    public static function cleanCv(array $cv): array
    {
        $text = fn ($value, int $max = 300) => Str::limit(trim(is_scalar($value) ? (string) $value : ''), $max, '');
        $words = fn ($values, int $max, int $length = 80) => collect(is_array($values) ? $values : [])->map(fn ($value) => $text($value, $length))->filter()->take($max)->values()->all();
        $rows = fn ($rows, array $keys, int $max) => collect(is_array($rows) ? $rows : [])->filter(fn ($row) => is_array($row))
            ->map(fn ($row) => collect($keys)->mapWithKeys(fn ($key) => [$key => $key === 'highlights' ? $words($row[$key] ?? [], 6, 300) : $text($row[$key] ?? '')])->all())
            ->filter(fn ($row) => $row[$keys[0]] !== '')->take($max)->values()->all();

        return [
            'headline' => $text($cv['headline'] ?? '', 150),
            'summary' => $text($cv['summary'] ?? '', 1500),
            'skills' => $words($cv['skills'] ?? [], 20),
            'experience' => $rows($cv['experience'] ?? [], ['title', 'company', 'location', 'start', 'end', 'highlights'], 15),
            'education' => $rows($cv['education'] ?? [], ['school', 'degree', 'field', 'start', 'end'], 10),
            'projects' => $rows($cv['projects'] ?? [], ['name', 'summary', 'url'], 10),
            'languages' => $words($cv['languages'] ?? [], 10),
        ];
    }

    private function brief($student, $role, string $locale, bool $resume): string
    {
        $user = $student->user;
        $skills = $student->skills->pluck('name')->merge($student->preferences['skills'] ?? [])->unique()->implode(', ');
        $line = fn (string $label, $value) => filled($value) ? $label.': '.$value : null;
        $history = fn (string $label, $rows) => $rows ? $label.":\n".collect($rows)->map(fn ($row) => '- '.implode(' | ', array_filter(array_map(fn ($value) => is_bool($value) ? ($value ? 'current' : null) : $value, $row))))->implode("\n") : null;

        return implode("\n", array_filter([
            'Write a CV and a cover letter in '.($locale === 'fr' ? 'French' : 'English').' for this candidate and job.',
            '',
            'CANDIDATE',
            $line('Name', $user?->name),
            $line('Headline', $student->headline),
            $line('Years of experience', $student->experience_years),
            $line('Field', $student->field?->name),
            $line('Location', $student->location ?: $student->city?->name),
            $line('Languages', implode(', ', $student->languages ?: [])),
            $line('Skills', $skills),
            $line('Website', $student->website),
            $line('LinkedIn', $user?->linkedin_profile_url),
            $line('About', $student->about),
            $history('Experience', $student->experience),
            $history('Education', $student->education),
            $history('Projects', $student->projects),
            $resume ? 'Their resume is attached as a PDF.' : 'They have not added a resume, so use the profile only.',
            '',
            'JOB',
            $line('Title', $role->titleFor($locale)),
            $line('Company', $role->company?->display_name),
            $line('Type', $role->kind),
            $line('Location', $role->location_label),
            $line('Work type', $role->work_type),
            $line('Seniority', $role->seniority),
            $line('Skills', $role->skills->pluck('name')->implode(', ')),
            'Description:',
            Str::limit(trim(html_entity_decode(strip_tags($role->descriptionFor($locale)))), 12000),
        ], fn ($part) => $part !== null));
    }
}

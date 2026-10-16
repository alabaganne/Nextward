<?php

namespace App\Ai;

use App\Ai\Agents\CoverLetterWriter;
use App\Models\Internship;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Ai\Files\Document;

// Drafts a cover letter for one role from the candidate's profile, with their resume PDF attached when they have one.
class CoverLetters
{
    public function __construct(private OpportunityClassifier $ai) {}

    public function enabled(): bool
    {
        return $this->ai->enabled();
    }

    public function write(User $user, Internship $role): string
    {
        $student = $user->userable;
        $resume = $student->resume_path && Storage::disk('local')->exists($student->resume_path);
        $response = (new CoverLetterWriter)->prompt(
            $this->brief($user, $role, $resume),
            attachments: $resume ? [Document::fromStorage($student->resume_path, 'local')] : [],
            provider: $this->ai->provider(),
            model: $this->ai->model('letter'),
        );
        return trim($response->text);
    }

    private function brief(User $user, Internship $role, bool $resume): string
    {
        $student = $user->userable->loadMissing('field', 'city', 'skills');
        $role->loadMissing('company', 'skills');
        $skills = $student->skills->pluck('name')->merge($student->preferences['skills'] ?? [])->unique()->implode(', ');
        $line = fn (string $label, $value) => filled($value) ? $label.': '.$value : null;

        return implode("\n", array_filter([
            'Write a cover letter for this candidate and role.',
            '',
            'CANDIDATE',
            $line('Name', $user->name),
            $line('Headline', $student->headline),
            $line('Career stage', $student->career_stage),
            $line('Years of experience', $student->experience_years),
            $line('Field', $student->field?->name),
            $line('Location', $student->location ?: $student->city?->name),
            $line('Languages', implode(', ', $student->languages ?: [])),
            $line('Skills', $skills),
            $line('Website', $student->website),
            $line('LinkedIn', $user->linkedin_profile_url),
            $line('About', $student->about),
            $resume ? 'Their resume is attached as a PDF.' : 'They have not added a resume, so use the profile only.',
            '',
            'ROLE',
            $line('Title', $role->title),
            $line('Company', $role->company?->display_name),
            $line('Type', $role->kind),
            $line('Location', $role->location_label),
            $line('Work type', $role->work_type),
            $line('Seniority', $role->seniority),
            $line('Skills', $role->skills->pluck('name')->implode(', ')),
            'Description:',
            Str::limit(trim(html_entity_decode(strip_tags((string) $role->description))), 12000),
        ], fn ($part) => $part !== null));
    }
}

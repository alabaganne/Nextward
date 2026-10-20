<?php

namespace App\Ai;

use App\Ai\Agents\ApplicationKitWriter;
use App\Models\ApplicationKit;
use App\Models\Opportunity;
use App\Support\Cv;
use App\Support\CvTemplates;
use App\Support\LanguageGuess;
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
        $candidate = $kit->candidate()->with('user', 'field', 'city', 'skills', 'mainResume')->firstOrFail();
        $role = $kit->opportunity()->with('company.user', 'skills')->firstOrFail();
        $resume = $candidate->mainResume?->hasFile() ? $candidate->mainResume : null;

        $response = (new ApplicationKitWriter($kit->locale))->prompt(
            $this->brief($candidate, $role, $kit->locale, (bool) $resume),
            attachments: $resume ? [Document::fromStorage($resume->path, 'local')] : [],
            provider: $this->ai->provider(),
            model: $this->ai->model('letter'),
        );
        $data = $response->toArray();

        return [
            'cv' => Cv::clean([...($data['cv'] ?? []), 'name' => (string) $candidate->user?->name, 'contact' => Cv::contact($candidate)]),
            'cover_letter' => trim((string) ($data['cover_letter'] ?? '')),
            'model' => $response->meta->model ?? $this->ai->model('letter'),
            'input_tokens' => (int) ($response->usage->inputTokens ?? 0),
            'output_tokens' => (int) ($response->usage->outputTokens ?? 0),
        ];
    }

    // Languages a kit can be written in for this listing: its own language first when we can lay it out, then English and French.
    public static function locales(Opportunity $role): array
    {
        $original = $role->originalLocale();

        return array_values(array_unique(array_filter([in_array($original, CvTemplates::LOCALES, true) ? $original : null, 'en', 'fr'])));
    }

    private function brief($candidate, $role, string $locale, bool $resume): string
    {
        $user = $candidate->user;
        $skills = $candidate->skills->pluck('name')->merge($candidate->preferences['skills'] ?? [])->unique()->implode(', ');
        $line = fn (string $label, $value) => filled($value) ? $label.': '.$value : null;
        $history = fn (string $label, $rows) => $rows ? $label.":\n".collect($rows)->map(fn ($row) => '- '.implode(' | ', array_filter(array_map(fn ($value) => is_bool($value) ? ($value ? 'current' : null) : $value, $row))))->implode("\n") : null;

        return implode("\n", array_filter([
            'Write a CV and a cover letter in '.(LanguageGuess::name($locale) ?? 'English').' for this candidate and job.',
            '',
            'CANDIDATE',
            $line('Name', $user?->name),
            $line('Headline', $candidate->headline),
            $line('Years of experience', $candidate->experience_years),
            $line('Field', $candidate->field?->name),
            $line('Location', $candidate->location ?: $candidate->city?->name),
            $line('Languages', implode(', ', $candidate->languages ?: [])),
            $line('Skills', $skills),
            $line('Website', $candidate->website),
            $line('LinkedIn', $user?->linkedin_profile_url),
            $line('About', $candidate->about),
            $history('Experience', $candidate->experience),
            $history('Education', $candidate->education),
            $history('Projects', $candidate->projects),
            $resume ? 'Their resume is attached as a PDF.' : 'They have not added a resume, so use the profile only.',
            '',
            'JOB',
            $line('Listing language', LanguageGuess::name($role->originalLocale())),
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

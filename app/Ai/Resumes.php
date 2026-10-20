<?php

namespace App\Ai;

use App\Ai\Agents\ResumeReader;
use App\Ai\Agents\ResumeTranslator;
use App\Models\Resume;
use App\Support\Cv;
use App\Support\LanguageGuess;
use App\Support\ProfileHistory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Laravel\Ai\Files\Document;

// Reads a resume PDF into profile suggestions (nothing is saved here), translates resumes and makes editable copies of uploaded PDFs.
class Resumes
{
    public function __construct(private OpportunityClassifier $ai) {}

    public function enabled(): bool
    {
        return $this->ai->enabled();
    }

    public function read(Resume $resume): array
    {
        $response = (new ResumeReader)->prompt(
            'Fill in the profile from the attached resume.',
            attachments: [Document::fromStorage($resume->path, 'local')],
            provider: $this->ai->provider(),
            model: $this->ai->model('review'),
        );

        return $this->clean($response->toArray());
    }

    // Writes the draft's source resume in the draft's language, or in its own language for a copy. Uploaded PDFs are read;
    // resumes made here are sent as text.
    public function translate(Resume $draft): array
    {
        $source = $draft->translatedFrom()->firstOrFail();
        $copy = $draft->source === 'copy';
        $language = LanguageGuess::name($draft->locale) ?? 'English';
        $uploaded = $source->source === 'uploaded';
        $response = (new ResumeTranslator($copy ? null : $draft->locale))->prompt(
            $copy ? 'Read the attached resume into the fields, in the language it is written in.'
                : ($uploaded ? "Translate the attached resume into {$language}." : "Translate this resume into {$language}.\n\n".json_encode(Arr::except($source->content ?? [], ['name', 'contact']), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
            attachments: $uploaded ? [Document::fromStorage($source->path, 'local')] : [],
            provider: $this->ai->provider(),
            model: $this->ai->model('review'),
        );
        $data = $response->toArray();
        $cv = $data['cv'] ?? [];
        $contact = $uploaded ? ($cv['contact'] ?? []) : ($source->content['contact'] ?? []);

        return [
            'content' => Cv::clean([
                ...$cv,
                'name' => $uploaded ? (($cv['name'] ?? '') ?: $source->candidate?->user?->name) : ($source->content['name'] ?? ''),
                'contact' => $contact ?: Cv::contact($source->candidate),
            ]),
            'source_language' => LanguageGuess::normalize($data['source_language'] ?? null),
            'model' => $response->meta->model ?? $this->ai->model('review'),
            'input_tokens' => (int) ($response->usage->inputTokens ?? 0),
            'output_tokens' => (int) ($response->usage->outputTokens ?? 0),
        ];
    }

    private function clean(array $data): array
    {
        $text = fn ($value, int $max = 255) => Str::limit(trim((string) ($value ?? '')), $max, '');
        $url = fn ($value) => filter_var($value = $text($value), FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $value) ? $value : '';
        $words = fn ($values, int $max) => collect(is_array($values) ? $values : [])->map(fn ($value) => $text($value, 80))
            ->filter()->unique(fn ($value) => mb_strtolower($value))->take($max)->values()->all();
        $country = strtoupper($text($data['country_code'] ?? '', 2));

        return [
            'language' => LanguageGuess::normalize($data['language'] ?? null) ?? '',
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

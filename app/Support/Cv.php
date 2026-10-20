<?php

namespace App\Support;

use App\Models\Candidate;
use Illuminate\Support\Str;

// One CV shape for resumes made on Nextward, AI translations and the CVs in kits.
class Cv
{
    public const ROWS = [
        'experience' => ['title', 'company', 'location', 'start', 'end', 'current', 'highlights'],
        'education' => ['school', 'degree', 'field', 'start', 'end'],
        'projects' => ['name', 'summary', 'url'],
    ];

    // Keeps the CV in one known shape, whatever the AI or the form sent.
    public static function clean(array $cv): array
    {
        $text = fn ($value, int $max = 300) => Str::limit(trim(is_scalar($value) ? (string) $value : ''), $max, '');
        $words = fn ($values, int $max, int $length = 80) => collect(is_array($values) ? $values : [])->map(fn ($value) => $text($value, $length))->filter()->take($max)->values()->all();
        $rows = fn ($rows, array $keys, int $max) => collect(is_array($rows) ? $rows : [])->filter(fn ($row) => is_array($row))
            ->map(function ($row) use ($keys, $text, $words) {
                $clean = [];
                foreach ($keys as $key) {
                    $clean[$key] = match ($key) {
                        'highlights' => $words($row[$key] ?? [], 12, 500),
                        'current' => filter_var($row[$key] ?? false, FILTER_VALIDATE_BOOLEAN),
                        'summary' => $text($row[$key] ?? '', 2000),
                        default => $text($row[$key] ?? ''),
                    };
                }
                // Older drafts wrote "current" as the end date.
                if (array_key_exists('current', $clean) && preg_match('/^(current|present|now|ongoing|aujourd|actuel|en cours|heute|actual|oggi|atual|heden)/iu', $clean['end'])) {
                    [$clean['current'], $clean['end']] = [true, ''];
                }
                return $clean;
            })
            ->filter(fn ($row) => $row[$keys[0]] !== '')->take($max)->values()->all();

        return [
            'name' => $text($cv['name'] ?? '', 150),
            'contact' => $words($cv['contact'] ?? [], 8, 255),
            'headline' => $text($cv['headline'] ?? '', 150),
            'summary' => $text($cv['summary'] ?? '', 5000),
            'skills' => $words($cv['skills'] ?? [], 40),
            'experience' => $rows($cv['experience'] ?? [], self::ROWS['experience'], 30),
            'education' => $rows($cv['education'] ?? [], self::ROWS['education'], 20),
            'projects' => $rows($cv['projects'] ?? [], self::ROWS['projects'], 20),
            'languages' => $words($cv['languages'] ?? [], 20),
        ];
    }

    // Email, phone, location, website and LinkedIn, in that order.
    public static function contact(Candidate $candidate): array
    {
        $user = $candidate->user;

        return array_values(array_filter([$user?->email, $user?->phone_number, $candidate->location ?: $candidate->city?->name, $candidate->website, $user?->linkedin_profile_url]));
    }

    // The profile as it is today, laid out as a CV. Nothing is rewritten.
    public static function fromProfile(Candidate $candidate): array
    {
        $candidate->loadMissing('user', 'city', 'skills');
        $skills = $candidate->skills->pluck('name')->merge($candidate->preferences['skills'] ?? [])
            ->map(fn ($skill) => trim((string) $skill))->filter()->unique(fn ($skill) => mb_strtolower($skill))->values()->all();

        return self::clean([
            'name' => $candidate->user?->name,
            'contact' => self::contact($candidate),
            'headline' => $candidate->headline,
            'summary' => $candidate->about,
            'skills' => $skills,
            'experience' => collect($candidate->experience ?: [])->map(fn ($row) => [...$row, 'highlights' => self::lines($row['summary'] ?? '')])->all(),
            'education' => $candidate->education ?: [],
            'projects' => $candidate->projects ?: [],
            'languages' => $candidate->languages ?: [],
        ]);
    }

    // A job summary as bullet lines: one per line, or one per sentence when it is a single paragraph.
    public static function lines(?string $text): array
    {
        $lines = collect(preg_split('/\R+/u', trim((string) $text)))->map(fn ($line) => trim(preg_replace('/^\s*[-•*·–]\s*/u', '', $line)))->filter();
        if ($lines->count() === 1) {
            $lines = collect(preg_split('/(?<=[.!?])\s+(?=\p{Lu})/u', $lines->first()))->map('trim')->filter();
        }

        return $lines->values()->all();
    }

    // Blank CV for the editor.
    public static function blank(): array
    {
        return self::clean([]);
    }
}

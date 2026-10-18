<?php

namespace App\Support;

// Work history, education and projects on a candidate profile: one shape for Settings, the resume reader and CVs.
class ProfileHistory
{
    public const FIELDS = [
        'experience' => ['title', 'company', 'location', 'start', 'end', 'current', 'summary'],
        'education' => ['school', 'degree', 'field', 'start', 'end'],
        'projects' => ['name', 'summary', 'url'],
    ];

    public static function rules(): array
    {
        $date = 'nullable|string|max:20';
        return [
            'experience' => 'nullable|array|max:30',
            'experience.*.title' => 'required|string|max:150',
            'experience.*.company' => 'nullable|string|max:150',
            'experience.*.location' => 'nullable|string|max:150',
            'experience.*.start' => $date,
            'experience.*.end' => $date,
            'experience.*.current' => 'nullable|boolean',
            'experience.*.summary' => 'nullable|string|max:3000',
            'education' => 'nullable|array|max:20',
            'education.*.school' => 'required|string|max:150',
            'education.*.degree' => 'nullable|string|max:150',
            'education.*.field' => 'nullable|string|max:150',
            'education.*.start' => $date,
            'education.*.end' => $date,
            'projects' => 'nullable|array|max:20',
            'projects.*.name' => 'required|string|max:150',
            'projects.*.summary' => 'nullable|string|max:2000',
            'projects.*.url' => 'nullable|url:http,https|max:255',
        ];
    }

    // Keeps known keys only, trims text and drops empty rows.
    public static function clean(string $list, ?array $rows): array
    {
        $keys = self::FIELDS[$list];
        return collect($rows ?: [])->filter(fn ($row) => is_array($row))->map(function (array $row) use ($keys) {
            $clean = [];
            foreach ($keys as $key) {
                $value = $row[$key] ?? null;
                $clean[$key] = $key === 'current' ? (bool) $value : (is_scalar($value) ? trim((string) $value) : '');
            }
            return $clean;
        })->filter(fn (array $row) => $row[$keys[0]] !== '')->values()->all();
    }
}

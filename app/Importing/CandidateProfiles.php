<?php

namespace App\Importing;

use App\Leads\Eligibility;
use App\Models\Candidate;
use App\Models\Field;
use App\Models\SearchProfile;

// What signed-up candidates are looking for, plus profiles read from resumes with `opportunities:profile`.
// Imports only add a listing when at least one profile wants it.
class CandidateProfiles
{
    public function __construct(public readonly array $profiles = []) {}

    // The profiles imports use, or none when matching is off.
    public static function forImports(): self
    {
        return config('importer.profiles.enabled') ? self::load() : new self();
    }

    public static function load(): self
    {
        $fields = Field::pluck('name', 'id');
        $ignored = array_map('strtolower', config('importer.profiles.ignore_domains', []));
        // Nextward is the owner's job search for now, so by default only admins' profiles count.
        $admins = config('importer.profiles.from', 'admins') === 'admins';
        $profiles = Candidate::with('skills', 'user')->whereHas('user', fn ($query) => $query->when($admins, fn ($query) => $query->where('is_admin', true)))->get()
            ->reject(fn ($candidate) => in_array(strtolower(substr(strrchr((string) $candidate->user->email, '@') ?: '', 1)), $ignored, true))
            ->map(function ($candidate) use ($fields) {
                $preferences = $candidate->preferences ?: [];
                return self::clean([
                    'name' => $candidate->user->name,
                    'fields' => collect([$candidate->field_id, ...($preferences['fields'] ?? [])])->map(fn ($id) => $fields[$id] ?? null)->all(),
                    'skills' => [...$candidate->skills->pluck('name'), ...($preferences['skills'] ?? [])],
                    'kinds' => $preferences['kinds'] ?? [],
                    'seniority' => $preferences['seniority'] ?? [],
                    'countries' => $preferences['countries'] ?? [],
                    'country' => $candidate->country_code,
                    'needs_visa' => $preferences['needs_visa'] ?? false,
                ]);
            })->values()->all();

        return new self(array_values(array_filter([...$profiles, ...array_values(self::saved())], fn ($profile) => $profile['fields'] || $profile['skills'])));
    }

    // Profiles read from resumes, by name.
    public static function saved(): array
    {
        return SearchProfile::orderBy('name')->get()->mapWithKeys(fn ($row) => [$row->name => self::clean(['name' => $row->name] + $row->data)])->all();
    }

    public static function save(string $name, array $profile): array
    {
        $profile = self::clean(['name' => $name] + $profile);
        SearchProfile::updateOrCreate(['name' => $name], ['data' => $profile]);
        return $profile;
    }

    public static function forget(string $name): bool
    {
        return (bool) SearchProfile::where('name', $name)->delete();
    }

    public function isEmpty(): bool
    {
        return ! $this->profiles;
    }

    // A listing is wanted when one profile takes its kind, level and place, and shares its field or two of its skills.
    public function wants(array $data, ?string $field, array $skills): bool
    {
        $skills = array_map('mb_strtolower', $skills);
        foreach ($this->profiles as $profile) {
            if ($profile['kinds'] && ! in_array($data['kind'] ?? null, $profile['kinds'], true)) { continue; }
            $seniority = $data['seniority'] ?? null;
            if ($profile['seniority'] && $seniority && $seniority !== 'unspecified' && ! in_array($seniority, $profile['seniority'], true)) { continue; }
            if (! $this->place($data, $profile)) { continue; }
            $shared = array_intersect($skills, array_map('mb_strtolower', $profile['skills']));
            if (in_array($field, $profile['fields'], true) || count($shared) >= 2) { return true; }
        }
        return false;
    }

    // Field counts and the most common skills, for command output.
    public function summary(): array
    {
        $count = fn (string $key) => collect($this->profiles)->pluck($key)->flatten()->countBy()->sortDesc();
        return ['fields' => $count('fields')->all(), 'skills' => $count('skills')->keys()->take(15)->all()];
    }

    // With needs_visa (someone in Tunisia who needs a visa to work abroad), on-site and hybrid roles need visa sponsorship
    // or relocation, remote roles must be open to Tunisia, and preferred countries only limit the visa roles.
    private function place(array $data, array $profile): bool
    {
        $country = $profile['country'];
        if ($country && in_array($country, $data['excluded_countries'] ?? [], true)) { return false; }
        if (! empty($profile['needs_visa'])) {
            $class = Eligibility::classify($data)['class'];
            if ($class === 'excluded') { return false; }
            if ($class !== 'visa') { return true; }
        }
        if (! $profile['countries']) { return true; }
        $countries = array_filter([$data['country_code'] ?? null, ...array_column($data['locations'] ?? [], 'country_code')]);
        return array_intersect($countries, $profile['countries']) || ($data['work_type'] ?? null) === 'remote';
    }

    private static function clean(array $profile): array
    {
        $list = fn ($values) => array_values(array_unique(array_filter(array_map(fn ($value) => is_string($value) ? trim($value) : null, (array) ($values ?? [])))));
        $country = strtoupper(trim((string) ($profile['country'] ?? '')));
        return [
            'name' => (string) ($profile['name'] ?? ''),
            'fields' => $list($profile['fields'] ?? []),
            'skills' => $list($profile['skills'] ?? []),
            'kinds' => array_values(array_intersect($list($profile['kinds'] ?? []), ['internship', 'job'])),
            'seniority' => $list($profile['seniority'] ?? []),
            'countries' => array_map('strtoupper', $list($profile['countries'] ?? [])),
            'country' => preg_match('/^[A-Z]{2}$/', $country) ? $country : null,
            'needs_visa' => (bool) ($profile['needs_visa'] ?? false),
        ];
    }
}

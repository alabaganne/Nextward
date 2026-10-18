<?php

namespace App\Importing;

use App\Models\Opportunity;
use App\Models\OpportunitySource;
use Generator;
use RuntimeException;

// One row per opportunity: readable columns first, then what another database needs to add it again.
class OpportunityCsv
{
    public const COLUMNS = [
        'title', 'company', 'kind', 'field', 'location', 'posted_at', 'link',
        'source_key', 'external_id', 'source_url', 'first_seen_at', 'last_verified_at', 'normalized_data', 'ai_review',
    ];

    public static function path(?string $path = null): string
    {
        $path ??= config('importer.csv_path');
        return str_starts_with($path, '/') ? $path : base_path($path);
    }

    // Appends opportunities that are not in the file yet and returns how many rows were written.
    public static function append(string $path, iterable $roles): int
    {
        $seen = [];
        foreach (is_file($path) ? self::rows($path) : [] as $row) { $seen[$row['source_key'].'|'.$row['external_id']] = true; }
        if (! is_dir(dirname($path))) { mkdir(dirname($path), 0775, true); }
        $new = ! is_file($path) || filesize($path) === 0;
        $file = fopen($path, 'a');
        if ($new) {
            fwrite($file, "\xEF\xBB\xBF");
            fputcsv($file, self::COLUMNS, escape: '');
        }
        $written = 0;
        foreach ($roles as $role) {
            $row = self::row($role);
            if (! $row || isset($seen[$row['source_key'].'|'.$row['external_id']])) { continue; }
            fputcsv($file, array_values($row), escape: '');
            $seen[$row['source_key'].'|'.$row['external_id']] = true;
            $written++;
        }
        fclose($file);
        return $written;
    }

    public static function rows(string $path): Generator
    {
        $file = @fopen($path, 'r');
        if (! $file) { throw new RuntimeException("Cannot read {$path}."); }
        $header = fgetcsv($file, escape: '');
        if ($header) { $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]); }
        if ($header !== self::COLUMNS) { fclose($file); throw new RuntimeException("{$path} is not an opportunities CSV file."); }
        while (($values = fgetcsv($file, escape: '')) !== false) {
            if ($values === [null]) { continue; }
            yield array_combine(self::COLUMNS, array_pad($values, count(self::COLUMNS), ''));
        }
        fclose($file);
    }

    private static function row(Opportunity $role): ?array
    {
        $item = $role->sources()->with('importSource')->whereNotNull('normalized_data')->orderBy('id')->first();
        if (! $item instanceof OpportunitySource || ! $item->importSource) { return null; }
        return [
            'title' => $role->title, 'company' => $role->company?->display_name, 'kind' => $role->kind,
            'field' => $role->field?->name, 'location' => $role->location_label, 'posted_at' => $role->posted_at?->toDateString(),
            'link' => $role->application_url, 'source_key' => $item->importSource->key, 'external_id' => $item->external_id,
            'source_url' => $item->source_url, 'first_seen_at' => $item->first_seen_at?->toIso8601String(),
            'last_verified_at' => $item->last_verified_at?->toIso8601String(),
            'normalized_data' => json_encode($item->normalized_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'ai_review' => $item->ai_review ? json_encode($item->ai_review, JSON_UNESCAPED_UNICODE) : '',
        ];
    }
}

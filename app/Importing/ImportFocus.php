<?php

namespace App\Importing;

use Illuminate\Support\Str;
use RuntimeException;

class ImportFocus
{
    // Returns [title regex, field names] for a group in importer.focus, or null when the group is unknown.
    // Extra keywords widen the title words for one run.
    public static function for(?string $group, array $keywords = []): ?array
    {
        $config = $group ? config('importer.focus.'.$group) : [];
        if ($group && ! $config) { return null; }
        $keywords = array_filter(array_map(fn ($word) => Str::ascii(mb_strtolower(trim($word))), $keywords));
        $include = [...($config['include'] ?? []), ...array_map(fn ($word) => preg_quote($word, '/'), $keywords)];
        $word = fn (array $words) => '(?<![a-z0-9])(?:'.implode('|', array_filter($words)).')(?![a-z0-9])';
        // A title must contain an include word and no exclude word, e.g. "business developer" is a sales role.
        $pattern = $include ? '/^'.(! empty($config['exclude']) ? '(?!.*'.$word($config['exclude']).')' : '').'.*'.$word($include).'/' : null;
        return [$pattern, $config['fields'] ?? null];
    }

    // The site-wide scope every import keeps to, as [title regex, field names]; both null when every field is taken.
    public static function scope(): array
    {
        $group = config('importer.scope') ?: null;
        return self::for($group) ?? throw new RuntimeException("Unknown importer scope \"{$group}\". Add it under focus in config/importer.php.");
    }

    // The lowercase, accent-free title a focus regex is matched against.
    public static function title(string $title): string
    {
        return Str::ascii(mb_strtolower(str_replace(['-', '_', '/'], ' ', $title)));
    }
}

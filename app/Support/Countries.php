<?php

namespace App\Support;

// Country names from a saved list for English and French, and from intl for other languages when it is installed.
class Countries
{
    private static ?array $names = null;

    public static function name(?string $code, string $language = 'en'): ?string
    {
        if (! $code) { return null; }
        $code = strtoupper($code);
        self::$names ??= require resource_path('data/countries.php');
        if ($name = self::$names[$language][$code] ?? null) { return $name; }
        $name = class_exists(\Locale::class) ? \Locale::getDisplayRegion('-'.$code, $language) : '';

        return $name && $name !== $code && ! str_contains($name, 'Unknown') ? $name : (self::$names['en'][$code] ?? $code);
    }
}

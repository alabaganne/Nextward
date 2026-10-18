<?php

namespace App\Support;

// A quick English / French / Arabic guess from common words, used when a source does not give a language.
class LanguageGuess
{
    private const WORDS = [
        'en' => ['the', 'and', 'for', 'with', 'you', 'we', 'our', 'is', 'are', 'to', 'of', 'in', 'on', 'your', 'will', 'be', 'this', 'that', 'as', 'an', 'at', 'experience', 'work'],
        'fr' => ['le', 'la', 'les', 'des', 'du', 'de', 'et', 'pour', 'avec', 'vous', 'nous', 'une', 'est', 'sur', 'dans', 'au', 'aux', 'par', 'votre', 'notre', 'qui', 'que', 'expérience', 'équipe', 'poste'],
    ];

    private const NAMES = [
        'en' => ['English', 'anglais'], 'fr' => ['French', 'français'], 'ar' => ['Arabic', 'arabe'],
        'de' => ['German', 'allemand'], 'es' => ['Spanish', 'espagnol'], 'it' => ['Italian', 'italien'],
        'pt' => ['Portuguese', 'portugais'], 'nl' => ['Dutch', 'néerlandais'], 'pl' => ['Polish', 'polonais'],
    ];

    public static function name(?string $code, string $locale = 'en'): ?string
    {
        $names = self::NAMES[self::normalize($code)] ?? null;
        return $names ? $names[$locale === 'fr' ? 1 : 0] : null;
    }

    public static function normalize(?string $code): ?string
    {
        $code = strtolower(substr(trim((string) $code), 0, 2));
        return preg_match('/^[a-z]{2}$/', $code) ? $code : null;
    }

    public static function guess(string $text): ?string
    {
        $text = mb_strtolower(mb_substr($text, 0, 6000));
        $letters = preg_match_all('/\p{L}/u', $text);
        if ($letters && preg_match_all('/\p{Arabic}/u', $text) / $letters > 0.3) {
            return 'ar';
        }
        preg_match_all('/\p{L}+/u', $text, $matches);
        $counts = array_count_values($matches[0]);
        $score = fn (string $locale) => array_sum(array_map(fn ($word) => $counts[$word] ?? 0, self::WORDS[$locale]));
        // At least three different common words, so one shared word like "team" does not decide.
        $distinct = fn (string $locale) => count(array_filter(self::WORDS[$locale], fn ($word) => isset($counts[$word])));
        [$en, $fr] = [$score('en'), $score('fr')];
        if ($en + $fr < 5) {
            return null;
        }
        if ($fr > $en * 1.5 && $distinct('fr') >= 3) {
            return 'fr';
        }
        return $en > $fr * 1.5 && $distinct('en') >= 3 ? 'en' : null;
    }
}

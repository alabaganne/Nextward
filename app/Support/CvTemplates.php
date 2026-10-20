<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

// The resume themes (from Resume Studio): layouts, accents, type and paper, plus section names in each CV language.
class CvTemplates
{
    public const TEMPLATES = [
        'ravenna' => ['name' => 'Ravenna', 'family' => 'Editorial', 'desc' => 'Centred serif masthead, classic and refined.', 'ats_safe' => true],
        'halden' => ['name' => 'Halden', 'family' => 'Modern', 'desc' => 'Left-aligned with a quiet accent rule.', 'ats_safe' => true],
        'marlow' => ['name' => 'Marlow', 'family' => 'Minimal', 'desc' => 'Lots of white space, hairline dividers.', 'ats_safe' => true],
        'soren' => ['name' => 'Sören', 'family' => 'Two columns', 'desc' => 'Side column for contact, skills and education. Some ATS read it less well.', 'ats_safe' => false],
        'atlas' => ['name' => 'Atlas', 'family' => 'Bold', 'desc' => 'Colour header block, confident scale.', 'ats_safe' => true],
        'verona' => ['name' => 'Verona', 'family' => 'Classic', 'desc' => 'Traditional and plain. The safest pick for ATS.', 'ats_safe' => true],
    ];

    // oklch for the page, hex for Word.
    public const ACCENTS = [
        'forest' => ['name' => 'Forest', 'value' => 'oklch(0.5 0.1 162)', 'hex' => '2E7D5B'],
        'ink' => ['name' => 'Ink', 'value' => 'oklch(0.28 0.012 250)', 'hex' => '2B2F36'],
        'navy' => ['name' => 'Navy', 'value' => 'oklch(0.42 0.1 250)', 'hex' => '2A4B7C'],
        'claret' => ['name' => 'Claret', 'value' => 'oklch(0.46 0.13 18)', 'hex' => '8E2B3F'],
        'amber' => ['name' => 'Amber', 'value' => 'oklch(0.58 0.12 65)', 'hex' => 'B5791F'],
        'plum' => ['name' => 'Plum', 'value' => 'oklch(0.45 0.12 320)', 'hex' => '7A3F86'],
    ];

    public const FONTS = ['sans' => 'Sans (Hanken Grotesk)', 'serif' => 'Serif (Newsreader)'];

    public const PAPERS = ['a4' => 'A4', 'letter' => 'US Letter'];

    public const DEFAULT = ['template' => 'halden', 'accent' => 'navy', 'font' => 'sans', 'paper' => 'a4'];

    // Languages a CV or letter can be written in. Arabic is left out until the themes handle right-to-left text.
    public const LOCALES = ['en', 'fr', 'de', 'es', 'it', 'pt', 'nl'];

    private const LABELS = [
        'en' => ['summary' => 'Summary', 'profile' => 'Profile', 'experience' => 'Experience', 'education' => 'Education', 'skills' => 'Skills', 'projects' => 'Projects', 'languages' => 'Languages', 'contact' => 'Contact', 'present' => 'Present', 'cv' => 'Resume', 'letter' => 'Cover letter'],
        'fr' => ['summary' => 'Profil', 'profile' => 'Profil', 'experience' => 'Expérience', 'education' => 'Formation', 'skills' => 'Compétences', 'projects' => 'Projets', 'languages' => 'Langues', 'contact' => 'Contact', 'present' => 'aujourd’hui', 'cv' => 'CV', 'letter' => 'Lettre de motivation'],
        'de' => ['summary' => 'Kurzprofil', 'profile' => 'Profil', 'experience' => 'Berufserfahrung', 'education' => 'Ausbildung', 'skills' => 'Kenntnisse', 'projects' => 'Projekte', 'languages' => 'Sprachen', 'contact' => 'Kontakt', 'present' => 'heute', 'cv' => 'Lebenslauf', 'letter' => 'Anschreiben'],
        'es' => ['summary' => 'Resumen', 'profile' => 'Perfil', 'experience' => 'Experiencia', 'education' => 'Formación', 'skills' => 'Habilidades', 'projects' => 'Proyectos', 'languages' => 'Idiomas', 'contact' => 'Contacto', 'present' => 'actualidad', 'cv' => 'CV', 'letter' => 'Carta de presentación'],
        'it' => ['summary' => 'Profilo', 'profile' => 'Profilo', 'experience' => 'Esperienza', 'education' => 'Formazione', 'skills' => 'Competenze', 'projects' => 'Progetti', 'languages' => 'Lingue', 'contact' => 'Contatti', 'present' => 'oggi', 'cv' => 'CV', 'letter' => 'Lettera di presentazione'],
        'pt' => ['summary' => 'Resumo', 'profile' => 'Perfil', 'experience' => 'Experiência', 'education' => 'Formação', 'skills' => 'Competências', 'projects' => 'Projetos', 'languages' => 'Idiomas', 'contact' => 'Contacto', 'present' => 'atual', 'cv' => 'CV', 'letter' => 'Carta de apresentação'],
        'nl' => ['summary' => 'Samenvatting', 'profile' => 'Profiel', 'experience' => 'Werkervaring', 'education' => 'Opleiding', 'skills' => 'Vaardigheden', 'projects' => 'Projecten', 'languages' => 'Talen', 'contact' => 'Contact', 'present' => 'heden', 'cv' => 'CV', 'letter' => 'Motivatiebrief'],
    ];

    public static function locale(?string $locale): string
    {
        return in_array($locale, self::LOCALES, true) ? $locale : 'en';
    }

    public static function labels(?string $locale): array
    {
        return self::LABELS[self::locale($locale)];
    }

    // A full, valid style from whatever was saved or sent.
    public static function style(?array $style): array
    {
        $style = is_array($style) ? $style : [];
        $pick = fn (string $key, array $options) => isset($style[$key]) && is_string($style[$key]) && array_key_exists($style[$key], $options) ? $style[$key] : self::DEFAULT[$key];

        return [
            'template' => $pick('template', self::TEMPLATES),
            'accent' => $pick('accent', self::ACCENTS),
            'font' => $pick('font', self::FONTS),
            'paper' => $pick('paper', self::PAPERS),
        ];
    }

    public static function rules(string $prefix = 'style'): array
    {
        return [
            $prefix => 'sometimes|array',
            "$prefix.template" => 'sometimes|in:'.implode(',', array_keys(self::TEMPLATES)),
            "$prefix.accent" => 'sometimes|in:'.implode(',', array_keys(self::ACCENTS)),
            "$prefix.font" => 'sometimes|in:'.implode(',', array_keys(self::FONTS)),
            "$prefix.paper" => 'sometimes|in:'.implode(',', array_keys(self::PAPERS)),
        ];
    }

    // What the style picker needs.
    public static function options(): array
    {
        $list = fn (array $items, callable $map) => collect($items)->map(fn ($item, $id) => ['id' => $id, ...$map($item)])->values()->all();

        return [
            'templates' => $list(self::TEMPLATES, fn ($t) => $t),
            'accents' => $list(self::ACCENTS, fn ($a) => ['name' => $a['name'], 'value' => $a['value'], 'hex' => $a['hex']]),
            'fonts' => $list(self::FONTS, fn ($name) => ['name' => $name]),
            'papers' => $list(self::PAPERS, fn ($name) => ['name' => $name]),
            'default' => self::DEFAULT,
        ];
    }

    // "2021-08" becomes "Aug 2021" in the CV's language; other text is kept as written.
    public static function date(?string $value, string $locale): string
    {
        $value = trim((string) $value);
        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $value)) {
            return Carbon::createFromFormat('!Y-m', $value)->locale(self::locale($locale))->translatedFormat('M Y');
        }

        return $value;
    }

    public static function dateRange(?string $start, ?string $end, bool $current, string $locale): string
    {
        $from = self::date($start, $locale);
        $to = $current ? self::labels($locale)['present'] : self::date($end, $locale);

        return $from !== '' && $to !== '' ? "$from — $to" : ($from ?: $to);
    }

    // Sorts contact lines into plain items and links, with the address each link opens.
    public static function contact(array $lines): array
    {
        $items = [];
        $links = [];
        foreach ($lines as $line) {
            $text = trim((string) $line);
            if ($text === '') {
                continue;
            }
            if (filter_var($text, FILTER_VALIDATE_EMAIL)) {
                $items[] = ['text' => $text, 'href' => 'mailto:'.$text];
            } elseif (preg_match('/^\+?[\d\s().-]{6,}$/', $text)) {
                $items[] = ['text' => $text, 'href' => 'tel:'.preg_replace('/[^\d+]/', '', $text)];
            } elseif (preg_match('#^(https?://)?([\w-]+\.)+[a-z]{2,}(/\S*)?$#i', $text)) {
                $links[] = ['text' => preg_replace('#^https?://(www\.)?#i', '', rtrim($text, '/')), 'href' => preg_match('#^https?://#i', $text) ? $text : 'https://'.$text];
            } else {
                $items[] = ['text' => $text, 'href' => null];
            }
        }

        return ['items' => $items, 'links' => $links];
    }

    // "Ala Baganne" and "CV" become "Ala_Baganne_CV".
    public static function fileName(?string $name, string $kind, string $extension = 'pdf'): string
    {
        $base = trim(preg_replace('/_+/', '_', preg_replace('/[^\w-]/', '', preg_replace('/\s+/', '_', Str::ascii(trim(($name ?: '').' '.$kind))))), '_');

        return ($base ?: 'Resume').'.'.$extension;
    }
}

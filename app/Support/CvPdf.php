<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Spatie\Browsershot\Browsershot;

// Renders resumes and cover letters: HTML for the live preview, PDF (real text, working links) through headless Chrome.
class CvPdf
{
    private const FONTS = [
        'Hanken Grotesk' => ['file' => 'hanken-grotesk', 'styles' => ['normal' => [400, 500, 600, 700]]],
        'Newsreader' => ['file' => 'newsreader', 'styles' => ['normal' => [400, 500, 600, 700], 'italic' => [400, 500]]],
    ];

    private const RANGES = [
        'latin' => 'U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD',
        'latin-ext' => 'U+0100-02BA,U+02BD-02C5,U+02C7-02CC,U+02CE-02D7,U+02DD-02FF,U+0304,U+0308,U+0329,U+1D00-1DBF,U+1E00-1E9F,U+1EF2-1EFF,U+2020,U+20A0-20AB,U+20AD-20C0,U+2113,U+2C60-2C7F,U+A720-A7FF',
    ];

    public function html(array $cv, ?array $style, ?string $locale, bool $print = false): string
    {
        return view('cv.document', $this->data(Cv::clean($cv), $style, $locale, $print))->render();
    }

    public function letterHtml(array $cv, string $letter, ?array $style, ?string $locale, bool $print = false): string
    {
        $data = $this->data(Cv::clean($cv), $style, $locale, $print);

        return view('cv.letter', [...$data, 'letter' => trim($letter), 'date' => Carbon::now()->locale($data['locale'])->translatedFormat('j F Y')])->render();
    }

    public function pdf(array $cv, ?array $style, ?string $locale): string
    {
        return $this->print($this->html($cv, $style, $locale, true), CvTemplates::style($style)['paper']);
    }

    public function letterPdf(array $cv, string $letter, ?array $style, ?string $locale): string
    {
        return $this->print($this->letterHtml($cv, $letter, $style, $locale, true), CvTemplates::style($style)['paper']);
    }

    private function data(array $cv, ?array $style, ?string $locale, bool $print): array
    {
        $style = CvTemplates::style($style);
        $locale = CvTemplates::locale($locale);

        return [
            'cv' => $cv,
            'style' => $style,
            'locale' => $locale,
            'labels' => CvTemplates::labels($locale),
            'accent' => CvTemplates::ACCENTS[$style['accent']]['value'],
            'print' => $print,
            'title' => trim(($cv['name'] ?: '').' '.CvTemplates::labels($locale)['cv']),
            'css' => $this->fonts($style['font'], $print).file_get_contents(resource_path('css/cv.css'))
                .($print ? '@page { size: '.($style['paper'] === 'letter' ? 'letter' : 'A4').'; margin: 0; }' : ''),
        ];
    }

    // Serif themes still use the sans face for dates, contact and section titles. PDFs get the fonts inline so Chrome loads nothing.
    private function fonts(string $font, bool $print): string
    {
        $families = $font === 'serif' ? ['Hanken Grotesk', 'Newsreader'] : ['Hanken Grotesk'];
        $css = '';
        foreach ($families as $family) {
            foreach (self::FONTS[$family]['styles'] as $fontStyle => $weights) {
                foreach ($weights as $weight) {
                    foreach (self::RANGES as $subset => $range) {
                        $file = self::FONTS[$family]['file']."-$subset-$weight-$fontStyle.woff2";
                        $src = $print ? 'data:font/woff2;base64,'.base64_encode((string) file_get_contents(public_path("fonts/cv/$file"))) : "/fonts/cv/$file";
                        $css .= "@font-face{font-family:'$family';font-style:$fontStyle;font-weight:$weight;font-display:block;src:url($src) format('woff2');unicode-range:$range}";
                    }
                }
            }
        }

        return $css;
    }

    private function print(string $html, string $paper): string
    {
        if (config('cv.fake')) {
            return "%PDF-1.4\n% Nextward test PDF\n".strip_tags($html)."\n%%EOF";
        }

        // Browsershot refuses HTML strings that mention local files or hosts, which a CV may do in plain text, so print from a file.
        $directory = storage_path('app/cv-print');
        is_dir($directory) || mkdir($directory, 0775, true);
        $path = $directory.'/'.Str::random(32).'.html';
        file_put_contents($path, $html);

        try {
            $shot = Browsershot::htmlFromFilePath($path)
                ->setNodeModulePath(base_path('node_modules'))
                ->disableJavascript()
                ->showBackground()
                ->margins(0, 0, 0, 0)
                ->format($paper === 'letter' ? 'Letter' : 'A4')
                ->timeout(config('cv.timeout', 60));
            if ($chrome = config('cv.chrome') ?: collect(['/usr/bin/google-chrome', '/usr/bin/chromium', '/usr/bin/chromium-browser'])->first(fn ($bin) => is_executable($bin))) {
                $shot->setChromePath($chrome);
            }
            config('cv.node') && $shot->setNodeBinary(config('cv.node'));
            config('cv.npm') && $shot->setNpmBinary(config('cv.npm'));
            config('cv.no_sandbox') && $shot->noSandbox();

            return $shot->pdf();
        } finally {
            @unlink($path);
        }
    }
}

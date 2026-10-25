<?php

namespace App\Leads;

use Illuminate\Support\Str;

// Can someone living in Tunisia take this role? remote (fully remote and hires from Tunisia), visa (on site or hybrid
// abroad with visa sponsorship or relocation), excluded, or unclear. The employer's own words beat board labels.
class Eligibility
{
    // Places that include Tunisia.
    private const OPEN = 'worldwide|world ?wide|anywhere(?: in the world)?|globally|global(?:ly)? remote|remote global|any country|all countries|any location|emea|africa|mena|middle east (?:and|&) north africa|north africa|maghreb|tunisia|tunisie|francophone';
    // Places that leave Tunisia out.
    private const OTHER = 'the u\.?s\.?a?|u\.?s\.?a?|united states(?: of america)?|america|the americas|americas|canada|north america|latam|latin america|south america|central america|europe|the eu|eu|european union|eea|the uk|uk|u\.k\.|united kingdom|great britain|britain|england|germany|france|spain|portugal|netherlands|belgium|poland|ireland|italy|switzerland|austria|sweden|denmark|norway|finland|nordics?|scandinavia|apac|asia(?:[- ]pacific)?|australia|new zealand|india|brazil|mexico|argentina|colombia|chile|philippines|pakistan|singapore|japan|israel|nigeria|kenya|south africa|egypt|turkey';

    public static function classify(array $data, ?string $text = null): array
    {
        $text ??= (string) ($data['description'] ?? '');
        $sentences = array_filter(array_map('trim', preg_split('/(?<=[.!?;])\s+|\R/u', $text)));
        $quote = fn (?string $sentence) => $sentence ? Str::limit(Str::squish($sentence), 300) : null;
        $evidence = (array) ($data['eligibility_evidence'] ?? []);
        $work = $data['work_type'] ?? 'unknown';
        $places = Str::squish(implode(' ', array_filter([$data['location'] ?? null, $data['remote_scope'] ?? null])));
        $excluded = array_map('strtoupper', (array) ($data['excluded_countries'] ?? []));
        $eligible = array_map('strtoupper', (array) ($data['eligible_countries'] ?? []));

        [$restriction, $open, $noVisa, $visa, $tunisiaOut] = self::read($sentences);
        $visa ??= ($data['visa_sponsorship'] ?? null) === 'yes' ? ($evidence['visa_sponsorship'] ?? 'The listing offers visa sponsorship.') : null;
        $visa ??= ($data['relocation'] ?? null) === 'yes' ? ($evidence['relocation'] ?? 'The listing offers relocation.') : null;
        if (($data['visa_sponsorship'] ?? null) === 'no') { $noVisa ??= $evidence['visa_sponsorship'] ?? 'No visa sponsorship.'; }

        if (in_array('TN', $excluded, true) || $tunisiaOut) {
            return self::answer('excluded', $tunisiaOut ?? 'Tunisia is excluded.', $quote);
        }
        if ($work === 'remote' || ($work === 'unknown' && $open)) {
            if ($restriction) { return $visa && ! $noVisa ? self::answer('visa', $visa, $quote) : self::answer('excluded', $restriction, $quote); }
            if ($eligible) {
                return in_array('TN', $eligible, true) ? self::answer('remote', 'Open to: '.implode(', ', $eligible), $quote)
                    : self::answer('excluded', 'Only open to: '.implode(', ', $eligible), $quote);
            }
            if ($open) { return self::answer('remote', $open, $quote); }
            if ($places !== '' && preg_match('/\b(?:'.self::OPEN.')\b/iu', $places)) { return self::answer('remote', 'Location: '.$places, $quote); }
            if (($data['remote_eligibility'] ?? null) === 'eligible' || ($data['applicant_scope'] ?? null) === 'anywhere') {
                return self::answer('remote', $evidence['remote'] ?? 'Open to people in any country.', $quote);
            }
            if ($places !== '' && preg_match('/\b(?:'.self::OTHER.')\b/iu', $places)) { return self::answer('excluded', 'Location: '.$places, $quote); }
            if (($data['remote_eligibility'] ?? null) === 'restricted' || ($data['applicant_scope'] ?? null) === 'local') {
                return self::answer('excluded', $evidence['remote'] ?? 'Limited to other countries.', $quote);
            }
            return self::answer('unclear', 'Remote, but the listing does not say from where.', $quote);
        }
        if (($data['country_code'] ?? null) === 'TN') { return self::answer('unclear', 'On site in Tunisia.', $quote); }
        if ($visa && ! $noVisa) { return self::answer('visa', $visa, $quote); }
        if ($noVisa) { return self::answer('excluded', $noVisa, $quote); }
        if ($restriction) { return self::answer('excluded', $restriction, $quote); }
        if (! in_array($work, ['onsite', 'hybrid'], true)) { return self::answer('unclear', 'The listing does not say where you can work from.', $quote); }
        $where = $places !== '' ? $places : ($data['country_code'] ?? 'abroad');
        return self::answer('excluded', ($work === 'hybrid' ? 'Hybrid' : 'On site').' in '.$where.' with no visa or relocation offer.', $quote);
    }

    // Finds, in the employer's text: a place limit without Tunisia, an open-to-all line, a no-visa line, a visa or
    // relocation offer, and a line that leaves Tunisia out.
    private static function read(array $sentences): array
    {
        $restriction = $open = $noVisa = $visa = $tunisiaOut = null;
        $other = '(?:'.self::OTHER.')';
        foreach ($sentences as $sentence) {
            $s = mb_strtolower(Str::ascii($sentence));
            if (preg_match('/(?:except|excluding|not (?:available|open|hiring)|cannot hire|can\'t hire|unable to hire).{0,40}\btunisi/u', $s)) { $tunisiaOut ??= $sentence; }
            $negative = preg_match('/\b(?:no|not|never|cannot|can\'t|won\'t|unable|without|unfortunately)\b/u', $s);
            if (preg_match('/(?:(?:no|not|never|cannot|can\'t|won\'t|will not|unable to|do not|don\'t|does not|doesn\'t)\s+(?:currently\s+|be\s+able\s+to\s+)?(?:offer\s+|provide\s+|support\s+)?(?:any\s+)?(?:visa\s+)?sponsor)|sponsorship (?:is )?not (?:available|offered|provided)|without (?:the need for )?(?:visa )?sponsorship|not eligible for (?:visa )?sponsorship/u', $s)) {
                $noVisa ??= $sentence;
            } elseif (preg_match('/visa sponsorship|sponsor (?:your |a |the )?(?:work |employment )?(?:visas?|permits?)|visa support|relocation (?:package|support|assistance|bonus|budget)|help (?:you )?relocate|relocate you/u', $s)
                && ! $negative && ! preg_match('/\b(?:may|might|case.by.case|depending)\b/u', $s)) {
                $visa ??= $sentence;
            }
            $limit = '(?:(?:must|need to|required to|should)\s+(?:be\s+)?(?:based|located|reside|residing|live|living)\s+(?:in|within)|(?:only|exclusively)\s+(?:open\s+to|hiring|accepting|considering|available\s+to|recruiting)\s+(?:candidates|applicants|people|residents|talent)?\s*(?:who\s+(?:are|live)\s+)?(?:in|from|based\s+in|located\s+in|residing\s+in|within)|(?:candidates|applicants)\s+(?:must\s+be\s+)?(?:based|located|residing)\s+(?:only\s+)?in|(?:authori[sz]ed|eligible|legally\s+(?:able|allowed|permitted)|right|permission|permit)\s+to\s+work\s+(?:in|for)|work\s+authori[sz]ation\s+(?:in|for)|(?:residents?|citizens?)\s+of|open\s+to\s+(?:candidates\s+|applicants\s+)?(?:in|from)\s+(?:the\s+)?)\s*(?:the\s+)?'.$other.'\b';
            if (preg_match('/'.$limit.'(?P<tail>.{0,60})/u', $s, $m) || preg_match('/\b'.$other.'\s*(?:-|,|\()?\s*(?:only|based only|residents only)\b(?P<tail>.{0,40})/u', $s, $m)
                || preg_match('/\b(?:us|u\.s\.) (?:citizens?(?:hip)?|persons?)\b|\bgreen card\b|security clearance/u', $s, $m)) {
                // "based in Europe or Africa" still includes Tunisia.
                if (! preg_match('/\b(?:'.self::OPEN.')\b/u', $m['tail'] ?? '') && ! preg_match('/\bor (?:remote|anywhere)\b/u', $m['tail'] ?? '')) { $restriction ??= $sentence; }
            }
            if (! $negative && preg_match('/(?:hire|hiring|recruit\w*|employ\w*|open|work(?:ing)?(?: remotely)? from|candidates?|applicants?|talent|based|located|living|reside)\b.{0,40}\b(?:'.self::OPEN.')\b|\bremote\s*(?:[-(:,\/]\s*)?(?:'.self::OPEN.')\b|\b(?:'.self::OPEN.')\b.{0,30}\b(?:applicants?|candidates?|eligible|welcome)\b/u', $s)) {
                $open ??= $sentence;
            }
        }
        return [$restriction, $open, $noVisa, $visa, $tunisiaOut];
    }

    private static function answer(string $class, string $evidence, callable $quote): array
    {
        return ['class' => $class, 'quote' => $quote($evidence)];
    }
}

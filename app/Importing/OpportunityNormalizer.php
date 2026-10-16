<?php

namespace App\Importing;

use Carbon\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

class OpportunityNormalizer
{
    public function normalize(array $input): array
    {
        $title = Str::limit($this->text($input['title'] ?? ''), 250, '');
        $description = Str::limit($this->text($input['description'] ?? ''), 80000, '');
        $employer = Str::limit($this->text($input['employer'] ?? ''), 250, '');
        if (! $title || mb_strlen($description) < 100 || ! $employer) {
            throw new RuntimeException('A title, identified employer and substantive description are required.');
        }
        $apply = $this->encodeUrl($input['application_url'] ?? '');
        if (! $this->publicUrl($apply)) {
            throw new RuntimeException('A valid public application URL is required.');
        }
        $types = $this->employmentTypes($input['employment_types'] ?? []);
        // Some boards tick every contract type, so INTERN only counts when no regular contract type is listed with it.
        $internship = (in_array('INTERN', $types, true) && ! array_diff($types, ['INTERN', 'OTHER', 'VOLUNTEER']))
            || preg_match('/\b(intern(?:ship)?s?|stagiaire|stage (?:pfe|de fin|d.été|en |dans )|pfe|apprentice(?:ship)?|alternance|co-op|working student|werkstudent|praktikum)\b/iu', $title)
            || preg_match('/(?:^|\s[-–—|:\/]|[(,])\s*stages?\b(?!\s+manager)/iu', $title);
        $program = preg_match('/\bpfe\b|fin d[’\x27]études/iu', $title) ? 'pfe'
            : (preg_match('/summer|été/iu', $title) ? 'summer' : null);
        $seniority = $internship ? 'entry' : null;
        foreach (['executive' => '\b(chief|c[tefo]o|vp|vice president)\b', 'lead' => '\b(lead|principal|head of)\b',
            'senior' => '\b(senior|sr\.?|confirmé)\b', 'entry' => '\b(junior|entry.level|débutant)\b', 'graduate' => '\b(graduate|jeune diplômé)\b'] as $level => $pattern) {
            if (preg_match('/'.$pattern.'/iu', $title)) { $seniority = $level; break; }
        }
        $locations = $this->places($input['locations'] ?? []);
        $location = $input['location'] ?? ($locations[0]['location'] ?? null);
        $country = $this->country($input['country_code'] ?? null) ?? ($locations[0]['country_code'] ?? null);
        if (! $country && $location) {
            foreach (array_reverse(preg_split('/[,|\/]/', $location)) as $part) {
                // CA, IN and similar tokens can be states, not countries, in free text.
                if (mb_strlen(trim($part)) > 2 && ($country = $this->country(trim($part)))) { break; }
            }
        }
        if (isset($input['location'])) { $location = $this->place($input['location'], $country); }
        $workType = strtolower($input['work_type'] ?? '');
        $workType = match ($workType) { 'remote' => 'remote', 'hybrid' => 'hybrid', 'onsite', 'on-site', 'on_site' => 'onsite', default => null };
        // One word in the title or location is enough; the description needs a clear phrase, and nothing found stays unknown.
        $workType ??= $this->arrangement($title, false) ?? $this->arrangement((string) $location, false);
        $fromDescription = ! $workType;
        $workType ??= $this->arrangement($description, true);
        $eligible = [];
        foreach ($input['applicant_locations'] ?? [] as $allowed) {
            $name = is_array($allowed) ? ($allowed['name'] ?? $allowed['addressCountry'] ?? '') : $allowed;
            if ($code = $this->country($name)) { $eligible[] = $code; }
        }
        $eligible = array_values(array_unique($eligible));
        $eligibility = $workType === 'remote' && $eligible ? (in_array('TN', $eligible, true) ? 'eligible' : 'restricted') : 'unknown';
        $evidence = [];
        if ($eligible) { $evidence['remote'] = 'Source applicant locations: '.implode(', ', $eligible); }
        $sentences = preg_split('/(?<=[.!?])\s+|\R/u', $description);
        if ($workType === 'remote' && ! $eligible) {
            $restriction = null;
            foreach ($sentences as $sentence) {
                if (preg_match('/\b(?:hire|hiring|recruit|recruiting)\b.{0,50}(?:worldwide|globally|in Tunisia)|\bwork\s+(?:remotely\s+)?from\s+(?:anywhere in the world|Tunisia|anywhere[.!]?$)|(?:applicants?|candidates?).{0,40}(?:in Tunisia|worldwide)|(?:worldwide|Tunisia).{0,40}(?:applicants|candidates|eligible)/iu', $sentence)
                    && ! preg_match('/\b(no|not|except|excluding|cannot|without)\b|ne .{0,30}pas/iu', $sentence)) {
                    $eligibility = 'eligible'; $evidence['remote'] = Str::limit($sentence, 400);
                }
                if (preg_match('/(?:must (?:be (?:based|located)|reside|live)|only (?:hiring in|available in|open to candidates in)|candidates? (?:based|located) in|based exclusively in).{0,70}/iu', $sentence)) {
                    if (! preg_match('/Tunisia|Tunisie|worldwide|anywhere/iu', $sentence)) {
                        $restriction = Str::limit($sentence, 400);
                    }
                }
            }
            if ($restriction) { $eligibility = 'restricted'; $evidence['remote'] = $restriction; }
            // The role's own location beats company-wide "we hire globally" text, also when only the description says remote.
            if ($eligibility !== 'restricted' && ($this->limitedToOtherRegions((string) $location) || ($country && $country !== 'TN')
                || ($fromDescription && $location && ! $country))) {
                $eligibility = 'restricted'; $evidence['remote'] = 'Source location: '.Str::limit((string) ($location ?: $country), 200);
            }
        }
        // A negative Tunisia statement overrides a broader positive hiring statement.
        foreach ($sentences as $sentence) {
            if (preg_match('/(?:except|excluding|not (?:available|open|hiring)|cannot hire).{0,40}(?:Tunisia|Tunisie)/iu', $sentence)) {
                $eligibility = 'restricted'; $evidence['remote'] = Str::limit($sentence, 400);
            }
        }
        [$relocation, $relocationEvidence] = $this->support($sentences, 'relocation|relocalisation|aide au déménagement');
        [$visa, $visaEvidence] = $this->support($sentences, 'visa sponsorship|visa support|sponsorship|parrainage de visa');
        if ($relocationEvidence) { $evidence['relocation'] = $relocationEvidence; }
        if ($visaEvidence) { $evidence['visa_sponsorship'] = $visaEvidence; }

        $salary = $input['salary'] ?? [];
        $value = is_array($salary['value'] ?? null) ? $salary['value'] : ['value' => $salary['value'] ?? null];
        $currency = strtoupper((string) ($salary['currency'] ?? ''));
        $currency = preg_match('/^[A-Z]{3}$/', $currency) ? $currency : null;
        $minimum = $this->amount($value['minValue'] ?? $value['value'] ?? null);
        $maximum = $this->amount($value['maxValue'] ?? $value['value'] ?? null);
        if ($minimum !== null && $maximum !== null && $minimum > $maximum) { $minimum = $maximum = null; }
        $period = match (strtoupper((string) ($value['unitText'] ?? ''))) {
            'HOUR', 'HOURLY' => 'hour', 'DAY', 'DAILY' => 'day', 'WEEK', 'WEEKLY' => 'week',
            'MONTH', 'MONTHLY' => 'month', 'YEAR', 'YEARLY', 'ANNUAL', 'ANNUALLY' => 'year', default => null,
        };
        $posted = $this->date($input['posted_at'] ?? null);
        if ($posted?->isFuture()) { $posted = null; }
        $deadline = $input['expires_at'] ?? null;
        $deadlineDate = $this->date($deadline);
        $dateOnly = $deadlineDate && is_string($deadline) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $deadline);
        return [
            'title' => $title, 'description' => $description, 'employer' => $employer,
            'website' => $this->publicUrl($website = $this->encodeUrl($input['website'] ?? '')) ? $website : null,
            'kind' => $internship ? 'internship' : 'job', 'program_type' => $program, 'seniority' => $seniority,
            'employment_types' => $types, 'work_type' => $workType ?? 'unknown',
            'country_code' => $country, 'location' => $location, 'locations' => $locations,
            'remote_scope' => $input['remote_scope'] ?? ($workType === 'remote' ? $location : null),
            'remote_eligibility' => $eligibility, 'eligible_countries' => $eligible,
            'excluded_countries' => $eligibility === 'restricted' && isset($evidence['remote']) && preg_match('/except|excluding|cannot hire/iu', $evidence['remote']) ? ['TN'] : [],
            'relocation' => $relocation, 'visa_sponsorship' => $visa, 'eligibility_evidence' => $evidence,
            'language' => is_string($input['language'] ?? null) ? substr($input['language'], 0, 10) : null,
            'posted_at' => $posted?->toIso8601String(), 'expires_at' => $dateOnly ? null : $deadlineDate?->toIso8601String(),
            'closing_at' => $dateOnly ? $deadline : null, 'application_url' => $apply,
            'pay_min' => $minimum, 'pay_max' => $maximum, 'pay_currency' => $currency, 'pay_unit' => $period,
            'compensation_status' => ($minimum ?? $maximum) !== null ? (($minimum ?? $maximum) > 0 ? 'paid' : 'unpaid') : 'unspecified',
            'requisition_id' => is_scalar($input['requisition_id'] ?? null) ? (string) $input['requisition_id'] : null,
        ];
    }

    public function text(string $html): string
    {
        // Some APIs send escaped HTML; decode it once so the tags can be handled, then decode text after stripping.
        $text = preg_match('~&lt;/?[a-z]~i', $html) ? html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8') : $html;
        $text = mb_check_encoding($text, 'UTF-8') ? $text : mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        $text = preg_replace(['~<(script|style)\b[^>]*>.*?</\1>~is', '~<!--.*?-->~s'], '', $text);
        // Tags become marks first: \x01 ends a line, \x02 a paragraph, \x03 starts a list item and \x04 ends one.
        $text = preg_replace(['~<br\b[^>]*>[^\S\n]*\n?~i', '~<li\b[^>]*>~i', '~</li\s*>~i', '~</?(?:p|h[1-6]|blockquote|pre|table)\b[^>]*>~i',
            '~</?(?:div|ul|ol|tr|dl|dt|dd|section|article|header|footer)\b[^>]*>~i', '~</t[dh]>~i'], ["\n", "\x01\x03", "\x04", "\x02", "\x01", ' '], $text);
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace(["\r\n", "\r", "\u{A0}"], ["\n", "\n", ' '], $text);
        // A sentence glued to the next heading, as in "integrations.Key Responsibilities", lost its line break.
        $text = preg_replace('/(?<=\p{Ll}[.!?])(?<!www\.)(?=\p{Lu}\p{Ll})(?!(?:Js|Net|Io)\b)/u', "\n", $text);
        $text = preg_replace(['/\x03[\s\x01\x02\x03]*\x04/u', '/\x03[\s\x01\x02\x03]*/u', '/\x04/', '/[^\S\n]+(?=[\x01\x02\n])|(?<=[\x01\x02\n])[^\S\n]+/u'], ['', "\x03", "\x01", ''], $text);
        $text = preg_replace_callback('/[\x01\x02\n]+/u', fn ($break) => str_contains($break[0], "\x02") || substr_count($break[0], "\n") > 1 ? "\n\n" : "\n", $text);
        // List items stay one per line, and an item with no text is dropped.
        $text = preg_replace(['/^(\x03.*)\n\n(?=\x03)/mu', '/^\x03$/mu', '/[^\S\n]+/u'], ["$1\n", '', ' '], $text);
        $text = preg_replace(['/^ | $/mu', '/\n{3,}/'], ['', "\n\n"], str_replace("\x03", '• ', $text));
        return trim($text);
    }

    // A remote role tied to other countries or regions, such as "Remote US" or "Canada (Remote)", is not open to Tunisia.
    public function limitedToOtherRegions(string $location): bool
    {
        if ($location === '' || preg_match('/\b(worldwide|anywhere|global|emea|africa|mena|tunisia|tunisie)\b/iu', $location)) {
            return false;
        }
        return (bool) (preg_match('/\b(US|USA|UK|EU|AMER|APAC|LATAM)\b|\bU\.S\.?(?![A-Za-z])/u', $location)
            || preg_match('/\b(united states|canada|americas?|north america|latin america|south america|central america|asia|india|japan|australia|united kingdom|ireland|europe|germany|france|spain|portugal|netherlands|belgium|poland|sweden|finland|denmark|norway|italy|switzerland|austria|brazil|mexico|argentina|singapore|philippines)\b/iu', $location));
    }

    private function support(array $sentences, string $terms): array
    {
        $positive = null;
        foreach ($sentences as $sentence) {
            if (! preg_match('/'.$terms.'/iu', $sentence)) { continue; }
            if (preg_match('/(?:\b(?:no|without)\s+(?:\w+\s+){0,2}|pas de\s+)(?:'.$terms.')|(?:not|cannot|unable to)\s+(?:offer|provide|support|sponsor).{0,30}(?:'.$terms.')|(?:'.$terms.').{0,20}(?:not available|not offered|unavailable)/iu', $sentence)) {
                return ['no', Str::limit($sentence, 400)];
            }
            $context = preg_replace('/(?:'.$terms.')/iu', ' ', $sentence);
            if (! preg_match('/\b(ask|discuss|may|might|possible)\b|case.by.case|subject to/iu', $sentence)
                && preg_match('/\b(offer|offers|offered|provide|provides|provided|available|assist|assistance|package|sponsor)\b|prise en charge|proposons/iu', $context)) {
                $positive = Str::limit($sentence, 400);
            }
        }
        return $positive ? ['yes', $positive] : ['unknown', null];
    }

    // Maps board wording such as "contractor", "Full-time: Remote" or "CDD" to the values the app filters on.
    private function employmentTypes($values): array
    {
        $types = [];
        foreach ((array) $values as $value) {
            foreach (preg_split('/\s*[,;|]\s*/', is_scalar($value) ? (string) $value : '') as $part) {
                $part = preg_replace('/[^a-z0-9]+/', ' ', strtolower(Str::ascii($part)));
                foreach (self::EMPLOYMENT_TYPES as [$type, $pattern]) {
                    if (preg_match('/\b(?:'.$pattern.')\b/', $part)) { $types[] = $type; break; }
                }
            }
        }
        return array_values(array_unique($types));
    }

    // Checked in order, so "Full Time Contractor" is a contractor and "Permanent contract" is full time.
    private const EMPLOYMENT_TYPES = [
        ['INTERN', 'intern|internship|stage|stagiaire|pfe|apprenti\w*|alternance|trainee|working student|co op'],
        ['CONTRACTOR', 'contractor|freelance\w*|consultant|independant|independent|prestataire'],
        ['TEMPORARY', 'temporary|temp|fixed ?term|short term|seasonal|saisonnier|cdd|duree determinee|interim|per diem'],
        ['PART_TIME', 'part ?time|temps partiel|mi temps'],
        ['FULL_TIME', 'full ?time|permanent|regular|temps plein|plein temps|cdi|duree indeterminee'],
        ['CONTRACTOR', 'contract'],
        ['VOLUNTEER', 'volunteer|benevol\w*'],
        ['OTHER', 'other|autre'],
    ];

    public function country($value): ?string
    {
        if (! is_string($value)) { return null; }
        $codes = explode(' ', 'AD AE AF AG AI AL AM AO AQ AR AS AT AU AW AX AZ BA BB BD BE BF BG BH BI BJ BL BM BN BO BQ BR BS BT BV BW BY BZ CA CC CD CF CG CH CI CK CL CM CN CO CR CU CV CW CX CY CZ DE DJ DK DM DO DZ EC EE EG EH ER ES ET FI FJ FK FM FO FR GA GB GD GE GF GG GH GI GL GM GN GP GQ GR GS GT GU GW GY HK HM HN HR HT HU ID IE IL IM IN IO IQ IR IS IT JE JM JO JP KE KG KH KI KM KN KP KR KW KY KZ LA LB LC LI LK LR LS LT LU LV LY MA MC MD ME MF MG MH MK ML MM MN MO MP MQ MR MS MT MU MV MW MX MY MZ NA NC NE NF NG NI NL NO NP NR NU NZ OM PA PE PF PG PH PK PL PM PN PR PS PT PW PY QA RE RO RS RU RW SA SB SC SD SE SG SH SI SJ SK SL SM SN SO SR SS ST SV SX SY SZ TC TD TF TG TH TJ TK TL TM TN TO TR TT TV TW TZ UA UG UM US UY UZ VA VC VE VG VI VN VU WF WS YE YT ZA ZM ZW');
        $upper = strtoupper(trim($value));
        if (in_array($upper, $codes, true)) { return $upper; }
        $aliases = ['tunisia' => 'TN', 'tunisie' => 'TN', 'tunis' => 'TN', 'تونس' => 'TN', 'france' => 'FR', 'germany' => 'DE', 'allemagne' => 'DE', 'united states' => 'US', 'usa' => 'US', 'united kingdom' => 'GB', 'uk' => 'GB', 'canada' => 'CA', 'morocco' => 'MA', 'maroc' => 'MA', 'algeria' => 'DZ', 'algérie' => 'DZ', 'spain' => 'ES', 'italy' => 'IT', 'netherlands' => 'NL', 'belgium' => 'BE', 'switzerland' => 'CH', 'united arab emirates' => 'AE', 'saudi arabia' => 'SA'];
        return $aliases[mb_strtolower(trim($value))] ?? null;
    }

    public function countryName(?string $code, string $language = 'en'): ?string
    {
        if (! $code) { return null; }
        $name = class_exists(\Locale::class) ? \Locale::getDisplayRegion('-'.$code, $language) : '';
        return $name && $name !== $code && ! str_contains($name, 'Unknown') ? $name : $code;
    }

    // Known skills named in the title or description, in the order of importer.skills.
    public function skills(string $title, string $description): array
    {
        $found = [];
        foreach (config('importer.skills', []) as $name => $pattern) {
            if (is_int($name)) { [$name, $pattern] = [$pattern, preg_quote($pattern, '~')]; }
            if (preg_match('~(?<![\pL\pN])(?:'.$pattern.')(?![\pL\pN])~iu', $title."\n".$description)) { $found[] = $name; }
        }
        return array_slice($found, 0, 20);
    }

    public const CLEANED = ['description', 'location', 'locations', 'work_type', 'employment_types'];
    public const REMOTE_FACTS = ['remote_scope', 'remote_eligibility', 'eligible_countries', 'excluded_countries', 'eligibility_evidence'];

    // Runs the description, location, work arrangement and contract type rules again on saved data,
    // from the source input when it is still at hand.
    public function clean(array $data, ?array $input = null): array
    {
        try {
            $fresh = $input ? $this->normalize($input) : null;
        } catch (RuntimeException) {
            $fresh = null;
        }
        $keys = self::CLEANED;
        // A new work arrangement brings its own remote facts; the AI only answers those for roles that were already remote.
        if ($fresh && $fresh['work_type'] !== ($data['work_type'] ?? null)) {
            $keys = [...$keys, ...self::REMOTE_FACTS];
            $fresh['eligibility_evidence'] = array_diff_key((array) ($data['eligibility_evidence'] ?? []), ['remote' => 1])
                + array_intersect_key($fresh['eligibility_evidence'], ['remote' => 1]);
        }
        if (! $fresh) {
            $saved = is_array($data['locations'] ?? null) ? $data['locations'] : [];
            $locations = $this->places(array_map(fn ($place) => is_array($place) ? (array) ($place['address'] ?? [])
                + array_filter(['addressLocality' => $place['city'] ?? null, 'addressCountry' => $place['country_code'] ?? null]) : null, $saved));
            $location = $data['location'] ?? null;
            $location = $saved && $location === ($saved[0]['location'] ?? null) ? ($locations[0]['location'] ?? null) : $this->place($location, $data['country_code'] ?? null);
            $description = $this->text((string) ($data['description'] ?? ''));
            $workType = in_array($data['work_type'] ?? null, ['remote', 'hybrid', 'onsite'], true) ? $data['work_type']
                : ($this->arrangement((string) ($data['title'] ?? ''), false) ?? $this->arrangement((string) $location, false) ?? $this->arrangement($description, true) ?? 'unknown');
            $fresh = ['description' => $description, 'location' => $location, 'locations' => $locations, 'work_type' => $workType,
                'employment_types' => $this->employmentTypes($data['employment_types'] ?? [])];
        }
        return array_replace($data, array_intersect_key($fresh, array_flip($keys)));
    }

    // Turns JSON-LD places into "City, Region, Country" labels; the address keeps the cleaned parts too.
    private function places($values): array
    {
        $places = [];
        foreach (is_array($values) ? $values : [] as $value) {
            if (! is_array($value)) { continue; }
            $address = $value['address'] ?? $value;
            if (is_string($address)) { $address = ['addressLocality' => $address]; }
            if (! is_array($address)) { continue; }
            $countryValue = $address['addressCountry'] ?? $address['country'] ?? null;
            $country = $this->country(is_array($countryValue) ? ($countryValue['name'] ?? '') : $countryValue);
            [$city, $region] = $this->cityAndRegion($address['addressLocality'] ?? $address['city'] ?? null, $address['addressRegion'] ?? $address['region'] ?? null, $country);
            $places[] = ['country_code' => $country, 'city' => $city, 'location' => implode(', ', array_filter([$city, $region, $this->countryName($country)])),
                'address' => array_filter(['streetAddress' => $address['streetAddress'] ?? null, 'addressLocality' => $city,
                    'addressRegion' => $region, 'postalCode' => $address['postalCode'] ?? null, 'addressCountry' => $country])];
        }
        return $places;
    }

    // Drops repeated parts, the country name and a region that the city already says, or that is another governorate.
    private function cityAndRegion($city, $region, ?string $country): array
    {
        $skip = $country ? [strtolower($country), $this->placeKey($this->countryName($country)), $this->placeKey($this->countryName($country, 'fr'))] : [];
        $parts = [];
        foreach (preg_split('/\s+[-–—|]\s+|\s*[,;]\s*/u', is_string($city) ? $city : '') as $part) {
            $key = $this->placeKey($part = $this->placeName($part));
            if ($key !== '' && ! in_array($key, $skip, true)) { $parts[$key] ??= $part; }
        }
        $region = $this->placeName(is_string($region) ? $region : '');
        $regionKey = in_array($this->placeKey($region), $skip, true) ? '' : $this->placeKey($region);
        $first = (string) array_key_first($parts);
        if ($regionKey !== '' && isset($parts[$regionKey]) && count($parts) > 1) {
            unset($parts[$regionKey]);
        } elseif ($regionKey !== '' && $first !== '' && ($first === $regionKey || str_starts_with($first, $regionKey.' ')
            || (in_array($country, [null, 'TN'], true) && in_array($first, self::GOVERNORATES, true) && in_array($regionKey, self::GOVERNORATES, true)))) {
            $regionKey = '';
        }
        return [implode(', ', $parts) ?: null, $regionKey !== '' ? $region : null];
    }

    private const GOVERNORATES = ['tunis', 'ariana', 'ben arous', 'manouba', 'nabeul', 'zaghouan', 'bizerte', 'beja', 'jendouba', 'kef', 'siliana',
        'sousse', 'monastir', 'mahdia', 'sfax', 'kairouan', 'kasserine', 'sidi bouzid', 'gabes', 'medenine', 'tataouine', 'gafsa', 'tozeur', 'kebili'];

    // Free-text locations lose repeated parts and governorate prefixes; a trailing code of the known country is spelled out.
    private function place($text, ?string $country): ?string
    {
        $parts = [];
        foreach (explode(',', is_string($text) ? $text : '') as $part) {
            $key = $this->placeKey($part = $this->placeName($part));
            if ($key !== '') { $parts[$key] ??= $part; }
        }
        if (count($parts) > 1 && $country && strtoupper((string) end($parts)) === $country) { $parts[array_key_last($parts)] = $this->countryName($country); }
        return implode(', ', $parts) ?: null;
    }

    private function placeName(string $name): string
    {
        $name = Str::squish(preg_replace('/^[\s,;|–—-]+|[\s,;|–—-]+$/u', '', $name));
        $name = preg_replace('/^(?:gouvernorat|governorate|wilaya|province|r[ée]gion|d[ée]l[ée]gation|delegation)\s+(?:of\s+|de\s+la\s+|de\s+l[\'’]\s*|du\s+|des\s+|de\s+|d[\'’]\s*)?|\s+(?:governorate|gouvernorat|province)$/iu', '', $name);
        return $name === mb_strtolower($name) ? Str::ucfirst($name) : $name;
    }

    private function placeKey(?string $name): string
    {
        return preg_replace('/^(?:la|le|el|l) /', '', Str::squish(preg_replace('/[^a-z0-9]+/', ' ', strtolower(Str::ascii((string) $name)))));
    }

    private function arrangement(string $text, bool $description): ?string
    {
        foreach ($description ? self::DESCRIPTION_CUES : self::SHORT_CUES as [$type, $pattern]) {
            if (preg_match('~'.$pattern.'~iu', $text)) { return $type; }
        }
        return null;
    }

    // Titles and locations: one word is enough. Checked in order, so "télétravail partiel" is hybrid, not remote.
    private const SHORT_CUES = [
        ['onsite', '(?:pas\s+de|sans)\s+t[ée]l[ée]travail|\bno\s+remote\b|\bnot\s+remote\b'],
        ['hybrid', '(?:^|[(\[|,:/–—-])\s*hybride?\s*(?:$|[)\]|,:/–—-])|t[ée]l[ée]travail\s+(?:partiel|possible|occasionnel)|\bhybrid\s+(?:remote|work)'],
        ['remote', '\bremote\b(?!\s+sensing)|t[ée]l[ée]travail|\bà\s+distance\b|\bhome[\s-]based\b|\banywhere\b|\bwork\s+from\s+home\b'],
        ['onsite', '\bon[\s-]?site\b|\bsur\s+site\b|\bpr[ée]sentiel\b|\bin[\s-]office\b'],
    ];

    // Descriptions: only phrases about the job itself, so "applications hybrides" or "maintenance à distance" say nothing.
    private const DESCRIPTION_CUES = [
        ['onsite', '(?:pas\s+de|sans|aucun)\s+(?:possibilit[ée]\s+de\s+)?t[ée]l[ée]travail|t[ée]l[ée]travail\s+(?:non|pas)\s+(?:possible|autoris[ée]|accept[ée])'
            .'|\bno\s+remote\b|\bnot\s+(?:a\s+)?remote\b|\bremote\s+(?:work\s+)?is\s+not\s+(?:possible|available|an\s+option)'],
        ['hybrid', '\b(?:travail|mode|poste|format|mod[eè]le|rythme|work|working|role|position|schedule|model|setup|arrangement)\s+(?:de\s+travail\s+|is\s+|en\s+)?hybride?\b'
            .'|\bhybride?\s+(?:work|working|role|position|schedule|model|setup|arrangement|t[ée]l[ée]travail)\b|\ben\s+(?:mode\s+)?hybride\b|\(\s*hybride?\s*\)'
            .'|(?:t[ée]l[ée]travail|travail\s+à\s+distance|remote\s+work)\s+(?:partiel|occasionnel|ponctuel|possible)|possibilit[ée]\s+de\s+(?:faire\s+du\s+|travail(?:ler)?\s+(?:en\s+)?)?(?:t[ée]l[ée]travail|à\s+distance|remote)'
            .'|\b(?:\d|un|deux|trois|one|two|three)\s*(?:jours?|j)\s+(?:de\s+|en\s+)t[ée]l[ée]travail|\b(?:\d|one|two|three)\s*days?\s+(?:a|per)\s+week\s+(?:in|at|from)\s+(?:the\s+)?(?:office|home)'
            .'|\b(?:\d|one|two|three)\s*days?\s+(?:remote|from\s+home|in[\s-]office|on[\s-]?site)\b|\bpartial(?:ly)?\s+remote\b'],
        ['remote', '100\s*%\s*(?:en\s+)?(?:remote|t[ée]l[ée]travail|à\s+distance)|\b(?:full|fully|entirely|completely|totally)[\s-]+remote\b|\bremote[\s-]+(?:only|position|role|job|opportunity)\b'
            .'|\b(?:position|role|job)\s+is\s+(?:fully\s+|100\s*%\s+)?remote\b|\bwork(?:ing)?\s+(?:fully|entirely|100\s*%)\s+remotely\b|\bwork\s+from\s+anywhere\b'
            .'|t[ée]l[ée]travail\s+(?:complet|total|int[ée]gral|à\s+100)|\b(?:poste|travail|emploi|job|mission|travailler)\s+(?:\S+\s+)?en\s+(?:full\s+)?t[ée]l[ée]travail|\bfull[\s-]+t[ée]l[ée]travail'
            .'|\b(?:poste|travail|emploi|job|mission|travailler)\s+(?:\S+\s+)?à\s+distance\b|\b(?:enti[eè]rement|totalement)\s+à\s+distance\b'],
        ['onsite', '\b(?:poste|travail|emploi|mission|bas[ée]e?|pr[ée]sence|uniquement|exclusivement)\s+(?:\S+\s+)?sur\s+site\b|100\s*%\s+(?:\S+\s+)?sur\s+site\b|\(\s*sur\s+site\s*\)'
            .'|\b(?:poste|travail|emploi|mission|uniquement|exclusivement|enti[eè]rement)\s+(?:\S+\s+)?pr[ée]sentiel\b|100\s*%\s+(?:\S+\s+)?pr[ée]sentiel\b'
            .'|\bpr[ée]sentiel\s+(?:obligatoire|uniquement|exig[ée]|requis)\b|\(\s*pr[ée]sentiel\s*\)|\b(?:role|position|job|work|working|based|fully)\s+(?:is\s+)?(?:fully\s+)?on[\s-]?site\b'
            .'|100\s*%\s+on[\s-]?site\b|\bon[\s-]?site\s+(?:role|position|job|only|work|basis)\b|\(\s*on[\s-]?site\s*\)|\bin[\s-]office\s+(?:role|position|job)\b'],
    ];

    private function date($value): ?Carbon
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) { return null; }
        if (! checkdate((int) substr($value, 5, 2), (int) substr($value, 8, 2), (int) substr($value, 0, 4))) { return null; }
        try { return Carbon::parse($value)->utc(); } catch (\Throwable) { return null; }
    }

    private function amount($value): ?float
    {
        return is_numeric($value) && $value >= 0 && $value <= 999999999 ? round((float) $value, 2) : null;
    }

    // Boards sometimes put raw accented letters in links, e.g. /job/1/chargé-e/, which URL checks reject.
    private function encodeUrl($value): string
    {
        return is_string($value) ? preg_replace_callback('/[^\x21-\x7E]/u', fn ($match) => rawurlencode($match[0]), trim($value)) : '';
    }

    private function publicUrl($value): bool
    {
        if (! is_string($value) || ! filter_var($value, FILTER_VALIDATE_URL)) { return false; }
        $parts = parse_url($value);
        if (! in_array($parts['scheme'] ?? '', ['https', 'http'], true) || isset($parts['user']) || isset($parts['pass'])) { return false; }
        $host = strtolower(rtrim(trim($parts['host'] ?? '', '[]'), '.'));
        if (! str_contains($host, '.') || str_ends_with($host, '.localhost')) { return false; }
        if (filter_var($host, FILTER_VALIDATE_IP)) { return (bool) filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_GLOBAL_RANGE); }
        // Browsers also interpret abbreviated numeric hosts as IP addresses.
        return ! preg_match('/^(?:0x[0-9a-f]+|[0-9]+)(?:\.(?:0x[0-9a-f]+|[0-9]+))*$/i', $host);
    }
}

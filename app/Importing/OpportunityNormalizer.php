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
        $apply = $input['application_url'] ?? '';
        if (! $this->publicUrl($apply)) {
            throw new RuntimeException('A valid public application URL is required.');
        }
        $types = array_values(array_unique(array_filter(array_map($this->employmentType(...), $input['employment_types'] ?? []))));
        $internship = in_array('INTERN', $types, true)
            || preg_match('/\b(intern(?:ship)?s?|stagiaire|stage (?:pfe|de fin|d.été|en |dans )|pfe|apprentice(?:ship)?|alternance|co-op|working student|werkstudent|praktikum)\b/iu', $title);
        $program = preg_match('/\bpfe\b|fin d[’\x27]études/iu', $title) ? 'pfe'
            : (preg_match('/summer|été/iu', $title) ? 'summer' : null);
        $seniority = $internship ? 'entry' : null;
        foreach (['executive' => '\b(chief|c[tefo]o|vp|vice president)\b', 'lead' => '\b(lead|principal|head of)\b',
            'senior' => '\b(senior|sr\.?|confirmé)\b', 'entry' => '\b(junior|entry.level|débutant)\b', 'graduate' => '\b(graduate|jeune diplômé)\b'] as $level => $pattern) {
            if (preg_match('/'.$pattern.'/iu', $title)) { $seniority = $level; break; }
        }
        $locations = [];
        foreach ($input['locations'] ?? [] as $location) {
            if (! is_array($location)) { continue; }
            $address = $location['address'] ?? $location;
            if (! is_array($address)) { continue; }
            $countryValue = $address['addressCountry'] ?? $address['country'] ?? null;
            $country = $this->country(is_array($countryValue) ? ($countryValue['name'] ?? '') : $countryValue);
            $city = $address['addressLocality'] ?? $address['city'] ?? null;
            $label = implode(', ', array_filter([$city, $address['addressRegion'] ?? $address['region'] ?? null, $country]));
            $locations[] = ['country_code' => $country, 'city' => $city, 'location' => $label,
                'address' => array_filter(['streetAddress' => $address['streetAddress'] ?? null, 'addressLocality' => $city,
                    'addressRegion' => $address['addressRegion'] ?? null, 'postalCode' => $address['postalCode'] ?? null,
                    'addressCountry' => $country])];
        }
        $location = $input['location'] ?? ($locations[0]['location'] ?? null);
        $country = $this->country($input['country_code'] ?? null) ?? ($locations[0]['country_code'] ?? null);
        if (! $country && $location) {
            foreach (array_reverse(preg_split('/[,|\/]/', $location)) as $part) {
                // CA, IN and similar tokens can be states, not countries, in free text.
                if (mb_strlen(trim($part)) > 2 && ($country = $this->country(trim($part)))) { break; }
            }
        }
        $workType = strtolower($input['work_type'] ?? '');
        $workType = match ($workType) { 'remote' => 'remote', 'hybrid' => 'hybrid', 'onsite', 'on-site', 'on_site' => 'onsite', default => null };
        if (! $workType && preg_match('/\b(remote|télétravail)\b/iu', (string) $location)) { $workType = 'remote'; }
        // Preserve uncertainty: description boilerplate alone never determines work arrangement.
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
            'website' => $this->publicUrl($input['website'] ?? null) ? $input['website'] : null,
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
        $text = html_entity_decode(html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('~<(script|style)\b[^>]*>.*?</\1>~is', '', $text);
        $text = preg_replace('~<(?:br\s*/?|/p|/div|/li|/h[1-6])\s*>~i', "\n", $text);
        $text = preg_replace('~<li\b[^>]*>~i', '• ', $text);
        return trim(preg_replace('/\n[\t ]*\n(?:[\t ]*\n)+/', "\n\n", strip_tags($text)));
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

    private function employmentType($value): ?string
    {
        $value = strtoupper(preg_replace('/[\s_-]/', '', (string) $value));
        return match ($value) {
            'FULLTIME' => 'FULL_TIME', 'PARTTIME' => 'PART_TIME', 'CONTRACT', 'CONTRACTOR', 'FREELANCE' => 'CONTRACTOR',
            'TEMPORARY', 'TEMP', 'FIXEDTERM' => 'TEMPORARY', 'INTERN', 'INTERNSHIP' => 'INTERN', 'VOLUNTEER' => 'VOLUNTEER',
            'OTHER' => 'OTHER', default => null,
        };
    }

    public function country($value): ?string
    {
        if (! is_string($value)) { return null; }
        $codes = explode(' ', 'AD AE AF AG AI AL AM AO AQ AR AS AT AU AW AX AZ BA BB BD BE BF BG BH BI BJ BL BM BN BO BQ BR BS BT BV BW BY BZ CA CC CD CF CG CH CI CK CL CM CN CO CR CU CV CW CX CY CZ DE DJ DK DM DO DZ EC EE EG EH ER ES ET FI FJ FK FM FO FR GA GB GD GE GF GG GH GI GL GM GN GP GQ GR GS GT GU GW GY HK HM HN HR HT HU ID IE IL IM IN IO IQ IR IS IT JE JM JO JP KE KG KH KI KM KN KP KR KW KY KZ LA LB LC LI LK LR LS LT LU LV LY MA MC MD ME MF MG MH MK ML MM MN MO MP MQ MR MS MT MU MV MW MX MY MZ NA NC NE NF NG NI NL NO NP NR NU NZ OM PA PE PF PG PH PK PL PM PN PR PS PT PW PY QA RE RO RS RU RW SA SB SC SD SE SG SH SI SJ SK SL SM SN SO SR SS ST SV SX SY SZ TC TD TF TG TH TJ TK TL TM TN TO TR TT TV TW TZ UA UG UM US UY UZ VA VC VE VG VI VN VU WF WS YE YT ZA ZM ZW');
        $upper = strtoupper(trim($value));
        if (in_array($upper, $codes, true)) { return $upper; }
        $aliases = ['tunisia' => 'TN', 'tunisie' => 'TN', 'tunis' => 'TN', 'تونس' => 'TN', 'france' => 'FR', 'germany' => 'DE', 'allemagne' => 'DE', 'united states' => 'US', 'usa' => 'US', 'united kingdom' => 'GB', 'uk' => 'GB', 'canada' => 'CA', 'morocco' => 'MA', 'maroc' => 'MA', 'algeria' => 'DZ', 'algérie' => 'DZ', 'spain' => 'ES', 'italy' => 'IT', 'netherlands' => 'NL', 'belgium' => 'BE', 'switzerland' => 'CH', 'united arab emirates' => 'AE', 'saudi arabia' => 'SA'];
        return $aliases[mb_strtolower(trim($value))] ?? null;
    }

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

<?php

namespace App\Leads;

use App\Importing\JobKey;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\Lead;
use App\Models\LeadContact;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

// Brings applications made elsewhere into job leads: the old jobs.json tracker, a Tsenta or LinkedIn CSV export, any
// CSV or JSON with company and role columns, Gmail and the manual form. Every row is merged with the lead for the same
// job (same company and a close title), so a job is never listed twice.
class LeadImport
{
    // Column names each field may have, compared without case, spaces or punctuation.
    private const COLUMNS = [
        'company' => ['company', 'companyname', 'employer', 'organization', 'organisation'],
        'title' => ['role', 'title', 'jobtitle', 'position', 'job', 'jobname'],
        'url' => ['url', 'joburl', 'link', 'joblink', 'applicationurl', 'postingurl'],
        'status' => ['status', 'stage', 'list', 'state'],
        'applied_at' => ['applied', 'appliedat', 'appliedon', 'applicationdate', 'dateapplied', 'date'],
        'notes' => ['notes', 'note', 'comments', 'comment'],
        'posted' => ['posted', 'postedat', 'dateposted', 'postedon'],
        'location' => ['location', 'place', 'city'],
        'id' => ['id', 'ref', 'reference'],
    ];

    public array $stats = ['added' => 0, 'updated' => 0, 'skipped' => 0, 'contacts' => 0];

    public function __construct(private Candidate $candidate) {}

    // Reads a file and returns the counts. $origin is guessed from the file when null.
    public function file(string $path, ?string $name = null, ?string $origin = null): array
    {
        $text = (string) @file_get_contents($path);
        $text = preg_replace('/^\xEF\xBB\xBF/', '', $text);
        if (trim($text) === '') { throw new RuntimeException('The file is empty.'); }
        $json = json_decode($text, true);
        if (is_array($json)) {
            $rows = array_is_list($json) ? $json : ($json['jobs'] ?? $json['applications'] ?? $json['leads'] ?? []);
            $tracker = isset($rows[0]['role'], $rows[0]['company']) && array_key_exists('list', $rows[0]);
            foreach ($rows as $row) { if (is_array($row)) { $this->save($this->row($row, $tracker), $origin ?? ($tracker ? 'tracker' : 'json')); } }
            return $this->stats;
        }
        $lines = array_values(array_filter(preg_split('/\R/', $text), fn ($line) => trim($line) !== ''));
        $delimiter = substr_count($lines[0] ?? '', ';') > substr_count($lines[0] ?? '', ',') ? ';' : ',';
        $rows = array_map(fn ($line) => str_getcsv($line, $delimiter, '"', ''), $this->csvRecords($text));
        $header = array_map(fn ($cell) => preg_replace('/[^a-z]/', '', mb_strtolower((string) $cell)), array_shift($rows) ?? []);
        if (! array_intersect($header, self::COLUMNS['company']) || ! array_intersect($header, self::COLUMNS['title'])) {
            throw new RuntimeException('The file needs company and role columns.');
        }
        // LinkedIn's data export names it Job Applications.csv, with Application Date, Company Name and Job Title.
        $origin ??= in_array('applicationdate', $header, true) && in_array('companyname', $header, true) ? 'linkedin'
            : (str_contains(mb_strtolower((string) $name), 'tsenta') ? 'tsenta' : 'csv');
        foreach ($rows as $cells) {
            $this->save($this->row(array_combine($header, array_slice(array_pad($cells, count($header), ''), 0, count($header))), false), $origin);
        }
        return $this->stats;
    }

    // Saves one job: merged with the lead for the same job when there is one. Returns the lead, or null when the row has no company or role.
    public function save(array $row, string $origin): ?Lead
    {
        $company = Str::limit(trim((string) ($row['company'] ?? '')), 191, '');
        $title = Str::limit(trim((string) ($row['title'] ?? '')), 255, '');
        if ($company === '' || $title === '') { $this->stats['skipped']++; return null; }
        $ref = isset($row['id']) && $row['id'] !== '' ? Str::limit((string) $row['id'], 191, '') : null;
        $companyKey = JobKey::company($company);
        $titleKey = JobKey::title($title);
        $lead = ($ref ? Lead::where('candidate_id', $this->candidate->id)->where('origin', $origin)->where('external_ref', $ref)->first() : null)
            ?? Lead::where('candidate_id', $this->candidate->id)->where('job_key', JobKey::key($company, $title))->first()
            ?? Lead::where('candidate_id', $this->candidate->id)->where('company_key', $companyKey)->get()
                ->first(fn ($lead) => JobKey::similar(JobKey::title($lead->title), $titleKey) >= config('leads.similar_title'));
        $new = ! $lead;
        $lead ??= new Lead(['candidate_id' => $this->candidate->id, 'origin' => $origin, 'external_ref' => $ref, 'company_name' => $company, 'title' => $title,
            'status' => $row['status'] ?? ($row['applied_at'] ?? null ? 'applied' : 'to_apply'),
            'company_id' => Company::where('name_key', $companyKey)->whereNull('claimed_by_company_id')->value('id')]);
        $url = $this->url($row['url'] ?? null);
        if ($url) { $lead->url ??= $url; $lead->urls = array_values(array_unique([...($lead->urls ?? []), $url])); }
        $lead->posted_at ??= $this->date($row['posted'] ?? null);
        $applied = $this->date($row['applied_at'] ?? null);
        if (! $new && ! empty($row['status'])) { $lead->advance($row['status'], $applied); }
        if ($applied && in_array($lead->status, ['applied', 'interviewing', 'offer', 'rejected'], true)) { $lead->applied_at ??= $applied; }
        if (in_array($lead->status, ['applied', 'interviewing', 'offer'], true)) { $lead->applied_at ??= today(); }
        $note = trim((string) ($row['notes'] ?? ''));
        if ($note !== '' && ! str_contains((string) $lead->notes, $note)) { $lead->notes = trim($lead->notes."\n\n".$note); }
        if (! $lead->eligibility && in_array($row['eligibility'] ?? null, ['remote', 'visa', 'unclear', 'excluded'], true)) {
            $lead->eligibility = $row['eligibility'];
            $lead->eligibility_quote ??= $row['eligibility_quote'] ?? null;
        }
        $lead->extra = array_filter([...($lead->extra ?? []), ...array_filter((array) ($row['extra'] ?? []), fn ($value) => $value !== null && $value !== '')]);
        $lead->save();
        $this->stats[$new ? 'added' : 'updated']++;
        if (! empty($row['contact']['name'])) { $this->contact($lead, $row['contact']); }
        return $lead;
    }

    // Turns a status word from any tracker into a lead status.
    public static function status(?string $value): ?string
    {
        $value = mb_strtolower(trim((string) $value));
        return match (true) {
            $value === '' => null,
            in_array($value, Lead::STATUSES, true) => $value,
            str_contains($value, 'offer') => 'offer',
            (bool) preg_match('/interview|screen|technical|final round|on.?site/', $value) => 'interviewing',
            (bool) preg_match('/reject|declin|not selected|no answer|ghost|closed|refus/', $value) => 'rejected',
            (bool) preg_match('/skip|not interested|pass|ignor|archiv/', $value) => 'skipped',
            (bool) preg_match('/to apply|todo|to do|saved|wish|later|shortlist|backlog|new|found/', $value) => 'to_apply',
            (bool) preg_match('/applied|submitted|sent|done|waiting|pending|postul/', $value) => 'applied',
            (bool) preg_match('/opened|viewed|seen/', $value) => 'opened',
            default => null,
        };
    }

    // A row in our shape, from a jobs.json entry or a CSV or JSON row.
    private function row(array $row, bool $tracker): array
    {
        if ($tracker) {
            return [
                'id' => $row['id'] ?? null, 'company' => $row['company'] ?? null, 'title' => $row['role'] ?? null, 'url' => $row['url'] ?? null,
                'status' => self::status($row['list'] ?? null), 'posted' => $row['posted'] ?? null, 'notes' => $row['note'] ?? null,
                'eligibility' => $row['elig'] ?? null, 'eligibility_quote' => $row['eligLabel'] ?? null,
                'extra' => ['location' => $row['location'] ?? null, 'pay' => $row['pay'] ?? null, 'kind' => $row['kind'] ?? null, 'list' => $row['list'] ?? null, 'found_on' => $row['foundOn'] ?? null],
                'contact' => ['name' => $row['leadName'] ?? null, 'title' => $row['leadTitle'] ?? null, 'profile_url' => $row['leadUrl'] ?? null,
                    'note' => $row['leadNote'] ?? null, 'mutual' => isset($row['mutual']) && ! is_bool($row['mutual']) ? (string) $row['mutual'] : (($row['mutual'] ?? false) ? 'Yes' : null)],
            ];
        }
        $flat = [];
        foreach ($row as $key => $value) { $flat[preg_replace('/[^a-z]/', '', mb_strtolower((string) $key))] = is_scalar($value) ? (string) $value : null; }
        $get = fn (string $field) => collect(self::COLUMNS[$field])->map(fn ($name) => $flat[$name] ?? null)->first(fn ($value) => $value !== null && trim($value) !== '');
        // A row with an application date and no status is an application.
        return ['id' => $get('id'), 'company' => $get('company'), 'title' => $get('title'), 'url' => $get('url'), 'status' => self::status($get('status')) ?? ($get('applied_at') ? 'applied' : null),
            'applied_at' => $get('applied_at'), 'notes' => $get('notes'), 'posted' => $get('posted'), 'extra' => ['location' => $get('location')]];
    }

    private function contact(Lead $lead, array $contact): void
    {
        $name = Str::limit(trim((string) $contact['name']), 191, '');
        $saved = LeadContact::where('candidate_id', $this->candidate->id)->where('company_key', $lead->company_key)->where('name', $name)->first()
            ?? new LeadContact(['candidate_id' => $this->candidate->id, 'name' => $name, 'source' => 'import']);
        $saved->fill(array_filter([
            'company_name' => $lead->company_name, 'company_id' => $lead->company_id,
            'title' => $saved->title ?: Str::limit(trim((string) ($contact['title'] ?? '')), 191, ''),
            'profile_url' => $saved->profile_url ?: $this->url($contact['profile_url'] ?? null),
            'note' => $saved->note ?: trim((string) ($contact['note'] ?? '')),
            'mutual' => $saved->mutual ?: Str::limit(trim((string) ($contact['mutual'] ?? '')), 191, ''),
        ], fn ($value) => $value !== null && $value !== ''));
        if ($saved->isDirty()) { $saved->save(); $this->stats['contacts']++; }
    }

    private function url(?string $url): ?string
    {
        $url = trim((string) $url);
        return preg_match('~^https?://[^\s]+$~i', $url) && strlen($url) <= 2000 ? $url : null;
    }

    private function date($value): ?Carbon
    {
        if (! $value) { return null; }
        try {
            return is_numeric($value) ? Carbon::createFromTimestamp($value > 1e11 ? $value / 1000 : $value) : Carbon::parse((string) $value);
        } catch (Throwable) {
            return null;
        }
    }

    // CSV records, keeping line breaks inside quoted cells.
    private function csvRecords(string $text): array
    {
        $records = [];
        $current = '';
        foreach (preg_split('/\R/', $text) as $line) {
            $current = $current === '' ? $line : $current."\n".$line;
            if (substr_count($current, '"') % 2 === 0) {
                if (trim($current) !== '') { $records[] = $current; }
                $current = '';
            }
        }
        return $records;
    }
}

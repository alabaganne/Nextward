<?php

namespace App\Leads;

use App\Importing\JobKey;
use App\Models\Application;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\Lead;
use App\Models\LeadSkip;
use App\Models\Opportunity;
use App\Models\OpportunitySource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

// The daily run: new listings and job board finds become the day's leads, never a job seen before or one already
// applied to. Every job left out is logged in lead_skips with the reason.
class LeadFinder
{
    private const FIELDS = ['work_type', 'location', 'country_code', 'remote_scope', 'remote_eligibility', 'applicant_scope', 'eligible_countries',
        'excluded_countries', 'eligibility_evidence', 'visa_sponsorship', 'relocation', 'description'];

    public function __construct(private Scorer $scorer) {}

    public function run(Candidate $candidate, ?Carbon $date = null): array
    {
        $date ??= today(config('importer.timezone', 'Africa/Tunis'));
        $since = $date->copy()->endOfDay()->min(now())->subDays(config('leads.lookback_days'));
        // Jobs applied to elsewhere (imports, Gmail, the manual form) are not marked seen, so a listing for them is logged as applied once.
        $seen = array_flip([
            ...Lead::where('candidate_id', $candidate->id)->where(fn ($query) => $query->whereNotIn('status', ['applied', 'interviewing', 'offer', 'rejected'])
                ->orWhereNotNull('opportunity_id')->orWhereNotNull('opportunity_source_id'))->pluck('job_key')->all(),
            ...LeadSkip::where('candidate_id', $candidate->id)->where('reason', '!=', 'cap')->pluck('job_key')->all(),
        ]);
        [$history, $applied] = $this->history($candidate);
        $leads = Lead::where('candidate_id', $candidate->id)->where('created_at', '>=', now()->subDays(config('leads.duplicate_days')))
            ->get(['id', 'company_key', 'title'])->map(fn ($lead) => ['id' => $lead->id, 'company' => $lead->company_key, 'title' => JobKey::title($lead->title), 'label' => $lead->title])->all();

        $kept = [];
        $stats = ['added' => 0, 'skipped' => 0];
        foreach ($this->jobs($since) as $job) {
            if (isset($seen[$job['key']])) { continue; }
            $seen[$job['key']] = true;
            [$reason, $detail] = $this->check($job, $history, $applied, $leads);
            if ($reason) {
                $this->skip($candidate, $date, $job, $reason, $detail);
                $stats['skipped']++;
                continue;
            }
            $kept[] = $job;
        }

        usort($kept, fn ($a, $b) => [$b['score'], $b['posted']?->timestamp ?? 0] <=> [$a['score'], $a['posted']?->timestamp ?? 0]);
        $room = max(0, config('leads.daily_cap') - Lead::where('candidate_id', $candidate->id)->whereDate('digest_date', $date)->count());
        foreach ($kept as $index => $job) {
            if ($index >= $room) {
                $this->skip($candidate, $date, $job, 'cap', 'Score '.$job['score'].'; more than '.config('leads.daily_cap').' good roles today.');
                $stats['skipped']++;
                continue;
            }
            Lead::create(['candidate_id' => $candidate->id, 'origin' => 'digest', 'digest_date' => $date, 'status' => 'to_apply',
                ...collect($job)->only(['opportunity_id', 'opportunity_source_id', 'company_id', 'title', 'url', 'urls', 'eligibility', 'eligibility_quote', 'score', 'reasons', 'date_source', 'reposted_at'])->all(),
                'company_name' => $job['company'], 'posted_at' => $job['posted'], 'extra' => array_filter(['location' => $job['location'], 'pay' => $job['pay']])]);
            $stats['added']++;
        }
        return $stats;
    }

    // Why a job is left out, as [reason, detail], or [null, null] when it is kept (with its score and eligibility set).
    private function check(array &$job, array $history, array $applied, array $leads): array
    {
        $title = JobKey::title($job['title']);
        $company = JobKey::company($job['company']);
        if ($job['reposted_at'] || ($job['posted'] && $job['posted']->lt(now()->subDays(config('leads.repost_days'))))) {
            return ['repost', 'First posted '.($job['posted']?->format('j M Y') ?? 'earlier').($job['reposted_at'] ? ', posted again '.$job['reposted_at']->format('j M Y') : '').'.'];
        }
        if (preg_match(config('leads.skip_titles'), mb_strtolower(Str::ascii($job['title'])), $level) || in_array($job['seniority'], ['entry', 'graduate', 'executive'], true)) {
            return ['seniority', 'Level: '.($level[0] ?? $job['seniority']).'.'];
        }
        if ($job['min_years'] && $job['min_years'] >= config('leads.max_years')) { return ['years', 'Asks for '.$job['min_years'].'+ years of experience.']; }
        ['class' => $class, 'quote' => $quote] = Eligibility::classify($job['data']);
        if ($class === 'excluded') { return ['ineligible', $quote]; }
        if ($job['opportunity_id'] && isset($applied[$job['opportunity_id']])) { return ['applied', 'You applied on '.$applied[$job['opportunity_id']].'.']; }
        foreach ($history as $past) {
            if ($past['company'] === $company && JobKey::similar($past['title'], $title) >= config('leads.similar_title')) {
                return ['applied', 'You applied to '.$past['label'].' on '.$past['date']->format('j M Y').'.'];
            }
        }
        foreach ($history as $past) {
            if ($past['company'] === $company && config('leads.cooldown') === 'all' && $past['date']->gte(now()->subDays(config('leads.cooldown_days')))) {
                return ['cooldown', 'You applied to '.$past['label'].' at '.$job['company'].' on '.$past['date']->format('j M Y').'.'];
            }
        }
        foreach ($leads as $lead) {
            if ($lead['company'] === $company && JobKey::similar($lead['title'], $title) >= config('leads.similar_title')) {
                return ['duplicate', 'Same role as lead #'.$lead['id'].': '.$lead['label'].'.'];
            }
        }
        [$score, $reasons, $fit] = $this->scorer->score($job['title'], str_ireplace($job['company'], '', (string) ($job['data']['description'] ?? '')), $class, $job['posted']);
        $job += ['score' => $score, 'reasons' => $reasons, 'eligibility' => $class, 'eligibility_quote' => $quote];
        if ($fit < config('leads.min_score')) { return ['low_score', 'Not close to the roles you look for (score '.$score.').']; }
        return [null, null];
    }

    // Listings first seen lately, then job board finds that are not on Nextward yet.
    private function jobs(Carbon $since): \Generator
    {
        $roles = Opportunity::available()->where('kind', 'job')->whereRaw('COALESCE(first_seen_at, created_at) >= ?', [$since])
            ->with('company.user', 'sources')->orderByDesc('id')->get();
        foreach ($roles as $role) { yield $this->fromRole($role); }

        $finds = OpportunitySource::where('state', 'lead')->whereNull('opportunity_id')->where('last_verified_at', '>=', $since)->orderByDesc('id')->get();
        foreach ($finds as $item) {
            $data = (array) $item->normalized_data;
            if (empty($data['title']) || empty($data['employer'])) { continue; }
            // The same job on an employer board already imported counts as that listing, with its date.
            $role = $item->url_key ? OpportunitySource::where('url_key', $item->url_key)->whereNotNull('opportunity_id')->where('id', '!=', $item->id)->first()?->opportunity : null;
            if ($role) { yield $this->fromRole($role); continue; }
            $url = $data['ats_url'] ?? $data['application_url'] ?? $item->source_url;
            yield [
                'key' => JobKey::key($data['employer'], $data['title'], $data['location'] ?? null), 'company' => $data['employer'],
                'company_id' => Company::where('name_key', JobKey::company($data['employer']))->whereNull('claimed_by_company_id')->value('id'),
                'title' => $data['title'], 'url' => $url, 'urls' => array_values(array_unique(array_filter([$url, $item->source_url]))),
                'opportunity_id' => null, 'opportunity_source_id' => $item->id, 'data' => array_intersect_key($data, array_flip(self::FIELDS)),
                'posted' => ! empty($data['posted_at']) ? Carbon::parse($data['posted_at']) : null, 'date_source' => 'board', 'reposted_at' => null,
                'min_years' => $data['min_years'] ?? null, 'seniority' => $data['seniority'] ?? null, 'location' => $data['location'] ?? null, 'pay' => null,
            ];
        }
    }

    private function fromRole(Opportunity $role): array
    {
        $company = $role->company?->display_name ?? 'Unknown company';
        $pay = $role->pay_min ? trim(number_format($role->pay_min).($role->pay_max ? '-'.number_format($role->pay_max) : '').' '.$role->pay_currency.' '.$role->pay_unit) : null;
        return [
            'key' => $role->job_key ?: JobKey::key($company, $role->title, $role->location), 'company' => $company, 'company_id' => $role->company_id,
            'title' => $role->title, 'url' => $role->application_url,
            'urls' => $role->sources->flatMap(fn ($source) => [$source->application_url, $source->source_url])->prepend($role->application_url)->filter()->unique()->values()->all(),
            'opportunity_id' => $role->id, 'opportunity_source_id' => null, 'data' => $role->only(self::FIELDS),
            'posted' => $role->first_posted_at ?? $role->posted_at, 'date_source' => $role->date_source, 'reposted_at' => $role->reposted_at,
            'min_years' => $role->min_years, 'seniority' => $role->seniority, 'location' => $role->location, 'pay' => $pay,
        ];
    }

    // Where the owner applied (leads marked applied and applications on Nextward), and the listings applied to.
    private function history(Candidate $candidate): array
    {
        $history = Lead::where('candidate_id', $candidate->id)->whereNotNull('applied_at')->whereIn('status', ['applied', 'interviewing', 'offer', 'rejected'])
            ->get(['company_key', 'title', 'applied_at'])
            ->map(fn ($lead) => ['company' => $lead->company_key, 'title' => JobKey::title($lead->title), 'label' => $lead->title, 'date' => $lead->applied_at])->all();
        $applied = [];
        Application::where('candidate_id', $candidate->id)->with('opportunity.company.user')->get()->each(function ($application) use (&$history, &$applied) {
            if (! $role = $application->opportunity) { return; }
            $date = $application->applied_at ?? $application->created_at;
            $applied[$role->id] = $date->format('j M Y');
            $history[] = ['company' => JobKey::company((string) $role->company?->display_name), 'title' => JobKey::title($role->title), 'label' => $role->title, 'date' => Carbon::parse($date)];
        });
        return [$history, $applied];
    }

    private function skip(Candidate $candidate, Carbon $date, array $job, string $reason, ?string $detail): void
    {
        LeadSkip::create(['candidate_id' => $candidate->id, 'run_date' => $date, 'opportunity_id' => $job['opportunity_id'], 'opportunity_source_id' => $job['opportunity_source_id'],
            'job_key' => $job['key'], 'company_name' => Str::limit($job['company'], 191, ''), 'title' => Str::limit($job['title'], 255, ''), 'url' => $job['url'],
            'reason' => $reason, 'detail' => $detail ? Str::limit($detail, 500) : null]);
    }
}

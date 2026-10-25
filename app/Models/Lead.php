<?php

namespace App\Models;

use App\Importing\JobKey;
use Illuminate\Database\Eloquent\Model;

// A job in the owner's personal job leads: found by the daily run, imported from a tracker or added by hand.
class Lead extends Model
{
    public const STATUSES = ['to_apply', 'opened', 'applied', 'interviewing', 'offer', 'rejected', 'skipped'];
    // A status only moves forward on its own; rejected is the end, and skipped only gives way to applying.
    private const ORDER = ['to_apply' => 0, 'opened' => 1, 'skipped' => 1, 'applied' => 2, 'interviewing' => 3, 'offer' => 4, 'rejected' => 5];

    protected $guarded = ['id'];

    protected $casts = [
        'urls' => 'array', 'reasons' => 'array', 'extra' => 'array', 'posted_at' => 'datetime', 'reposted_at' => 'datetime',
        'digest_date' => 'date', 'opened_at' => 'datetime', 'applied_at' => 'date',
    ];

    protected static function booted(): void
    {
        static::saving(function (Lead $lead) {
            if ($lead->isDirty('company_name') || ! $lead->company_key) { $lead->company_key = JobKey::company($lead->company_name); }
            if ($lead->isDirty('company_name', 'title') || ! $lead->job_key) { $lead->job_key = JobKey::key($lead->company_name, $lead->title); }
            if ($lead->url) { $lead->urls = array_values(array_unique([...($lead->urls ?? []), $lead->url])); }
        });
    }

    public function candidate() { return $this->belongsTo(Candidate::class); }
    public function opportunity() { return $this->belongsTo(Opportunity::class); }
    public function company() { return $this->belongsTo(Company::class); }
    public function application() { return $this->belongsTo(Application::class); }

    public function contacts()
    {
        return $this->hasMany(LeadContact::class, 'company_key', 'company_key')->whereColumn('lead_contacts.candidate_id', 'leads.candidate_id');
    }

    // Moves the status forward, never back; applying keeps the first applied date.
    public function advance(string $status, $date = null): self
    {
        if ((self::ORDER[$status] ?? -1) > (self::ORDER[$this->status] ?? 0)) { $this->status = $status; }
        if ($status === 'opened') { $this->opened_at ??= now(); }
        if (in_array($status, ['applied', 'interviewing', 'offer'], true)) { $this->applied_at ??= $date ?? today(); }
        return $this;
    }

    public static function enabledFor(?User $user): bool
    {
        return $user && $user->isAdmin() && $user->isCandidate();
    }

    // Marks the matching lead applied when the owner applies or tracks an application on Nextward, or adds one.
    public static function fromApplication(Application $application): void
    {
        $application->loadMissing('opportunity.company', 'candidate.user');
        if (! self::enabledFor($application->candidate?->user) || ! $opportunity = $application->opportunity) { return; }
        $company = (string) $opportunity->company?->display_name;
        $lead = self::where('candidate_id', $application->candidate_id)
            ->where(fn ($query) => $query->where('opportunity_id', $opportunity->id)->orWhere('job_key', JobKey::key($company, $opportunity->title)))->first()
            ?? new self(['candidate_id' => $application->candidate_id, 'origin' => 'app', 'company_name' => $company ?: 'Unknown company',
                'title' => $opportunity->title, 'url' => $opportunity->application_url, 'posted_at' => $opportunity->first_posted_at ?? $opportunity->posted_at]);
        $lead->fill(['opportunity_id' => $opportunity->id, 'company_id' => $opportunity->company_id, 'application_id' => $application->id])
            ->advance('applied', $application->applied_at)->save();
    }
}

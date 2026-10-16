<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Internship extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'description', 'company_id', 'field_id', 'city_id', 'attachments',
        'closing_at', 'work_type', 'duration_weeks', 'term', 'pay_amount', 'pay_unit',
        'status', 'kind', 'origin', 'application_mode', 'application_url', 'slug',
        'publication_status', 'posted_at', 'first_seen_at', 'last_verified_at',
        'content_updated_at', 'expires_at', 'country_code', 'location', 'locations',
        'employment_types', 'seniority', 'program_type', 'start_date', 'remote_scope',
        'remote_eligibility', 'relocation', 'visa_sponsorship', 'eligible_countries',
        'excluded_countries', 'eligibility_evidence', 'language', 'pay_min', 'pay_max',
        'pay_currency', 'compensation_status', 'moderation_overrides', 'merged_into_id',
    ];

    protected $attributes = [
        'kind' => 'internship',
        'origin' => 'native',
        'application_mode' => 'internal',
        'publication_status' => 'published',
        'status' => 'open',
        'work_type' => 'onsite',
        'remote_eligibility' => 'unknown',
        'relocation' => 'unknown',
        'visa_sponsorship' => 'unknown',
        'compensation_status' => 'unspecified',
    ];

    protected $casts = [
        'attachments' => 'array',
        'closing_at' => 'date:Y-m-d',
        'start_date' => 'date:Y-m-d',
        'posted_at' => 'datetime',
        'first_seen_at' => 'datetime',
        'last_verified_at' => 'datetime',
        'content_updated_at' => 'datetime',
        'expires_at' => 'datetime',
        'locations' => 'array',
        'employment_types' => 'array',
        'eligible_countries' => 'array',
        'excluded_countries' => 'array',
        'eligibility_evidence' => 'array',
        'moderation_overrides' => 'array',
        'pay_min' => 'decimal:2',
        'pay_max' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (Internship $internship) {
            $internship->slug = $internship->slug ?: (Str::limit(Str::slug($internship->title), 180, '') ?: 'opportunity');
            $internship->first_seen_at ??= now();
            $internship->content_updated_at ??= now();

            if ($internship->origin === 'native' && $internship->publication_status === 'published') {
                $internship->posted_at ??= now();
            }
        });

        static::updating(function (Internship $internship) {
            $contentChanges = array_diff(array_keys($internship->getDirty()), [
                'last_verified_at', 'first_seen_at', 'updated_at', 'moderation_overrides',
            ]);
            if ($contentChanges) {
                $internship->content_updated_at = now();
            }
            if ($internship->origin === 'native' && $internship->publication_status === 'published' && ! $internship->posted_at) {
                $internship->posted_at = now();
            }
        });
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function field()
    {
        return $this->belongsTo(Field::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function skills()
    {
        return $this->morphToMany(Skill::class, 'skillable');
    }

    public function likes()
    {
        return $this->belongsToMany(Student::class, 'likes');
    }

    public function applications()
    {
        return $this->belongsToMany(Student::class, 'applications')
            ->wherePivot('channel', 'internal')
            ->withPivot(['cover_letter', 'message', 'attachments', 'created_at'])
            ->withTimestamps();
    }

    public function sources()
    {
        return $this->hasMany(OpportunitySource::class);
    }

    public function candidateApplications()
    {
        return $this->hasMany(Application::class);
    }

    public function scopeWithCandidateState($query, $user = null)
    {
        if (! $user || ! $user->isCandidate()) {
            return $query;
        }

        return $query->with(['candidateApplications' => fn ($query) => $query->where('student_id', $user->userable_id)])
            ->withExists(['likes as is_saved' => fn ($query) => $query->where('students.id', $user->userable_id)]);
    }

    public function mergedInto()
    {
        return $this->belongsTo(self::class, 'merged_into_id');
    }

    public function getPublicUrlAttribute(): string
    {
        return $this->publicUrl();
    }

    public function publicUrl(string $locale = 'en'): string
    {
        return rtrim(config('app.url'), '/').($locale === 'fr' ? '/fr' : '').'/opportunities/'.$this->id.'-'.($this->slug ?: (Str::slug($this->title) ?: 'opportunity'));
    }

    public function getLocationLabelAttribute(): string
    {
        $country = $this->country_code ? app(\App\Importing\OpportunityNormalizer::class)->countryName($this->country_code) : null;

        return $this->location ?: (collect([$this->city?->name, $country])->filter()->implode(', ') ?: __('Location not specified'));
    }

    public function scopePublished($query)
    {
        return $query->where('publication_status', 'published')->whereNull('merged_into_id');
    }

    public function scopeAvailable($query)
    {
        return $query->published()->where('status', 'open')
            ->where(function ($query) {
                $query->where('expires_at', '>', now())
                    ->orWhere(function ($query) {
                        $query->whereNull('expires_at')->where(function ($query) {
                            $query->whereNull('closing_at')->orWhereDate('closing_at', '>=', today(config('importer.timezone', 'Africa/Tunis'))->toDateString());
                        });
                    });
            });
    }

    public function scopeOpen($query)
    {
        return $query->available();
    }

    public function scopeListedFor($query, $user = null)
    {
        if ($user && $user->isAdmin()) {
            return $query;
        }
        return $query->where(function ($query) use ($user) {
            $query->available();
            if ($user && $user->isCompany()) {
                $query->orWhere('company_id', $user->userable_id);
            }
        });
    }

    public function isOpen(): bool
    {
        if ($this->publication_status !== 'published' || $this->status !== 'open' || $this->merged_into_id) {
            return false;
        }
        if ($this->expires_at) {
            return $this->expires_at->isFuture();
        }
        return ! $this->closing_at || $this->closing_at->toDateString() >= today(config('importer.timezone', 'Africa/Tunis'))->toDateString();
    }

    public function hasFreshSource(): bool
    {
        if ($this->origin !== 'imported') {
            return true;
        }
        return $this->sources->contains(fn ($item) => in_array($item->state, ['published', 'review'], true)
            && $item->importSource?->permission_status === 'approved'
            && $item->last_verified_at
            && $item->last_verified_at->gte(now()->subHours($item->importSource->freshnessHours())));
    }

    public function isEligibleForCountry(?string $country, bool $includeUnknown = false): bool
    {
        $country = $country ? strtoupper($country) : null;
        $allowed = $this->eligible_countries ?: [];
        if ($country && in_array($country, $this->excluded_countries ?: [], true)) {
            return false;
        }
        if ($allowed && (! $country || ! in_array($country, $allowed, true))) {
            return ! $country && $includeUnknown;
        }
        if (! $country && $this->excluded_countries) {
            return $includeUnknown;
        }
        if ($this->work_type !== 'remote') {
            return true;
        }
        // This tri-state field describes Tunisia specifically, not every candidate country.
        if ($country === 'TN' && $this->remote_eligibility !== 'unknown') {
            return $this->remote_eligibility === 'eligible';
        }
        if ($country && in_array($country, $allowed, true)) {
            return true;
        }
        $evidence = (string) (($this->eligibility_evidence ?: [])['remote'] ?? '');
        $restrictedEvidence = preg_match('/\b(no|not|except|excluding|cannot|without|only|exclusively)\b|must\s+(?:be\s+(?:based|located)|reside|live)/iu', $evidence);
        $worldwide = preg_match('/^(?:remote[ :,-]*)?(?:worldwide|anywhere in the world|global)$/i', trim((string) $this->remote_scope)) && ! $restrictedEvidence;
        $globalHiring = preg_match('/\b(?:hire|hiring|recruit|recruiting)\s+(?:remotely\s+)?(?:worldwide|globally)\b|\bwork\s+(?:remotely\s+)?from\s+(?:anywhere in the world|anywhere)(?=[.!?]|$)/iu', $evidence)
            && ! $restrictedEvidence;
        return (bool) ($worldwide || $globalHiring || $includeUnknown);
    }

    public function scopeWithFilters($query, $fields = [], $companies = [], $cities = [], $search = '')
    {
        return $query->when(count($fields), fn ($query) => $query->whereIn('field_id', $fields))
            ->when(count($companies), fn ($query) => $query->whereIn('company_id', $companies))
            ->when(count($cities), fn ($query) => $query->whereIn('city_id', $cities))
            ->when(Str::length($search) > 0, function ($query) use ($search) {
                $term = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';
                $query->where(function ($query) use ($term) {
                    $query->where('title', 'like', $term)
                        ->orWhere('description', 'like', $term)
                        ->orWhereHas('company', function ($query) use ($term) {
                            $query->where('name', 'like', $term)
                                ->orWhereHas('user', fn ($query) => $query->where('name', 'like', $term));
                        });
                });
            });
    }

    public function scopeSearchFilters($query, array $filters)
    {
        $category = $filters['category'] ?? null;
        if (in_array($category, ['internships', 'jobs'], true)) {
            $query->where('kind', $category === 'jobs' ? 'job' : 'internship');
        } elseif ($category === 'tunisia') {
            $query->where(fn ($query) => $query->where('country_code', 'TN')->orWhereJsonContains('locations', ['country_code' => 'TN']));
        }
        $query->withFilters($filters['fields'] ?? (isset($filters['field']) ? [$filters['field']] : []), $filters['companies'] ?? [], $filters['cities'] ?? [], $filters['search'] ?? '');
        foreach (['kind', 'work_type', 'seniority', 'language', 'program_type', 'compensation_status'] as $field) {
            if (filled($filters[$field] ?? null)) {
                $query->where($field, $filters[$field]);
            }
        }
        if (filled($filters['country'] ?? null)) {
            $query->where(function ($query) use ($filters) {
                $query->where('country_code', $filters['country'])
                    ->orWhereJsonContains('locations', ['country_code' => $filters['country']]);
            });
        }
        if (filled($filters['employment_type'] ?? null)) {
            $query->whereJsonContains('employment_types', $filters['employment_type']);
        }
        if (! empty($filters['posted_within'])) {
            $query->where('posted_at', '>=', now()->subDays((int) $filters['posted_within']));
        }
        if (isset($filters['min_pay']) && filled($filters['currency'] ?? null) && filled($filters['pay_unit'] ?? null)) {
            $query->where('pay_currency', $filters['currency'])->where('pay_unit', $filters['pay_unit'])
                ->where('pay_min', '>=', $filters['min_pay']);
        }
        if (! empty($filters['duration_max'])) {
            $query->where('duration_weeks', '<=', $filters['duration_max']);
        }
        if (! empty($filters['start_after'])) {
            $query->whereDate('start_date', '>=', $filters['start_after']);
        }
        if (($filters['audience'] ?? null) === 'students') {
            $query->where(fn ($query) => $query->where('kind', 'internship')->orWhereIn('seniority', ['entry', 'graduate']));
        } elseif (($filters['audience'] ?? null) === 'professionals') {
            $query->where('kind', 'job');
        }
        if (! empty($filters['remote_from_tunisia']) || $category === 'remote-from-tunisia') {
            $query->where('work_type', 'remote')->where(function ($query) use ($filters) {
                $query->where('remote_eligibility', 'eligible');
                if (! empty($filters['include_unknown_remote'])) {
                    $query->orWhere('remote_eligibility', 'unknown');
                }
            });
        }
        if (! empty($filters['relocation']) || $category === 'relocation') {
            $query->where('relocation', 'yes')->where('country_code', '!=', 'TN');
        }
        return $query;
    }
}

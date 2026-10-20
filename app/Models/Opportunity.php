<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Support\LanguageGuess;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Opportunity extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'description', 'company_id', 'field_id', 'city_id', 'attachments',
        'closing_at', 'work_type', 'duration_weeks', 'term', 'pay_amount', 'pay_unit',
        'status', 'kind', 'origin', 'application_mode', 'application_url', 'slug',
        'publication_status', 'posted_at', 'first_seen_at', 'last_verified_at',
        'content_updated_at', 'expires_at', 'country_code', 'location', 'locations',
        'employment_types', 'seniority', 'program_type', 'start_date', 'remote_scope',
        'remote_eligibility', 'applicant_scope', 'relocation', 'visa_sponsorship', 'eligible_countries',
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
        'applicant_scope' => 'unknown',
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
        static::saving(function (Opportunity $opportunity) {
            if (! $opportunity->language && $opportunity->description) {
                $opportunity->language = LanguageGuess::guess($opportunity->title."\n".$opportunity->description);
            }
        });

        static::creating(function (Opportunity $opportunity) {
            $opportunity->slug = $opportunity->slug ?: (Str::limit(Str::slug($opportunity->title), 180, '') ?: 'opportunity');
            $opportunity->first_seen_at ??= now();
            $opportunity->content_updated_at ??= now();

            if ($opportunity->origin === 'native' && $opportunity->publication_status === 'published') {
                $opportunity->posted_at ??= now();
            }
        });

        static::updating(function (Opportunity $opportunity) {
            $contentChanges = array_diff(array_keys($opportunity->getDirty()), [
                'last_verified_at', 'first_seen_at', 'updated_at', 'moderation_overrides',
            ]);
            if ($contentChanges) {
                $opportunity->content_updated_at = now();
            }
            if ($opportunity->origin === 'native' && $opportunity->publication_status === 'published' && ! $opportunity->posted_at) {
                $opportunity->posted_at = now();
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
        return $this->belongsToMany(Candidate::class, 'likes');
    }

    public function applications()
    {
        return $this->belongsToMany(Candidate::class, 'applications')
            ->wherePivot('channel', 'internal')
            ->withPivot(['cover_letter', 'message', 'attachments', 'created_at'])
            ->withTimestamps();
    }

    public function sources()
    {
        return $this->hasMany(OpportunitySource::class);
    }

    public function translations()
    {
        return $this->hasMany(OpportunityTranslation::class);
    }

    // The language the listing was written in, as a two-letter code.
    public function originalLocale(): ?string
    {
        return LanguageGuess::normalize($this->language);
    }

    public function sourceHash(): string
    {
        return sha1($this->title."\n".$this->description);
    }

    public function translation(string $locale): ?OpportunityTranslation
    {
        return $this->originalLocale() === $locale ? null : $this->translations->firstWhere('locale', $locale);
    }

    // The site language that shows the original text: French for French listings, English for everything else.
    public function primaryLocale(): string
    {
        return $this->originalLocale() === 'fr' ? 'fr' : 'en';
    }

    // Site languages this listing can really be read in: its original one plus any translation.
    public function availableLocales(): array
    {
        return array_values(array_filter(['en', 'fr'], fn ($locale) => $locale === $this->primaryLocale() || $this->translation($locale) !== null));
    }

    public function hasLocale(string $locale): bool
    {
        return in_array($locale, $this->availableLocales(), true);
    }

    public function titleFor(string $locale): string
    {
        return $this->translation($locale)?->title ?? (string) $this->title;
    }

    public function descriptionFor(string $locale): string
    {
        return $this->translation($locale)?->description ?? (string) $this->description;
    }

    public function slugFor(string $locale): string
    {
        return $this->translation($locale)?->slug ?: ($this->slug ?: (Str::slug($this->title) ?: 'opportunity'));
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

        return $query->with(['candidateApplications' => fn ($query) => $query->where('candidate_id', $user->userable_id)])
            ->withExists(['likes as is_saved' => fn ($query) => $query->where('candidates.id', $user->userable_id)]);
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
        return rtrim(config('app.url'), '/').($locale === 'fr' ? '/fr' : '').'/opportunities/'.$this->id.'-'.$this->slugFor($locale);
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

    // remote_eligibility still says whether someone in Tunisia can take a remote role; alerts and recommendations use it.
    public static function tunisiaEligibility(array $data): ?string
    {
        if (($data['work_type'] ?? null) !== 'remote') { return null; }
        if (in_array('TN', $data['excluded_countries'] ?? [], true)) { return 'restricted'; }
        if (in_array('TN', $data['eligible_countries'] ?? [], true)) { return 'eligible'; }
        return match ($data['applicant_scope'] ?? 'unknown') {
            'anywhere' => 'eligible',
            'listed', 'local' => 'restricted',
            default => null,
        };
    }

    // The AI's answer to "can someone living in Tunisia apply?". A role open to everyone that leaves Tunisia out
    // gets TN excluded, so Open worldwide never shows it; a remote role open to a region with Tunisia counts as eligible.
    public static function withTunisiaAnswer(array $data, string $answer): array
    {
        if ($answer === 'no' && ($data['applicant_scope'] ?? null) === 'anywhere') {
            $data['excluded_countries'] = array_values(array_unique([...($data['excluded_countries'] ?? []), 'TN']));
        }
        $data['remote_eligibility'] = self::tunisiaEligibility($data) ?? $data['remote_eligibility'] ?? 'unknown';
        if (($data['work_type'] ?? null) === 'remote' && in_array($answer, ['yes', 'no'], true) && ! in_array('TN', $data['excluded_countries'] ?? [], true)) {
            $data['remote_eligibility'] = $answer === 'yes' ? 'eligible' : 'restricted';
        }
        return $data;
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
                        ->orWhereHas('translations', fn ($query) => $query->where('title', 'like', $term)->orWhere('description', 'like', $term))
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
        // Listings tagged with any of the picked skills.
        if (! empty($filters['skills'])) {
            $query->whereHas('skills', fn ($query) => $query->whereIn('skills.id', $filters['skills']));
        }
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
        // Open worldwide: anyone can apply from any country. Listings that leave Tunisia out are never shown here.
        if (! empty($filters['worldwide'])) {
            $query->where('applicant_scope', 'anywhere')->where(fn ($query) => $query->whereNull('excluded_countries')->orWhereJsonDoesntContain('excluded_countries', 'TN'));
        }
        if (! empty($filters['relocation']) || $category === 'relocation') {
            $query->where('relocation', 'yes')->where('country_code', '!=', 'TN');
        }
        return $query;
    }
}

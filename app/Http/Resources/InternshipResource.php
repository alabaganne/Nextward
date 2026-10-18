<?php

namespace App\Http\Resources;

use App\Support\LanguageGuess;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class InternshipResource extends JsonResource
{
    public function toArray($request)
    {
        $user = $request->user();
        $detail = $request->routeIs('opportunities.show', 'fr.opportunity');
        $locale = $request->is('fr', 'fr/*') ? 'fr' : 'en';
        $candidate = $user && $user->isCandidate() ? $user->userable : null;
        $application = $candidate && $this->relationLoaded('candidateApplications')
            ? $this->candidateApplications->firstWhere('student_id', $candidate->id)
            : null;
        $source = $this->relationLoaded('sources')
            ? ($this->sources->where('state', 'published')->sortByDesc('last_verified_at')->first() ?? $this->sources->sortByDesc('last_verified_at')->first())
            : null;
        $company = $this->company;
        $companyUser = $company?->user;
        $minimum = $this->pay_min ?? $this->pay_amount;
        $maximum = $this->pay_max ?? $this->pay_amount;
        $hasPay = $minimum !== null || $maximum !== null;
        $title = $this->titleFor($locale);
        $description = $this->descriptionFor($locale);
        $translated = $this->translation($locale) !== null;
        $original = $this->originalLocale();

        return [
            'id' => $this->id,
            'title' => $title,
            'description' => $detail ? $description : Str::limit($description, 320),
            'translated' => $translated,
            'original' => $detail && $translated ? [
                'language' => $original,
                'label' => LanguageGuess::name($original, $locale),
                'title' => $this->title,
                'description' => $this->description,
            ] : null,
            'original_language' => $original,
            'original_language_label' => LanguageGuess::name($original, $locale),
            'public_url' => $this->publicUrl($locale),
            'slug' => $this->slug,
            'kind' => $this->kind,
            'origin' => $this->origin,
            'application_mode' => $this->application_mode,
            'application_url' => $this->application_url,
            'publication_status' => $this->publication_status,
            'status' => $this->status,
            'closing_at' => $this->closing_at?->format('F d, Y'),
            'closing_date' => $this->closing_at?->toDateString(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'is_open' => $this->isOpen(),
            'work_type' => $this->work_type,
            'duration_weeks' => $this->duration_weeks,
            'term' => $this->term,
            'program_type' => $this->program_type,
            'start_date' => $this->start_date?->toDateString(),
            'employment_types' => $this->employment_types ?: [],
            'seniority' => $this->seniority,
            'language' => $this->language,
            'country_code' => $this->country_code,
            'location' => $this->location,
            'location_label' => $this->location_label,
            'locations' => $this->locations ?: [],
            'remote_scope' => $this->remote_scope,
            'remote_eligibility' => $this->remote_eligibility,
            'relocation' => $this->relocation,
            'visa_sponsorship' => $this->visa_sponsorship,
            'eligible_countries' => $this->eligible_countries ?: [],
            'excluded_countries' => $this->excluded_countries ?: [],
            'eligibility_evidence' => $detail ? ($this->eligibility_evidence ?: []) : [],
            'pay' => $hasPay ? [
                'amount' => $minimum,
                'min' => $minimum,
                'max' => $maximum,
                'currency' => $this->pay_currency,
                'unit' => $this->pay_unit,
            ] : null,
            'pay_min' => $minimum,
            'pay_max' => $maximum,
            'pay_currency' => $this->pay_currency,
            'pay_unit' => $this->pay_unit,
            'compensation_status' => $this->compensation_status,
            'created_at' => $this->created_at?->diffForHumans(),
            'posted_at' => $this->posted_at?->toIso8601String(),
            'posted_label' => $this->posted_at
                ? __('Posted :time', ['time' => $this->posted_at->diffForHumans()])
                : __('Added to Nextward :time', ['time' => $this->first_seen_at?->diffForHumans() ?? __('recently')]),
            'first_seen_at' => $this->first_seen_at?->toIso8601String(),
            'last_verified_at' => $this->last_verified_at?->toIso8601String(),
            'last_verified_label' => $this->last_verified_at?->diffForHumans(),
            'source_name' => $source?->importSource?->name ?? ($this->origin === 'native' ? __('Posted on Nextward') : null),
            'source_url' => $source?->source_url,
            'company' => [
                'id' => $company?->id,
                'user_id' => $companyUser?->id,
                'image' => $company?->logo,
                'name' => $company?->display_name ?? 'Employer not specified',
                'public_url' => $company?->hasPublicName() ? $company->publicUrl($locale) : null,
                'website' => $company?->website,
                'country_code' => $company?->country_code,
                'location' => $company?->location,
                'email' => $detail ? $companyUser?->email : null,
                'phone_number' => $detail ? $companyUser?->phone_number : null,
                'linkedin_profile_url' => $companyUser?->linkedin_profile_url,
                'city' => ['name' => $company?->city?->name],
            ],
            'city' => ['id' => $this->city_id, 'name' => $this->city?->name],
            'field' => ['id' => $this->field_id, 'name' => $this->field?->name],
            'skills' => $this->skills->pluck('name'),
            'attachments' => $this->attachments ?: [],
            'applications_count' => $this->when($this->applications_count !== null, $this->applications_count),
            'is_new' => $this->posted_at && $this->posted_at->gt(now()->subWeek()),
            'application' => $application ? [
                'id' => $application->id,
                'student_id' => $application->student_id,
                'channel' => $application->channel,
                'status' => $application->status,
                'created_at' => $application->created_at?->format('F d, Y'),
            ] : null,
            'liked' => $candidate ? (bool) $this->is_saved : false,
        ];
    }
}

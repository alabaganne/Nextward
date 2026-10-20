<?php

namespace App\Jobs;

use App\Ai\OpportunityClassifier;
use App\Importing\OpportunityImporter;
use App\Models\Field;
use App\Models\Opportunity;
use App\Models\Skill;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;

// The AI reads a role an employer posted and fills what they left blank: skills, field, experience,
// contract types, programme and who can apply. Anything the employer chose stays as it is.
class ReviewPostedOpportunity implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Queueable;

    // The employer form allows up to 12 skills.
    private const MAX_SKILLS = 12;

    public int $timeout = 300;
    public int $uniqueFor = 900;

    public function __construct(public int $opportunityId) {}

    public function uniqueId(): string { return 'review-posted-opportunity:'.$this->opportunityId; }

    public function handle(OpportunityClassifier $ai, OpportunityImporter $importer): void
    {
        $role = Opportunity::with('company.user', 'city', 'skills')->find($this->opportunityId);
        if (! $role || $role->origin === 'imported' || ! $ai->enabled()) { return; }
        $skills = $importer->skillNames();
        $review = $ai->classifyMany([$role->id => [
            'title' => $role->title,
            'employer' => $role->company?->display_name ?? '',
            'location' => $role->location ?: implode(', ', array_filter([$role->city?->name, $role->country_code])),
            'work_type' => $role->work_type ?: 'unknown',
            'employment_types' => $role->employment_types ?? [],
            'description' => $role->description,
        ]], $importer->fieldNames(), $skills)[$role->id] ?? null;
        if (! $review) { return; }

        $role->update($this->blanks($role, $review));
        $picked = $role->skills->pluck('id');
        $found = collect($review['skills'] ?? [])->intersect($skills)->map(fn ($name) => Skill::firstOrCreate(['name' => $name])->id);
        $ids = $picked->merge($found)->unique()->take(self::MAX_SKILLS)->values();
        if ($ids->count() > $picked->count()) { $role->skills()->sync($ids->all()); }
    }

    private function blanks(Opportunity $role, array $review): array
    {
        $empty = fn ($value) => $value === null || $value === '' || $value === 'unknown' || $value === [];
        $data = [];
        if (! $role->field_id && $review['field'] !== 'Other') { $data['field_id'] = Field::where('name', $review['field'])->value('id'); }
        if ($empty($role->seniority) && $review['seniority'] !== 'unspecified') { $data['seniority'] = $review['seniority']; }
        if ($empty($role->employment_types) && $review['employment_types']) { $data['employment_types'] = array_values(array_unique($review['employment_types'])); }
        if ($role->kind === 'internship' && $empty($role->program_type) && $review['program_type'] !== 'none') { $data['program_type'] = $review['program_type']; }
        foreach (['relocation', 'visa_sponsorship'] as $key) {
            if ($empty($role->{$key}) && $review[$key] !== 'unknown') { $data[$key] = $review[$key]; }
        }
        // Who can apply is only filled when the employer did not say.
        if ($empty($role->applicant_scope) && $review['applicant_scope'] !== 'unknown') {
            $facts = ['applicant_scope' => $review['applicant_scope'], 'work_type' => $role->work_type, 'remote_eligibility' => $role->remote_eligibility];
            foreach (['eligible_countries', 'excluded_countries'] as $key) {
                $facts[$key] = $role->{$key} ?: array_values(array_unique($review[$key]));
            }
            $facts = Opportunity::withTunisiaAnswer($facts, $review['tunisia'] ?? 'unknown');
            unset($facts['work_type']);
            $data = [...$data, ...$facts];
            if ($review['evidence'] !== '') { $data['eligibility_evidence'] = [...($role->eligibility_evidence ?? []), 'remote' => Str::limit($review['evidence'], 400)]; }
        }
        return array_filter($data, fn ($value) => $value !== null);
    }
}

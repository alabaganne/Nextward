<?php

namespace App\Http\Controllers;

use App\Http\Requests\OpportunityRequest;
use App\Http\Resources\OpportunityResource;
use App\Jobs\ReviewPostedOpportunity;
use App\Models\City;
use App\Models\Company;
use App\Models\Field;
use App\Models\Opportunity;
use App\Models\Skill;
use App\Support\PublicSeo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class OpportunityController extends Controller
{
    public function index(Request $request, ?string $category = null)
    {
        $categories = ['internships' => 'Internships', 'jobs' => 'Jobs'];
        abort_if($category && ! array_key_exists($category, $categories), 404);
        $app = $request->routeIs('app.*');
        $filters = $this->browseFilters($request);
        $request->query->replace($filters);
        $queryFilters = array_merge($filters, ['category' => $category]);
        $results = Opportunity::with(['company.user', 'company.city', 'field', 'city', 'skills', 'sources.importSource', 'translations'])
            ->available()->searchFilters($queryFilters)->withCandidateState($request->user())
            ->withCount('applications')
            ->orderByRaw('COALESCE(posted_at, first_seen_at, created_at) DESC')->orderByDesc('id')
            ->paginate(20)->withQueryString();
        abort_if($results->currentPage() > $results->lastPage(), 404);

        $countries = Opportunity::available()->whereNotNull('country_code')->distinct()->orderBy('country_code')
            ->pluck('country_code')->map(fn ($code) => [
                'code' => $code,
                'name' => \App\Support\Countries::name($code, $request->is('fr/*') ? 'fr' : 'en'),
            ])->values();

        $seo = PublicSeo::browse($request, $category, $results->total());
        // Open listings next to the older ones that closed and stay archived, for the current tab.
        $kind = ['internships' => 'internship', 'jobs' => 'job'][$category] ?? null;
        $counts = Cache::remember('opportunities:counts:'.($kind ?? 'all'), now()->addMinutes(10), function () use ($kind) {
            $published = Opportunity::published()->when($kind, fn ($query) => $query->where('kind', $kind));
            $open = (clone $published)->available()->count();
            return ['open' => $open, 'archived' => (clone $published)->count() - $open];
        });

        return Inertia::render('Opportunities/Index', [
            'filters' => $filters,
            'opportunities' => OpportunityResource::collection($results),
            'category' => $category,
            'categoryLabel' => $categories[$category] ?? 'Explore opportunities',
            'countries' => $countries,
            'counts' => $counts + ['days' => config('importer.retention_days')],
            'fields' => Field::withCount(['opportunities' => fn ($query) => $query->available()->searchFilters(array_diff_key($queryFilters, ['fields' => true, 'field' => true]))])->orderBy('name')->get(),
            'skills' => $this->skillCounts(array_diff_key($queryFilters, ['skills' => true]), $filters['skills'] ?? []),
            'seo' => $app ? PublicSeo::workspace($seo) : $seo,
        ]);
    }

    // Skills on the listings that match the other filters, most used first. Picked skills always stay in the list.
    private function skillCounts(array $filters, array $picked): array
    {
        $counts = DB::table('skillables')->where('skillable_type', 'opportunity')
            ->whereIn('skillable_id', Opportunity::available()->searchFilters($filters)->select('opportunities.id'))
            ->selectRaw('skill_id, count(*) as total')->groupBy('skill_id')->pluck('total', 'skill_id');

        return Skill::whereIn('id', $counts->keys()->merge($picked)->unique())->get(['id', 'name'])
            ->map(fn ($skill) => ['id' => $skill->id, 'name' => $skill->name, 'opportunities_count' => (int) ($counts[$skill->id] ?? 0)])
            ->sortBy([['opportunities_count', 'desc'], ['name', 'asc']])->values()->all();
    }

    private function browseFilters(Request $request): array
    {
        $input = $request->query();
        foreach (['country', 'currency'] as $field) {
            if (isset($input[$field]) && is_string($input[$field])) {
                $input[$field] = strtoupper(trim($input[$field]));
            }
        }
        if (isset($input['search']) && is_string($input['search'])) {
            $input['search'] = trim($input['search']);
        }
        foreach (['fields', 'skills', 'companies', 'cities'] as $field) {
            if (isset($input[$field]) && is_array($input[$field])) {
                $input[$field] = array_values(array_filter($input[$field], fn ($value) => $value !== '' && $value !== null));
            }
        }
        $validator = Validator::make($input, [
            'search' => 'nullable|string|max:150',
            'kind' => 'nullable|in:internship,job',
            'country' => 'nullable|string|regex:/^[A-Z]{2}$/',
            'work_type' => 'nullable|in:onsite,hybrid,remote',
            'seniority' => 'nullable|in:entry,graduate,mid,senior,lead,executive',
            'employment_type' => 'nullable|in:FULL_TIME,PART_TIME,CONTRACTOR,TEMPORARY,INTERN,VOLUNTEER,OTHER',
            'language' => 'nullable|string|regex:/^[a-z]{2}(-[A-Z]{2})?$/',
            'program_type' => 'nullable|in:pfe,summer,apprenticeship,graduate,other',
            'compensation_status' => 'nullable|in:paid,unpaid,unspecified',
            'fields' => 'nullable|array|max:20',
            'fields.*' => 'integer|min:1',
            'field' => 'nullable|integer|min:1',
            'skills' => 'nullable|array|max:20',
            'skills.*' => 'integer|min:1',
            'companies' => 'nullable|array|max:20',
            'companies.*' => 'integer|min:1',
            'cities' => 'nullable|array|max:20',
            'cities.*' => 'integer|min:1',
            'posted_within' => 'nullable|integer|in:1,3,7,14,30,90',
            'min_pay' => 'nullable|numeric|min:0|max:9999999999.99',
            'currency' => 'nullable|required_with:min_pay|string|regex:/^[A-Z]{3}$/',
            'pay_unit' => 'nullable|required_with:min_pay|in:hour,day,week,month,year',
            'duration_max' => 'nullable|integer|min:1|max:104',
            'start_after' => 'nullable|date_format:Y-m-d',
            'audience' => 'nullable|in:students,professionals,both',
            'worldwide' => 'nullable|boolean',
            'remote_from_tunisia' => 'nullable|boolean',
            'include_unknown_remote' => 'nullable|boolean',
            'relocation' => 'nullable|boolean',
            'page' => 'nullable|integer|min:1|max:10000',
        ]);
        abort_if($validator->fails(), 404);

        return array_filter($validator->validated(), fn ($value) => $value !== null && $value !== '' && $value !== []);
    }

    // Most used skills first, so the form can show the common ones before a search.
    private function skillChoices()
    {
        return Skill::select('id', 'name')->withCount('opportunities')->orderByDesc('opportunities_count')->orderBy('name')->get()
            ->map(fn ($skill) => ['id' => $skill->id, 'name' => $skill->name]);
    }

    public function getFields()
    {
        return Field::select('id', 'name')->orderBy('name')->get();
    }

    public function create()
    {
        $this->authorize('create', Opportunity::class);
        return Inertia::render('Opportunities/Edit', [
            'fields' => $this->getFields(),
            'cities' => City::select('id', 'name')->orderBy('name')->get(),
            'skills' => $this->skillChoices(),
            'aiReview' => app(\App\Ai\OpportunityClassifier::class)->enabled(),
        ]);
    }

    public function store(OpportunityRequest $request)
    {
        $this->authorize('create', Opportunity::class);
        $company = $request->user()->userable;
        $data = $this->nativeData($request);
        $data['company_id'] = $company->id;
        $data['origin'] = 'native';
        $data['application_mode'] = 'internal';
        $data['attachments'] = $this->storeAttachments($request, 'internships');
        $opportunity = Opportunity::create($data);
        $opportunity->skills()->sync($request->validated('skills') ?? []);
        ReviewPostedOpportunity::dispatch($opportunity->id);

        return redirect($opportunity->publication_status === 'published'
            ? route('app.opportunities.show', ['opportunity' => $opportunity->id.'-'.$opportunity->slug])
            : route('opportunities.edit', $opportunity))
            ->with('toast', ['type' => 'store', 'message' => 'Your opportunity has been saved.']);
    }

    public function show(Opportunity $opportunity)
    {
        $opportunity = $this->canonicalOpportunity($opportunity);
        abort_unless($opportunity->publication_status === 'published', 404);
        return redirect($opportunity->public_url, 301);
    }

    public function publicShow(Request $request, string $segment)
    {
        abort_unless(preg_match('/^([1-9][0-9]*)(?:-.+)?$/u', $segment, $matches), 404);
        $opportunity = $this->canonicalOpportunity(Opportunity::findOrFail($matches[1]));
        abort_unless($opportunity->publication_status === 'published', 404);
        $locale = $request->is('fr/*') ? 'fr' : 'en';
        $app = $request->routeIs('app.*');
        $canonicalSegment = basename(parse_url($opportunity->publicUrl($locale), PHP_URL_PATH));
        if ($segment !== $canonicalSegment) {
            return redirect($app ? route('app.opportunities.show', $canonicalSegment) : $opportunity->publicUrl($locale), 301);
        }
        $opportunity->load(['company.user', 'company.city', 'field', 'city', 'skills', 'sources.importSource', 'translations']);
        if ($request->user()?->isCandidate()) {
            $opportunity->load(['candidateApplications' => fn ($query) => $query->where('candidate_id', $request->user()->userable_id)]);
            $opportunity->loadExists(['likes as is_saved' => fn ($query) => $query->where('candidates.id', $request->user()->userable_id)]);
        }
        $related = Opportunity::with(['company.user', 'company.city', 'field', 'city', 'skills', 'sources.importSource', 'translations'])
            ->available()->where('id', '!=', $opportunity->id)->where('kind', $opportunity->kind)
            ->when($opportunity->field_id, fn ($query) => $query->where('field_id', $opportunity->field_id))
            ->withCandidateState($request->user())
            ->orderByRaw('COALESCE(posted_at, first_seen_at, created_at) DESC')->orderByDesc('id')->limit(4)->get();

        return Inertia::render('Opportunities/Show', [
            'opportunity' => (new OpportunityResource($opportunity))->resolve($request),
            'related' => OpportunityResource::collection($related),
            'seo' => $app ? PublicSeo::workspace(PublicSeo::opportunity($opportunity)) : PublicSeo::opportunity($opportunity),
            'translation' => $app ? null : parse_url($opportunity->publicUrl($locale === 'fr' ? 'en' : 'fr'), PHP_URL_PATH),
            'kits' => $request->user()?->isCandidate()
                ? \App\Models\ApplicationKit::where('candidate_id', $request->user()->userable_id)->where('opportunity_id', $opportunity->id)->get(['id', 'locale', 'status'])
                : [],
            'kitLocales' => collect(\App\Ai\ApplicationKits::locales($opportunity))->map(fn ($code) => [
                'code' => $code,
                'label' => \App\Support\LanguageGuess::name($code, $locale) ?? strtoupper($code),
                'original' => $code === $opportunity->originalLocale(),
            ])->all(),
        ]);
    }

    private function canonicalOpportunity(Opportunity $opportunity): Opportunity
    {
        $seen = [];
        while ($opportunity->merged_into_id) {
            abort_if(isset($seen[$opportunity->id]) || count($seen) >= 10, 404);
            $seen[$opportunity->id] = true;
            $opportunity = Opportunity::findOrFail($opportunity->merged_into_id);
        }
        return $opportunity;
    }

    public function edit(Opportunity $opportunity)
    {
        $this->authorize('update', $opportunity);
        return Inertia::render('Opportunities/Edit', [
            'opportunity' => $opportunity->load('skills:id,name'),
            'fields' => $this->getFields(),
            'cities' => City::select('id', 'name')->orderBy('name')->get(),
            'skills' => $this->skillChoices(),
            'aiReview' => app(\App\Ai\OpportunityClassifier::class)->enabled(),
        ]);
    }

    public function update(OpportunityRequest $request, Opportunity $opportunity)
    {
        $this->authorize('update', $opportunity);
        $data = $this->nativeData($request);
        $oldAttachments = $opportunity->attachments ?: [];
        $replaceFiles = $request->hasFile('attachment_files') || $request->boolean('remove_attachments');
        if ($replaceFiles) {
            $data['attachments'] = $this->storeAttachments($request, 'internships');
        }
        $skills = $request->has('skills') ? ($request->validated('skills') ?? []) : null;
        if ($opportunity->origin === 'imported') {
            $data['moderation_overrides'] = array_replace($opportunity->moderation_overrides ?: [], $data, $skills === null ? [] : ['skills' => $skills]);
        }
        $opportunity->update($data);
        if ($skills !== null) {
            $opportunity->skills()->sync($skills);
        }
        if ($opportunity->origin !== 'imported' && $opportunity->wasChanged(['title', 'description'])) {
            ReviewPostedOpportunity::dispatch($opportunity->id);
        }
        if ($replaceFiles) {
            $this->deleteAttachments($oldAttachments);
        }

        return redirect($opportunity->publication_status === 'published'
            ? route('app.opportunities.show', ['opportunity' => $opportunity->id.'-'.$opportunity->slug])
            : route('opportunities.edit', $opportunity))
            ->with('toast', ['type' => 'update', 'message' => 'Opportunity updated successfully.']);
    }

    public function destroy(Opportunity $opportunity)
    {
        $this->authorize('delete', $opportunity);
        $opportunity->update(['status' => 'closed', 'publication_status' => 'suppressed']);
        return Redirect::route('dashboard')->with('toast', [
            'type' => 'destroy',
            'message' => 'Opportunity removed from public discovery. Application history is retained.',
        ]);
    }

    private function nativeData(OpportunityRequest $request): array
    {
        $data = $request->safe()->except(['attachment_files', 'remove_attachments', 'skills']);
        if (array_key_exists('pay_amount', $data) && ! array_key_exists('pay_min', $data)) {
            $data['pay_min'] = $data['pay_amount'];
            $data['pay_max'] = $data['pay_amount'];
        } elseif (array_key_exists('pay_min', $data) || array_key_exists('pay_max', $data)) {
            $data['pay_amount'] = null;
        }
        if (($data['compensation_status'] ?? null) === 'unpaid') {
            $data['pay_min'] = 0;
            $data['pay_max'] = 0;
            $data['pay_amount'] = null;
        } elseif (($data['pay_min'] ?? 0) > 0 || ($data['pay_max'] ?? 0) > 0) {
            $data['compensation_status'] = 'paid';
        }
        foreach (['work_type', 'applicant_scope', 'relocation', 'visa_sponsorship', 'compensation_status'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] === null) {
                unset($data[$field]);
            }
        }
        if (array_key_exists('applicant_scope', $data)) {
            $data['remote_eligibility'] = Opportunity::tunisiaEligibility($data) ?? 'unknown';
        }
        return $data;
    }

    private function storeAttachments(Request $request, string $directory): array
    {
        return collect($request->file('attachment_files', []))->map(function ($file) use ($directory) {
            $path = $file->store($directory, 'public');
            return [
                'name' => $file->getClientOriginalName(),
                'url' => Storage::disk('public')->url($path),
                'size' => $file->getSize(),
                'mime' => $file->getMimeType(),
            ];
        })->values()->all();
    }

    private function deleteAttachments(array $attachments): void
    {
        foreach ($attachments as $attachment) {
            $path = is_array($attachment) ? parse_url($attachment['url'] ?? '', PHP_URL_PATH) : null;
            if (is_string($path) && str_starts_with($path, '/storage/internships/') && ! str_contains($path, '..')) {
                Storage::disk('public')->delete(substr($path, strlen('/storage/')));
            }
        }
    }
}

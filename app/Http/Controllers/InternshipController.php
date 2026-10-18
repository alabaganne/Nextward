<?php

namespace App\Http\Controllers;

use App\Http\Requests\InternshipRequest;
use App\Http\Resources\InternshipResource;
use App\Models\City;
use App\Models\Company;
use App\Models\Field;
use App\Models\Internship;
use App\Models\Skill;
use App\Support\PublicSeo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class InternshipController extends Controller
{
    public function index(Request $request, ?string $category = null)
    {
        $categories = [
            'internships' => 'Internships',
            'jobs' => 'Jobs',
            'tunisia' => 'Opportunities in Tunisia',
            'remote-from-tunisia' => 'Remote opportunities from Tunisia',
            'relocation' => 'Opportunities abroad with relocation',
        ];
        abort_if($category && ! array_key_exists($category, $categories), 404);
        $filters = $this->browseFilters($request);
        $request->query->replace($filters);
        $queryFilters = array_merge($filters, ['category' => $category]);
        $results = Internship::with(['company.user', 'company.city', 'field', 'city', 'skills', 'sources.importSource', 'translations'])
            ->available()->searchFilters($queryFilters)->withCandidateState($request->user())
            ->withCount('applications')
            ->orderByRaw('COALESCE(posted_at, first_seen_at, created_at) DESC')->orderByDesc('id')
            ->paginate(20)->withQueryString();
        abort_if($results->currentPage() > $results->lastPage(), 404);

        $companyFilters = array_diff_key($queryFilters, ['companies' => true]);
        $companies = Company::with('user')
            ->whereHas('internships', fn ($query) => $query->available()->searchFilters($companyFilters))
            ->withCount(['internships' => fn ($query) => $query->available()->searchFilters($companyFilters)])
            ->orderBy('name')->limit(50)->get()->map(fn ($company) => [
                'id' => $company->id,
                'name' => $company->display_name,
                'internships_count' => $company->internships_count,
            ]);
        $countries = Internship::available()->whereNotNull('country_code')->distinct()->orderBy('country_code')
            ->pluck('country_code')->map(fn ($code) => [
                'code' => $code,
                'name' => class_exists(\Locale::class) ? (\Locale::getDisplayRegion('und_'.$code, $request->is('fr/*') ? 'fr' : 'en') ?: $code) : $code,
            ])->values();

        return Inertia::render('Internships/Index', [
            'filters' => $filters,
            'internships' => InternshipResource::collection($results),
            'category' => $category,
            'categoryLabel' => $categories[$category] ?? 'Explore opportunities',
            'countries' => $countries,
            'companies' => $companies,
            'fields' => Field::withCount(['internships' => fn ($query) => $query->available()->searchFilters(array_diff_key($queryFilters, ['fields' => true, 'field' => true]))])->orderBy('name')->get(),
            'cities' => City::withCount(['internships' => fn ($query) => $query->available()->searchFilters(array_diff_key($queryFilters, ['cities' => true]))])
                ->orderByDesc('internships_count')->orderBy('name')->limit(60)->get(),
            'seo' => PublicSeo::browse($request, $category, $results->total()),
        ]);
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
        foreach (['fields', 'companies', 'cities'] as $field) {
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
            'remote_from_tunisia' => 'nullable|boolean',
            'include_unknown_remote' => 'nullable|boolean',
            'relocation' => 'nullable|boolean',
            'page' => 'nullable|integer|min:1|max:10000',
        ]);
        abort_if($validator->fails(), 404);

        return array_filter($validator->validated(), fn ($value) => $value !== null && $value !== '' && $value !== []);
    }

    public function getFields()
    {
        return Field::select('id', 'name')->orderBy('name')->get();
    }

    public function create()
    {
        $this->authorize('create', Internship::class);
        return Inertia::render('Internships/Edit', [
            'fields' => $this->getFields(),
            'cities' => City::select('id', 'name')->orderBy('name')->get(),
            'skills' => Skill::select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function store(InternshipRequest $request)
    {
        $this->authorize('create', Internship::class);
        $company = $request->user()->userable;
        $data = $this->nativeData($request);
        $data['company_id'] = $company->id;
        $data['origin'] = 'native';
        $data['application_mode'] = 'internal';
        $data['attachments'] = $this->storeAttachments($request, 'internships');
        $internship = Internship::create($data);
        $internship->skills()->sync($request->validated('skills') ?? []);

        return redirect($internship->publication_status === 'published'
            ? route('opportunities.show', ['opportunity' => $internship->id.'-'.$internship->slug])
            : route('internships.edit', $internship))
            ->with('toast', ['type' => 'store', 'message' => 'Your opportunity has been saved.']);
    }

    public function show(Internship $internship)
    {
        $internship = $this->canonicalOpportunity($internship);
        abort_unless($internship->publication_status === 'published', 404);
        return redirect($internship->public_url, 301);
    }

    public function publicShow(Request $request, string $opportunity)
    {
        abort_unless(preg_match('/^([1-9][0-9]*)(?:-.+)?$/u', $opportunity, $matches), 404);
        $internship = $this->canonicalOpportunity(Internship::findOrFail($matches[1]));
        abort_unless($internship->publication_status === 'published', 404);
        $locale = $request->is('fr/*') ? 'fr' : 'en';
        $canonicalSegment = basename(parse_url($internship->publicUrl($locale), PHP_URL_PATH));
        if ($opportunity !== $canonicalSegment) {
            return redirect($internship->publicUrl($locale), 301);
        }
        $internship->load(['company.user', 'company.city', 'field', 'city', 'skills', 'sources.importSource', 'translations']);
        if ($request->user()?->isCandidate()) {
            $internship->load(['candidateApplications' => fn ($query) => $query->where('student_id', $request->user()->userable_id)]);
            $internship->loadExists(['likes as is_saved' => fn ($query) => $query->where('students.id', $request->user()->userable_id)]);
        }
        $related = Internship::with(['company.user', 'company.city', 'field', 'city', 'skills', 'sources.importSource', 'translations'])
            ->available()->where('id', '!=', $internship->id)->where('kind', $internship->kind)
            ->when($internship->field_id, fn ($query) => $query->where('field_id', $internship->field_id))
            ->withCandidateState($request->user())
            ->orderByRaw('COALESCE(posted_at, first_seen_at, created_at) DESC')->orderByDesc('id')->limit(4)->get();

        return Inertia::render('Internships/Show', [
            'internship' => (new InternshipResource($internship))->resolve($request),
            'related' => InternshipResource::collection($related),
            'seo' => PublicSeo::opportunity($internship),
            'translation' => parse_url($internship->publicUrl($locale === 'fr' ? 'en' : 'fr'), PHP_URL_PATH),
        ]);
    }

    private function canonicalOpportunity(Internship $internship): Internship
    {
        $seen = [];
        while ($internship->merged_into_id) {
            abort_if(isset($seen[$internship->id]) || count($seen) >= 10, 404);
            $seen[$internship->id] = true;
            $internship = Internship::findOrFail($internship->merged_into_id);
        }
        return $internship;
    }

    public function edit(Internship $internship)
    {
        $this->authorize('update', $internship);
        return Inertia::render('Internships/Edit', [
            'internship' => $internship->load('skills:id,name'),
            'fields' => $this->getFields(),
            'cities' => City::select('id', 'name')->orderBy('name')->get(),
            'skills' => Skill::select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function update(InternshipRequest $request, Internship $internship)
    {
        $this->authorize('update', $internship);
        $data = $this->nativeData($request);
        $oldAttachments = $internship->attachments ?: [];
        $replaceFiles = $request->hasFile('attachment_files') || $request->boolean('remove_attachments');
        if ($replaceFiles) {
            $data['attachments'] = $this->storeAttachments($request, 'internships');
        }
        $skills = $request->has('skills') ? ($request->validated('skills') ?? []) : null;
        if ($internship->origin === 'imported') {
            $data['moderation_overrides'] = array_replace($internship->moderation_overrides ?: [], $data, $skills === null ? [] : ['skills' => $skills]);
        }
        $internship->update($data);
        if ($skills !== null) {
            $internship->skills()->sync($skills);
        }
        if ($replaceFiles) {
            $this->deleteAttachments($oldAttachments);
        }

        return redirect($internship->publication_status === 'published'
            ? route('opportunities.show', ['opportunity' => $internship->id.'-'.$internship->slug])
            : route('internships.edit', $internship))
            ->with('toast', ['type' => 'update', 'message' => 'Opportunity updated successfully.']);
    }

    public function destroy(Internship $internship)
    {
        $this->authorize('delete', $internship);
        $internship->update(['status' => 'closed', 'publication_status' => 'suppressed']);
        return Redirect::route('dashboard')->with('toast', [
            'type' => 'destroy',
            'message' => 'Opportunity removed from public discovery. Application history is retained.',
        ]);
    }

    private function nativeData(InternshipRequest $request): array
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
        foreach (['work_type', 'remote_eligibility', 'relocation', 'visa_sponsorship', 'compensation_status'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] === null) {
                unset($data[$field]);
            }
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

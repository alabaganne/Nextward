<?php

namespace App\Http\Controllers;

use App\Http\Resources\OpportunityResource;
use App\Models\Company;
use App\Models\Field;
use App\Support\Countries;
use App\Support\PublicSeo;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PublicCompanyController extends Controller
{
    // Everyone gets the public directory here; signed-in users have the workspace list at /app/companies.
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'field' => ['nullable', 'integer'],
            'show' => ['nullable', 'in:hiring,all'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $search = trim($filters['search'] ?? '');
        $field = isset($filters['field']) ? (int) $filters['field'] : null;
        $all = ($filters['show'] ?? 'hiring') === 'all' && ! $field;
        $open = fn ($query) => $query->available()->when($field, fn ($query) => $query->where('field_id', $field));

        $companies = self::cards(Company::listed()
            ->when(! $all, fn ($query) => $query->whereHas('opportunities', $open))
            ->when($search !== '', function ($query) use ($search) {
                $term = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';
                $query->where(fn ($query) => $query->where('name', 'like', $term)->orWhere('location', 'like', $term)
                    ->orWhereHas('user', fn ($query) => $query->where('name', 'like', $term))
                    ->orWhereHas('city', fn ($query) => $query->where('name', 'like', $term)));
            }), $open)->paginate(24)->withQueryString();
        abort_if($companies->currentPage() > max(1, $companies->lastPage()), 404);

        return Inertia::render('Companies/Directory', [
            'companies' => $companies->through(fn ($company) => self::card($company, $request)),
            'fields' => Field::whereHas('opportunities', fn ($query) => $query->available())->orderBy('name')->get(['id', 'name']),
            'filters' => ['search' => $search, 'field' => $field, 'show' => $all ? 'all' : 'hiring'],
            'seo' => PublicSeo::companies($request, $companies->total()),
        ]);
    }

    // Companies with their open roles counted, most open roles first.
    public static function cards($query, ?\Closure $open = null)
    {
        $open ??= fn ($query) => $query->available();

        return $query->with(['user', 'city'])
            ->withCount(['opportunities as open_roles_count' => $open])
            ->orderByDesc('open_roles_count')->orderBy('name');
    }

    public static function card(Company $company, Request $request): array
    {
        $locale = $request->is('fr', 'fr/*') ? 'fr' : 'en';

        return [
            'id' => $company->id,
            'name' => $company->display_name,
            'logo' => $company->logo,
            'verified' => (bool) $company->verified_at,
            'location' => $company->city?->name ?: $company->location,
            'about' => Str::limit(trim(preg_replace('/\s+/', ' ', (string) $company->about)), 240) ?: null,
            'stage' => $company->stage,
            'size' => $company->size,
            'url' => $company->publicUrl($locale),
            'open_roles_count' => $company->open_roles_count,
        ];
    }

    public function show(Request $request, string $companySlug)
    {
        abort_unless(preg_match('/^([1-9][0-9]*)-/', $companySlug, $matches), 404);
        $model = Company::with('user', 'city')->findOrFail($matches[1]);
        $locale = $request->is('fr/*') ? 'fr' : 'en';
        if ($model->claimed_by_company_id) {
            return redirect(Company::with('user')->findOrFail($model->claimed_by_company_id)->publicUrl($locale), 301);
        }
        abort_unless($model->hasPublicName(), 404);
        if ($companySlug !== basename(parse_url($model->publicUrl($locale), PHP_URL_PATH))) {
            return redirect($model->publicUrl($locale), 301);
        }
        $opportunities = $model->opportunities()->available()
            ->with(['company.user', 'company.city', 'field', 'city', 'skills', 'sources.importSource', 'translations'])
            ->withCandidateState($request->user())
            ->orderByRaw('COALESCE(posted_at, first_seen_at, created_at) DESC')->orderByDesc('id')->limit(50)->get();

        return Inertia::render('Companies/Public', [
            'company' => [
                'id' => $model->id,
                'name' => $model->display_name,
                'logo' => $model->logo,
                'about' => $model->about,
                'website' => $model->website,
                'location' => $model->city?->name ?: $model->location,
                'managed' => (bool) $model->user,
                ...$model->only('stage', 'size', 'founded_year', 'linkedin_url', 'careers_url'),
                'verified' => (bool) $model->verified_at,
                ...self::profile($model, $locale),
            ],
            'opportunities' => OpportunityResource::collection($opportunities),
            'seo' => PublicSeo::company($model, $opportunities->count()),
            'translation' => parse_url($model->publicUrl($locale === 'fr' ? 'en' : 'fr'), PHP_URL_PATH),
        ]);
    }

    // Facts and hiring numbers shown on both company pages.
    public static function profile(Company $company, string $locale = 'en'): array
    {
        $roles = $company->opportunities()->available()->with(['skills' => fn ($query) => $query->select('skills.id', 'skills.name'), 'field:id,name'])
            ->get(['id', 'kind', 'work_type', 'field_id', 'country_code']);
        $top = fn ($values, int $limit) => $values->filter()->countBy()->sortDesc()->take($limit)
            ->map(fn ($count, $name) => ['name' => $name, 'count' => $count])->values();
        $site = $company->website ? preg_replace('/^www\./', '', (string) parse_url($company->website, PHP_URL_HOST)) : null;

        return [
            'facts' => [
                'location' => $company->city?->name ?: $company->location,
                'country' => Countries::name($company->country_code, $locale),
                'site' => $site ?: null,
                'checked_at' => $company->verified_at?->toDateString(),
            ],
            'hiring' => [
                'open' => $roles->count(),
                'jobs' => $roles->where('kind', 'job')->count(),
                'internships' => $roles->where('kind', 'internship')->count(),
                'work_types' => $roles->countBy('work_type'),
                'skills' => $top($roles->flatMap(fn ($role) => $role->skills->pluck('name')), 12),
                'fields' => $top($roles->map(fn ($role) => $role->field?->name), 4),
                'countries' => $top($roles->map(fn ($role) => Countries::name($role->country_code, $locale)), 5),
            ],
        ];
    }
}

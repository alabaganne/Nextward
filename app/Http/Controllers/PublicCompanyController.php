<?php

namespace App\Http\Controllers;

use App\Http\Resources\OpportunityResource;
use App\Models\Company;
use App\Models\Field;
use App\Support\PublicSeo;
use Illuminate\Http\Request;
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
            }), $open, 3)->paginate(24)->withQueryString();
        abort_if($companies->currentPage() > max(1, $companies->lastPage()), 404);

        return Inertia::render('Companies/Directory', [
            'companies' => $companies->through(fn ($company) => self::card($company, $request)),
            'fields' => Field::whereHas('opportunities', fn ($query) => $query->available())->orderBy('name')->get(['id', 'name']),
            'filters' => ['search' => $search, 'field' => $field, 'show' => $all ? 'all' : 'hiring'],
            'seo' => PublicSeo::companies($request, $companies->total()),
        ]);
    }

    // Companies with their open roles counted and the newest few loaded, most open roles first.
    public static function cards($query, ?\Closure $open = null, int $roles = 0)
    {
        $open ??= fn ($query) => $query->available();

        return $query->with(['user', 'city'])
            ->withCount(['opportunities as open_roles_count' => $open])
            ->when($roles, fn ($query) => $query->with(['opportunities' => fn ($query) => $open($query)
                ->with('translations')->orderByRaw('COALESCE(posted_at, first_seen_at, created_at) DESC')->orderByDesc('id')->limit($roles)]))
            ->orderByDesc('open_roles_count')->orderBy('name');
    }

    public static function card(Company $company, Request $request): array
    {
        $locale = $request->is('fr', 'fr/*') ? 'fr' : 'en';

        return [
            'id' => $company->id,
            'name' => $company->display_name,
            'logo' => $company->logo,
            'location' => $company->city?->name ?: $company->location,
            'url' => $company->publicUrl($locale),
            'open_roles_count' => $company->open_roles_count,
            'roles' => $company->relationLoaded('opportunities') ? $company->opportunities->map(fn ($opportunity) => [
                'id' => $opportunity->id,
                'title' => $opportunity->titleFor($locale),
                'kind' => $opportunity->kind,
                'url' => $opportunity->publicUrl($locale),
            ])->values() : [],
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
            ],
            'opportunities' => OpportunityResource::collection($opportunities),
            'seo' => PublicSeo::company($model, $opportunities->count()),
            'translation' => parse_url($model->publicUrl($locale === 'fr' ? 'en' : 'fr'), PHP_URL_PATH),
        ]);
    }
}

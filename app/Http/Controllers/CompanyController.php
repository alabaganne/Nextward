<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompanyRequest;
use App\Http\Resources\InternshipResource;
use App\Models\City;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Inertia\Inertia;
class CompanyController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Company::class);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(\Illuminate\Http\Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'hiring' => ['nullable', 'boolean'],
        ]);
        $search = trim($filters['search'] ?? '');

        return Inertia::render('Companies/Index', [
            'filters' => ['search' => $search, 'hiring' => (bool) ($filters['hiring'] ?? false)],
            'companies' => Company::whereNull('claimed_by_company_id')
				->withCount(['internships as open_roles_count' => fn ($query) => $query->available()])
				->with('city', 'user')
				->when($search !== '', function ($query) use ($search) {
					$term = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';
					$query->where(fn ($query) => $query->where('name', 'like', $term)
						->orWhereHas('user', fn ($query) => $query->where('name', 'like', $term))
						->orWhereHas('city', fn ($query) => $query->where('name', 'like', $term)));
				})
				->when($filters['hiring'] ?? false, fn ($query) => $query->whereHas('internships', fn ($query) => $query->available()))
				->orderByDesc('open_roles_count')
				->orderBy('name')
				->paginate(12)
				->withQueryString()
				->through(function ($company) {
					return [
						'id' => $company->id,
						'user_id' => $company->user?->id,
						'name' => $company->display_name,
						'website' => $company->website,
						'location' => $company->city?->name ?: $company->location,
						'open_roles_count' => $company->open_roles_count,
					];
				}),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return Inertia::render('Companies/Edit', [
            'cities' => City::all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CompanyRequest $request)
    {
        DB::transaction(function () use ($request) {
            Company::create($request->only('name', 'about', 'website', 'city_id', 'country_code', 'location'))->user()->create([
                ...$request->only('name', 'email', 'phone_number'),
                'password' => Hash::make(Str::random(64)),
            ]);
        });

        return Redirect::route('companies.index')->with('toast', [
            'type' => 'store',
            'message' => 'Company created successfully.'
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Company  $company
     * @return \Illuminate\Http\Response
     */
    public function show(Company $company)
    {
        if ($company->claimed_by_company_id) {
            return redirect()->route('companies.show', $company->claimed_by_company_id);
        }
        return Inertia::render('Companies/Show', [
            'company' => [
				'id' => $company->id,
				'user_id' => $company->user?->id,
				'name' => $company->display_name,
				'email' => $company->user?->email,
				'phone_number' => $company->user?->phone_number,
				'image' => $company->logo,
				'about' => $company->about,
                'country_code' => $company->country_code,
                'location' => $company->location,
				'website' => $company->website,
				'claimed_by_company_id' => $company->claimed_by_company_id,
				'city' => [
					'name' => $company->city?->name,
				],
				'internships' => InternshipResource::collection($company->internships()
                    ->listedFor(auth()->user())
                    ->with(['company.user', 'company.city', 'city', 'field', 'skills'])
                    ->withCandidateState(auth()->user())
                    ->latest()->orderByDesc('id')->paginate(10)->withQueryString())
			]
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Company  $company
     * @return \Illuminate\Http\Response
     */
    public function edit(Company $company)
    {
        return Inertia::render('Companies/Edit', [
            'company' => [
				'id' => $company->id,
				'name' => $company->display_name,
				'email' => $company->user?->email,
				'phone_number' => $company->user?->phone_number,
				'image' => $company->logo,
				'website' => $company->website,
				'about' => $company->about,
                'country_code' => $company->country_code,
                'location' => $company->location,
				'city_id' => $company->city_id,
			],
            'cities' => City::all(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Company  $company
     * @return \Illuminate\Http\Response
     */
    public function update(CompanyRequest $request, Company $company)
    {
        $company->update($request->only('name', 'about', 'website', 'city_id', 'country_code', 'location'));

        $company->user()->update($request->only('name', 'email', 'phone_number'));

        return Redirect::route('companies.show', $company)->with('toast', [
            'type' => 'update',
            'message' => 'Company updated successfully.'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Company  $company
     * @return \Illuminate\Http\Response
     */
    public function destroy(Company $company)
    {
        $company->user()->delete();
        $company->delete();

        return Redirect::route('companies.index')->with('toast', [
            'type' => 'destroy',
            'message' => 'Company deleted successfully.'
        ]);
    }
}

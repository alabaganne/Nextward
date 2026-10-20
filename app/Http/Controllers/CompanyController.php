<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompanyRequest;
use App\Http\Resources\OpportunityResource;
use App\Models\City;
use App\Models\Company;
use App\Notifications\AccountCreated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
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
            'companies' => PublicCompanyController::cards(Company::whereNull('claimed_by_company_id')
                ->when($search !== '', function ($query) use ($search) {
                    $term = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';
                    $query->where(fn ($query) => $query->where('name', 'like', $term)->orWhere('location', 'like', $term)
                        ->orWhereHas('user', fn ($query) => $query->where('name', 'like', $term))
                        ->orWhereHas('city', fn ($query) => $query->where('name', 'like', $term)));
                })
                ->when($filters['hiring'] ?? false, fn ($query) => $query->whereHas('opportunities', fn ($query) => $query->available())), null, 3)
                ->paginate(12)
                ->withQueryString()
                ->through(fn ($company) => [...PublicCompanyController::card($company, $request), 'href' => route('companies.show', $company->id, false)]),
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
        $request->validate(['logo' => 'nullable|image|max:2048']);

        $logo = $request->file('logo')?->store('company-logos', 'public');

        $user = DB::transaction(function () use ($request, $logo) {
            return Company::create($request->only('name', 'about', 'website', 'city_id', 'country_code', 'location'))->user()->create([
                ...$request->only('name', 'email', 'phone_number'),
                'image' => $logo,
                'password' => Hash::make(Str::random(64)),
            ]);
        });

        $user->notify(new AccountCreated);

        return Redirect::route('app.companies.index')->with('toast', [
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
				'opportunities' => OpportunityResource::collection($company->opportunities()
                    ->listedFor(auth()->user())
                    ->with(['company.user', 'company.city', 'city', 'field', 'skills', 'translations'])
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
				'user_id' => $company->user?->id,
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
        $request->validate(['logo' => 'nullable|image|max:2048']);

        $company->update($request->only('name', 'about', 'website', 'city_id', 'country_code', 'location'));

        $company->user()->update($request->only('name', 'email', 'phone_number'));

        if ($request->hasFile('logo') && $user = $company->user()->first()) {
            $old = $user->image;
            $user->update(['image' => $request->file('logo')->store('company-logos', 'public')]);
            $this->deleteLogo($old);
        }

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
        $logo = $company->user?->image;
        $company->user()->delete();
        $company->delete();
        $this->deleteLogo($logo);

        return Redirect::route('app.companies.index')->with('toast', [
            'type' => 'destroy',
            'message' => 'Company deleted successfully.'
        ]);
    }

    private function deleteLogo(?string $path): void
    {
        if ($path && str_starts_with($path, 'company-logos/') && ! str_contains($path, '..')) {
            Storage::disk('public')->delete($path);
        }
    }
}

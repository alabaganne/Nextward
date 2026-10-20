<?php

namespace App\Http\Controllers;

use App\Http\Requests\CandidateRequest;
use App\Models\Candidate;
use App\Models\Field;
use App\Models\City;
use App\Notifications\AccountCreated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CandidateController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Candidate::class);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $public = in_array($request->query('public'), ['pending', 'approved'], true) ? $request->query('public') : null;

        return Inertia::render('Candidates/Index', [
            'filter' => $public,
            'pending_count' => Candidate::where('public_status', 'pending')->count(),
            'candidates' => Candidate::with('field', 'user')
                    ->when($public, fn ($query) => $query->where('public_status', $public))
                    ->latest()
                    ->paginate(12)
                    ->withQueryString()
					->through(function ($candidate) {
						return [
							'id' => $candidate->id,
							'user_id' => $candidate->user->id,
							'name' => $candidate->user->name,
							'email' => $candidate->user->email,
							'image' => $candidate->user->image,
							'public_status' => $candidate->public_status,
							'public_url' => parse_url($candidate->publicUrl(), PHP_URL_PATH),
							'field' => [
								'name' => $candidate->field?->name
							]
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
        return Inertia::render('Candidates/Edit', [
            'fields' => Field::select('id', 'name')->get(),
            'cities' => City::all()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CandidateRequest $request)
    {
        $user = DB::transaction(function () use ($request) {
            return Candidate::create($request->only('about', 'field_id', 'city_id', 'headline', 'experience_years', 'country_code', 'location'))->user()->create([
                ...$request->only('name', 'email', 'phone_number'),
                'password' => Hash::make(Str::random(64)),
            ]);
        });

        $user->notify(new AccountCreated);

        return Redirect::route('candidates.index')->with('toast', [
            'type' => 'store',
            'message' => 'Candidate created successfully.'
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Student  $student
     * @return \Illuminate\Http\Response
     */
    public function show(Candidate $candidate)
    {
        return Inertia::render('Candidates/Show', [
            'candidate' => [
				'id' => $candidate->id,
				'about' => $candidate->about,
                'headline' => $candidate->headline,
                'experience_years' => $candidate->experience_years,
                'country_code' => $candidate->country_code,
                'location' => $candidate->location,
				'linkedin_profile_url' => $candidate->user->linkedin_profile_url,
				'user_id' => $candidate->user->id,
				'name' => $candidate->user->name,
				'email' => $candidate->user->email,
				'phone_number' => $candidate->user->phone_number,
				'image' => $candidate->user->image,
				'skills' => $candidate->skills->pluck('name'),
				'public_status' => $candidate->public_status,
				'public_url' => parse_url($candidate->publicUrl(), PHP_URL_PATH),
				'field' => [
					'name' => $candidate->field?->name,
				],
				'city' => [
					'name' => $candidate->city?->name
				]
			]
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Student  $student
     * @return \Illuminate\Http\Response
     */
    public function edit(Candidate $candidate)
    {
        return Inertia::render('Candidates/Edit', [
            'candidate' => [
				'id' => $candidate->id,
				'name' => $candidate->user->name,
				'email' => $candidate->user->email,
				'phone_number' => $candidate->user->phone_number,
				'about' => $candidate->about,
                'headline' => $candidate->headline,
                'experience_years' => $candidate->experience_years,
                'country_code' => $candidate->country_code,
                'location' => $candidate->location,
				'field_id' => $candidate->field_id,
				'city_id' => $candidate->city_id,
			],
            'fields' => Field::select('id', 'name')->get(),
            'cities' => City::all()
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Student  $student
     * @return \Illuminate\Http\Response
     */
    public function update(CandidateRequest $request, Candidate $candidate)
    {
        $candidate->update($request->only('about', 'field_id', 'city_id', 'headline', 'experience_years', 'country_code', 'location'));

        $candidate->user()->update($request->only('name', 'email', 'phone_number'));

        return Redirect::route('candidates.show', $candidate)->with('toast', [
            'type' => 'update',
            'message' => 'Candidate updated successfully.'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Student  $student
     * @return \Illuminate\Http\Response
     */
    public function destroy(Candidate $candidate)
    {
        // Rows go with the candidate; their resume files have to be deleted by hand.
        $files = $candidate->resumes()->whereNotNull('path')->pluck('path')->all();
        $candidate->user()->delete();
        $candidate->delete();
        \Illuminate\Support\Facades\Storage::disk('local')->delete($files);

        return Redirect::route('candidates.index')->with('toast', [
            'type' => 'destroy',
            'message' => 'Candidate deleted successfully.'
        ]);
    }
}

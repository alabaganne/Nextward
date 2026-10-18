<?php

namespace App\Http\Controllers;

use App\Http\Requests\StudentRequest;
use App\Models\Student;
use App\Models\Field;
use App\Models\City;
use App\Notifications\AccountCreated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Inertia\Inertia;

class StudentController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Student::class);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $public = in_array($request->query('public'), ['pending', 'approved'], true) ? $request->query('public') : null;

        return Inertia::render('Students/Index', [
            'filter' => $public,
            'pending_count' => Student::where('public_status', 'pending')->count(),
            'students' => Student::with('field', 'user')
                    ->when($public, fn ($query) => $query->where('public_status', $public))
                    ->latest()
                    ->paginate(12)
                    ->withQueryString()
					->through(function ($student) {
						return [
							'id' => $student->id,
							'user_id' => $student->user->id,
							'name' => $student->user->name,
							'career_stage' => $student->career_stage,
							'email' => $student->user->email,
							'image' => $student->user->image,
							'public_status' => $student->public_status,
							'public_url' => parse_url($student->publicUrl(), PHP_URL_PATH),
							'field' => [
								'name' => $student->field?->name
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
        return Inertia::render('Students/Edit', [
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
    public function store(StudentRequest $request)
    {
        $user = DB::transaction(function () use ($request) {
            return Student::create($request->only('about', 'field_id', 'city_id', 'career_stage', 'headline', 'experience_years', 'country_code', 'location'))->user()->create([
                ...$request->only('name', 'email', 'phone_number'),
                'password' => Hash::make(Str::random(64)),
            ]);
        });

        $user->notify(new AccountCreated);

        return Redirect::route('students.index')->with('toast', [
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
    public function show(Student $student)
    {
        return Inertia::render('Students/Show', [
            'student' => [
				'id' => $student->id,
				'about' => $student->about,
                'career_stage' => $student->career_stage,
                'headline' => $student->headline,
                'experience_years' => $student->experience_years,
                'country_code' => $student->country_code,
                'location' => $student->location,
				'linkedin_profile_url' => $student->user->linkedin_profile_url,
				'user_id' => $student->user->id,
				'name' => $student->user->name,
				'email' => $student->user->email,
				'phone_number' => $student->user->phone_number,
				'image' => $student->user->image,
				'skills' => $student->skills->pluck('name'),
				'public_status' => $student->public_status,
				'public_url' => parse_url($student->publicUrl(), PHP_URL_PATH),
				'field' => [
					'name' => $student->field?->name,
				],
				'city' => [
					'name' => $student->city?->name
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
    public function edit(Student $student)
    {
        return Inertia::render('Students/Edit', [
            'student' => [
				'id' => $student->id,
				'name' => $student->user->name,
				'email' => $student->user->email,
				'phone_number' => $student->user->phone_number,
				'about' => $student->about,
                'career_stage' => $student->career_stage,
                'headline' => $student->headline,
                'experience_years' => $student->experience_years,
                'country_code' => $student->country_code,
                'location' => $student->location,
				'field_id' => $student->field_id,
				'city_id' => $student->city_id,
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
    public function update(StudentRequest $request, Student $student)
    {
        $student->update($request->only('about', 'field_id', 'city_id', 'career_stage', 'headline', 'experience_years', 'country_code', 'location'));

        $student->user()->update($request->only('name', 'email', 'phone_number'));

        return Redirect::route('students.show', $student)->with('toast', [
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
    public function destroy(Student $student)
    {
        $student->user()->delete();
        $student->delete();

        return Redirect::route('students.index')->with('toast', [
            'type' => 'destroy',
            'message' => 'Candidate deleted successfully.'
        ]);
    }
}

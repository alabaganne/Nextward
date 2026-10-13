<?php

namespace App\Http\Controllers;

use App\Http\Requests\InternshipRequest; // validations
use App\Http\Resources\InternshipResource; // response
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Field;
use App\Models\City;
use App\Models\Company;
use App\Models\Internship;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;

class InternshipController extends Controller
{
    public function index(Request $request)
    {
		$user = $request->user();

        return Inertia::render('Internships/Index', [
			'filters' => $request->only('fields', 'cities', 'companies', 'search'),
			'internships' => InternshipResource::collection(
				Internship::with('company', 'field', 'city', 'skills')
					->listedFor($user)
					->withCount('applications')
					->withFilters(
						$request->input('fields', []),
						$request->input('companies', []),
						$request->input('cities', []),
						$request->input('search', '')
					)
					->latest()
					->paginate(7)
			),
			'fields' => Field::withCount(['internships' => function ($query) use ($request, $user) {
					$query->listedFor($user)->withFilters(
						// [$request->input('fields', [])],
						[],
						$request->input('companies', []),
						$request->input('cities', []),
						$request->input('search', '')
					);
				}])
				->get(),
			'cities' => City::withCount(['internships' => function ($query) use ($request, $user) {
					$query->listedFor($user)->withFilters(
						$request->input('fields', []),
						$request->input('companies', []),
						// $request->input('cities', []),
						[],
						$request->input('search', '')
					);
				}])
				->get(),
			'companies' => $user && $user->isCompany() ? [] : Company::withCount(['internships' => function ($query) use ($request, $user) {
					$query->listedFor($user)->withFilters(
						$request->input('fields', []),
						[],
						$request->input('cities', []),
						$request->input('search', '')
					);
				}])
				->get()
				->transform(function ($company) {
					return [
						'id' => $company->id,
						'name' => $company->user->name,
						'internships_count' => $company->internships_count,
					];
				})
		]);
    }

    public function getFields() {
        return Field::select('id', 'name')->get();
    }

    public function create()
    {
        $this->authorize('create', Internship::class);

        return Inertia::render('Internships/Edit', [
            'fields' => $this->getFields()
        ]);
    }

    public function store(InternshipRequest $request)
    {
        $this->authorize('create', Internship::class);

        $data = $request->validated();
		$company = auth()->user()->userable;
        $data['company_id'] = $company->id;
		$data['city_id'] = $company->city_id;

        $data['attachments'] = $this->storeAttachments($request, 'internships');

        Internship::create($data);

        return Redirect::route('internships.index')->with('toast', [
            'type' => 'store',
            'message' => 'A new internship has been added.'
        ]);
    }

    public function show(Internship $internship) {
		$user = auth()->user();
		$application = null;
		if($user && $user->isStudent()) {
			$application = \App\Models\Application::where('internship_id', $internship->id)
							->where('student_id', $user->userable->id)
							->first();
		}

        return Inertia::render('Internships/Show', [
            'internship' => [
				'id' => $internship->id,
				'title' => $internship->title,
				'description' => $internship->description,
				'closing_at' => $internship->closing_at->format('F d, Y'),
				'is_open' => $internship->isOpen(),
				'created_at' => $internship->created_at->diffForHumans(),
				'work_type' => $internship->work_type,
				'duration_weeks' => $internship->duration_weeks,
				'term' => $internship->term,
				'pay' => $internship->pay_amount ? ['amount' => $internship->pay_amount, 'unit' => $internship->pay_unit] : null,
				'field' => [
					'name' => $internship->field->name,
				],
				'city' => [
					'name' => $internship->city->name
				],
				'company' => [
					'id' => $internship->company->id,
					'user_id' => $internship->company->user->id,
					'image' => $internship->company->user->image,
					'name' => $internship->company->user->name,
					'email' => $internship->company->user->email,
					'phone_number' => $internship->company->user->phone_number,
					'website' => $internship->company->website,
					'linkedin_profile_url' => $internship->company->user->linkedin_profile_url,
					'city' => [
						'name' => $internship->company->city->name,
					]
				],
				'attachments' => $internship->attachments ?: [],
				'skills' => $internship->skills->pluck('name'),
				'liked' => $user && $user->isStudent() ? $user->userable->likes->contains($internship->id) : null,
				'application' => $application ? [
					'id' => $application->id,
					'student_id' => $application->student_id,
					'created_at' => $application->created_at->format('F d, Y')
				] : null
			]
        ]);
    }

    public function edit(Internship $internship)
    {
        $this->authorize('update', $internship);

        return Inertia::render('Internships/Edit', [
            'internship' => $internship,
            'fields' => $this->getFields()
        ]);
    }

    public function update(InternshipRequest $request, Internship $internship)
    {
        $this->authorize('update', $internship);

        $data = $request->validated();
        if ($request->hasFile('attachment_files')) {
            $data['attachments'] = $this->storeAttachments($request, 'internships');
        }

        $internship->update($data);

        return Redirect::route('internships.show', $internship->id)->with('toast', [
            'type' => 'update',
            'message' => 'Internship updated successfully.'
        ]);
    }


    public function destroy(Internship $internship)
    {
        $this->authorize('delete', $internship);

        $internship->delete();

        return Redirect::route('internships.index')->with('toast', [
            'type' => 'destroy',
            'message' => 'Internship deleted successully.'
        ]);
    }
    private function storeAttachments(Request $request, string $directory): array
    {
        return collect($request->file('attachment_files', []))->map(function ($file) use ($directory) {
            $path = $file->store($directory, 'public');
            return [
                'name' => $file->getClientOriginalName(),
                'url' => Storage::disk('public')->url($path),
                'size' => $file->getSize(),
                'mime' => $file->getClientMimeType(),
            ];
        })->values()->all();
    }
}

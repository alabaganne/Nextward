<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApplicationRequest;
use App\Http\Resources\ApplicationResource;
use App\Http\Resources\InternshipResource;
use App\Models\Application;
use App\Models\Internship;
use App\Notifications\ApplicationReviewed;
use App\Notifications\ApplicationSubmitted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ApplicationController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Application::class);
    }

    public function index() // TODO: only students are authorized to access this route
    {
        $query = Application::with('internship', 'internship.company', 'internship.city', 'internship.field');

        if (! Auth::user()->isAdmin()) {
            $field_name = Auth::user()->isStudent() ? 'student_id' : 'company_id';
            $query->where($field_name, Auth::user()->userable->id);
        }

        return Inertia::render('Applications/Index', [
            'applications' => ApplicationResource::collection(
                $query->latest()
                    ->paginate(8)
            )
        ]);
    }

    public function create(Internship $internship)
    {
        return Inertia::render('Applications/Edit', [
            'internship' => new InternshipResource($internship)
        ]);
    }

    public function store(Internship $internship, ApplicationRequest $request)
    {
        $application = Application::create([
            'student_id' => $request->user()->userable->id,
            'internship_id' => $internship->id,
            'cover_letter' => $request->cover_letter,
            'message' => $request->message,
            'attachments' => $request->hasFile('attachment_files')
                ? $this->storeAttachments($request, 'applications')
                : [],
            'company_id' => $internship->company_id
        ]);

   		$application->company->user->notify(new ApplicationSubmitted($application));

        return Redirect::route('applications.index')->with('toast', [
            'type' => 'store',
            'message' => 'Your application has been submitted.'
        ]);
    }

    public function show(Application $application)
    {
        return Inertia::render('Applications/Show', [
            'application' => [
				'id' => $application->id,
				'name' => $application->name,
				'cover_letter' => $application->cover_letter,
				'message' => $application->message,
				'attachments' => $application->attachments ?: [],
				'status' => $application->status,
				'created_at' => $application->created_at->format('F d, Y'),
				'student' => [
					'id' => $application->student->id,
					'user_id' => $application->student->user->id,
					'name' => $application->student->user->name,
					'email' => $application->student->user->email,
					'phone_number' => $application->student->user->phone_number,
					'image' => $application->student->user->image,
				],
				'internship' => [
					'id' => $application->internship->id,
					'title' => $application->internship->title,
					'field' => ['name' => $application->internship->field->name],
					'city' => ['name' => $application->internship->city->name],
					'closing_at' => $application->internship->closing_at->format('F d, Y'),
				],
				'company' => [
					'id' => $application->company->id,
					'user_id' => $application->company->user->id,
					'name' => $application->company->user->name,
					'email' => $application->company->user->email,
					'phone_number' => $application->company->user->phone_number,
					'city' => ['name' => optional($application->company->city)->name],
				],
				'updated_at' => $application->updated_at->diffForHumans(),
			]
        ]);
    }

    public function edit(Application $application)
    {
        return Inertia::render('Applications/Edit', [
            'internship' => new InternshipResource(
                Internship::findOrFail($application->internship_id)
            ),
            'application' => $application,
        ]);
    }

    public function update(Application $application, ApplicationRequest $request)
    {
        $data = $request->validated();
        if ($request->hasFile('attachment_files')) {
            $data['attachments'] = array_merge(
                $application->attachments ?: [],
                $this->storeAttachments($request, 'applications')
            );
        } else {
            unset($data['attachments']);
        }

        unset($data['attachment_files']);

        $application->update($data);

        return Redirect::route('applications.show', $application)->with('toast', [
            'type' => 'update',
            'message' => 'Application updated successfully.'
        ]);
    }

    public function destroy(Application $application) {
        $application->delete();

        return Redirect::route('applications.index')->with('toast', [
            'type' => 'destroy',
            'message' => 'Applications deleted successfully.'
        ]);
    }

    public function reply(Application $application, Request $request) {
        $this->authorize('reply', $application);

        $application->update(
            $request->validate([
                'status' => 'required|in:viewed,review,interview,offer,closed'
            ])
        );

        $application->student->user->notify(new ApplicationReviewed($application));

        return Redirect::back()->with('toast', [
            'type' => 'notification',
            'message' => 'Reply sent to the applicant.'
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

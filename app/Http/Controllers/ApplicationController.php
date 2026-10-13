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
use Illuminate\Support\Facades\DB;
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
            $field_name = Auth::user()->isCandidate() ? 'student_id' : 'company_id';
            $query->where($field_name, Auth::user()->userable->id);
            if (Auth::user()->isCompany()) {
                $query->where('channel', 'internal');
            }
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
        $application = DB::transaction(function () use ($request, $internship) {
            $request->user()->userable()->lockForUpdate()->first();
            abort_if(Application::where('student_id', $request->user()->userable_id)->where('internship_id', $internship->id)->exists(), 409, 'You already have an application for this opportunity.');
            return Application::create([
            'student_id' => $request->user()->userable->id,
            'internship_id' => $internship->id,
            'cover_letter' => $request->cover_letter,
            'message' => $request->message,
            'attachments' => $request->hasFile('attachment_files')
                ? $this->storeAttachments($request, 'applications')
                : [],
            'company_id' => $internship->company_id,
            'channel' => 'internal',
            'applied_at' => today(),
            ]);
        });

        $application->company->user?->notify(new ApplicationSubmitted($application));

        return Redirect::route('applications.index')->with('toast', [
            'type' => 'store',
            'message' => 'Your application has been submitted.'
        ]);
    }

    public function show(Application $application)
    {
        return Inertia::render('Applications/Show', [
            'application' => (new ApplicationResource($application))->resolve(),
        ]);
    }

    public function edit(Application $application)
    {
        return Inertia::render('Applications/Edit', [
            'internship' => new InternshipResource(
                Internship::findOrFail($application->internship_id)
            ),
            'application' => (new ApplicationResource($application))->resolve(),
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
            $path = $file->store($directory, 'local');
            if (!$path) {
                throw new \RuntimeException('The application attachment could not be saved. Please retry.');
            }
            return [
                'name' => $file->getClientOriginalName(),
                'path' => $path,
                'disk' => 'local',
                'size' => $file->getSize(),
                'mime' => $file->getMimeType(),
            ];
        })->values()->all();
    }

    public function track(Internship $internship, Request $request)
    {
        abort_unless($request->user()->isCandidate() && $internship->application_mode === 'external' && $internship->isOpen(), 403);
        $data = $request->validate([
            'applied_at' => 'nullable|date|before_or_equal:today',
            'candidate_notes' => 'nullable|string|max:10000',
        ]);

        $application = DB::transaction(function () use ($request, $internship, $data) {
            $request->user()->userable()->lockForUpdate()->first();
            return Application::firstOrCreate([
                'student_id' => $request->user()->userable_id,
                'internship_id' => $internship->id,
            ], [
                'company_id' => $internship->company_id,
                'channel' => 'external',
                'status' => 'submitted',
                'applied_at' => $data['applied_at'] ?? today(),
                'candidate_notes' => $data['candidate_notes'] ?? null,
            ]);
        });

        return redirect()->route('applications.show', $application)->with('toast', [
            'type' => 'store', 'message' => 'Saved to your tracker. This does not submit an application to the employer.',
        ]);
    }

    public function updateTracking(Application $application, Request $request)
    {
        abort_unless($request->user()->isCandidate() && $request->user()->userable_id === $application->student_id && $application->channel === 'external', 403);
        $application->update($request->validate([
            'status' => 'required|in:submitted,review,interview,offer,closed',
            'applied_at' => 'required|date|before_or_equal:today',
            'candidate_notes' => 'nullable|string|max:10000',
        ]));

        return back()->with('toast', ['type' => 'update', 'message' => 'Your private tracker was updated.']);
    }

    public function downloadAttachment(Application $application, int $attachment, Request $request)
    {
        $user = $request->user();
        abort_unless(($user->isCandidate() && $user->userable_id === $application->student_id)
            || ($application->channel === 'internal' && $user->isCompany() && $user->userable_id === $application->company_id), 403);
        $file = ($application->attachments ?: [])[$attachment] ?? null;
        $storage = $file ? Application::attachmentStorage($file) : null;
        abort_unless($storage && Storage::disk($storage['disk'])->exists($storage['path']), 404);

        return Storage::disk($storage['disk'])->download($storage['path'], basename($file['name'] ?? 'attachment'), [
            'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}

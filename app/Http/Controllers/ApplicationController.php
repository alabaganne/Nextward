<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApplicationRequest;
use App\Http\Resources\ApplicationResource;
use App\Http\Resources\OpportunityResource;
use App\Models\Application;
use App\Models\ApplicationKit;
use App\Models\Candidate;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Resume;
use App\Models\User;
use App\Notifications\ApplicationReviewed;
use App\Notifications\ApplicationSubmitted;
use App\Support\CvPdf;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ApplicationController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Application::class);
    }

    public function index(Request $request) // TODO: only students are authorized to access this route
    {
        $query = Application::with('opportunity', 'opportunity.company', 'opportunity.city', 'opportunity.field');

        // Admins with their own candidate profile see their own applications.
        if (! Auth::user()->isAdmin() || Auth::user()->userable_id) {
            $field_name = Auth::user()->isCandidate() ? 'candidate_id' : 'company_id';
            $query->where($field_name, Auth::user()->userable->id);
            if (Auth::user()->isCompany()) {
                $query->where('channel', 'internal');
            }
        }

        // Status filters; "review" is every application not in one of these.
        $buckets = ['interview' => ['interview'], 'offer' => ['offer', 'accepted'], 'closed' => ['closed', 'rejected']];
        $byStatus = (clone $query)->toBase()->select('status')->selectRaw('count(*) as total')->groupBy('status')->pluck('total', 'status');
        $counts = ['all' => (int) $byStatus->sum()];
        foreach ($buckets as $bucket => $statuses) {
            $counts[$bucket] = (int) $byStatus->only($statuses)->sum();
        }
        $counts['review'] = $counts['all'] - $counts['interview'] - $counts['offer'] - $counts['closed'];

        $status = $request->query('status');
        $status = is_string($status) && array_key_exists($status, $counts) ? $status : 'all';
        if ($status === 'review') {
            $query->where(fn ($query) => $query->whereNotIn('status', array_merge(...array_values($buckets)))->orWhereNull('status'));
        } elseif ($status !== 'all') {
            $query->whereIn('status', $buckets[$status]);
        }

        return Inertia::render('Applications/Index', [
            'applications' => ApplicationResource::collection(
                $query->latest()
                    ->paginate(8)
                    ->withQueryString()
            ),
            'counts' => $counts,
            'status' => $status,
        ]);
    }

    public function create(Opportunity $opportunity, Request $request)
    {
        return Inertia::render('Applications/Edit', [
            'opportunity' => new OpportunityResource($opportunity),
            ...$this->resumeChoices($request->user()->ensureCandidateProfile(), $opportunity->id),
        ]);
    }

    public function store(Opportunity $opportunity, ApplicationRequest $request)
    {
        $request->user()->ensureCandidateProfile() ?? abort(403);
        $application = DB::transaction(function () use ($request, $opportunity) {
            $request->user()->userable()->lockForUpdate()->first();
            abort_if(Application::where('candidate_id', $request->user()->userable_id)->where('opportunity_id', $opportunity->id)->exists(), 409, 'You already have an application for this role.');
            return Application::create([
            'candidate_id' => $request->user()->userable->id,
            'opportunity_id' => $opportunity->id,
            'cover_letter' => $request->cover_letter,
            'message' => $request->message,
            'attachments' => [
                ...array_filter([$this->resumeAttachment($request)]),
                ...($request->hasFile('attachment_files') ? $this->storeAttachments($request, 'applications') : []),
            ],
            'company_id' => $opportunity->company_id,
            'channel' => 'internal',
            'applied_at' => today(),
            ]);
        });

        $this->notifySafely($application->company->user, new ApplicationSubmitted($application));
        rescue(fn () => Lead::fromApplication($application));

        return Redirect::route('applications.index')->with('toast', [
            'type' => 'store',
            'message' => 'Your application has been submitted.'
        ]);
    }

    public function show(Application $application)
    {
        $user = Auth::user();
        if ($user->isCompany() && $user->userable_id === $application->company_id
            && $application->channel === 'internal' && $application->status === 'submitted') {
            $application->update(['status' => 'viewed']);
        }

        return Inertia::render('Applications/Show', [
            'application' => (new ApplicationResource($application))->resolve(),
        ]);
    }

    public function edit(Application $application)
    {
        return Inertia::render('Applications/Edit', [
            'opportunity' => new OpportunityResource(
                Opportunity::findOrFail($application->opportunity_id)
            ),
            'application' => (new ApplicationResource($application))->resolve(),
            ...$this->resumeChoices($application->candidate, $application->opportunity_id),
        ]);
    }

    public function update(Application $application, ApplicationRequest $request)
    {
        $attachments = collect($application->attachments ?: []);
        $resume = $this->resumeAttachment($request, $application->candidate_id);
        if ($resume || $request->boolean('remove_resume')) {
            [$old, $attachments] = $attachments->partition(fn ($file) => $file['resume'] ?? false);
            Application::deleteFiles($old->all());
        }
        $attachments = [
            ...array_filter([$resume]),
            ...$attachments->values()->all(),
            ...($request->hasFile('attachment_files') ? $this->storeAttachments($request, 'applications') : []),
        ];

        $application->update([...$request->safe()->only('cover_letter', 'message'), 'attachments' => $attachments]);

        return Redirect::route('applications.show', $application)->with('toast', [
            'type' => 'update',
            'message' => 'Application updated successfully.'
        ]);
    }

    public function destroy(Application $application) {
        $files = $application->attachments ?: [];
        $application->delete();
        Application::deleteFiles($files);

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

        $this->notifySafely($application->candidate->user, new ApplicationReviewed($application));

        return Redirect::back()->with('toast', [
            'type' => 'notification',
            'message' => 'Reply sent to the applicant.'
        ]);
    }
    // The application is already saved, so a mail or broadcast error must not fail the request.
    private function notifySafely(?User $user, Notification $notification): void
    {
        try {
            $user?->notify($notification);
        } catch (\Throwable $exception) {
            Log::warning('Application notification failed; the application was still saved.', [
                'user_id' => $user?->id,
                'notification' => $notification::class,
                'error' => $exception->getMessage(),
            ]);
        }
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

    // Ready resumes and this listing's tailored CVs the candidate can send.
    private function resumeChoices(?Candidate $candidate, int $opportunityId): array
    {
        if (! $candidate instanceof Candidate) {
            return ['resumes' => [], 'kits' => []];
        }

        return [
            'resumes' => $candidate->resumes()->where('status', 'ready')->whereNotNull('path')->orderByDesc('is_main')->latest('updated_at')->get()
                ->map(fn (Resume $resume) => [...$resume->only('id', 'name', 'file_name', 'is_main', 'source'), 'language' => $resume->summary()['language']['label'] ?? null])->all(),
            'kits' => ApplicationKit::where('candidate_id', $candidate->id)->where('opportunity_id', $opportunityId)->whereNotNull('cv')->latest('updated_at')->get()
                ->map(fn (ApplicationKit $kit) => ['id' => $kit->id, 'language' => \App\Support\LanguageGuess::name($kit->locale) ?? strtoupper($kit->locale), 'updated_label' => $kit->updated_at?->diffForHumans()])->all(),
        ];
    }

    // Copies the chosen resume, or prints the chosen tailored CV, so the company keeps it even if the candidate deletes theirs.
    private function resumeAttachment(Request $request, ?int $candidateId = null): ?array
    {
        $candidateId ??= $request->user()->userable_id;
        $path = 'applications/'.Str::random(40).'.pdf';
        if ($request->filled('resume_id')) {
            $resume = Resume::where('candidate_id', $candidateId)->findOrFail($request->integer('resume_id'));
            abort_unless($resume->hasFile() && Storage::disk('local')->copy($resume->path, $path), 409, 'This resume file is missing. Upload it again.');
            $name = $resume->file_name ?: 'resume.pdf';
        } elseif ($request->filled('kit_id')) {
            $kit = ApplicationKit::where('candidate_id', $candidateId)->with('opportunity.company')->findOrFail($request->integer('kit_id'));
            Storage::disk('local')->put($path, app(CvPdf::class)->pdf($kit->cv, $kit->style, $kit->locale));
            $name = $kit->fileName('cv');
        } else {
            return null;
        }

        return ['name' => $name, 'path' => $path, 'disk' => 'local', 'size' => Storage::disk('local')->size($path), 'mime' => 'application/pdf', 'resume' => true];
    }

    public function track(Opportunity $opportunity, Request $request)
    {
        abort_unless($opportunity->application_mode === 'external' && $opportunity->isOpen() && $request->user()->ensureCandidateProfile(), 403);
        $data = $request->validate([
            'applied_at' => 'nullable|date|before_or_equal:today',
            'candidate_notes' => 'nullable|string|max:10000',
        ]);

        $application = DB::transaction(function () use ($request, $opportunity, $data) {
            $request->user()->userable()->lockForUpdate()->first();
            return Application::firstOrCreate([
                'candidate_id' => $request->user()->userable_id,
                'opportunity_id' => $opportunity->id,
            ], [
                'company_id' => $opportunity->company_id,
                'channel' => 'external',
                'status' => 'submitted',
                'applied_at' => $data['applied_at'] ?? today(),
                'candidate_notes' => $data['candidate_notes'] ?? null,
            ]);
        });

        rescue(fn () => Lead::fromApplication($application));

        return back()->with('toast', [
            'type' => 'store', 'message' => 'Marked as applied and saved to your tracker. This does not submit an application to the employer.',
        ]);
    }

    public function updateTracking(Application $application, Request $request)
    {
        abort_unless($request->user()->isCandidate() && $request->user()->userable_id === $application->candidate_id && $application->channel === 'external', 403);
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
        abort_unless(($user->isCandidate() && $user->userable_id === $application->candidate_id)
            || ($application->channel === 'internal' && $user->isCompany() && $user->userable_id === $application->company_id), 403);
        $file = ($application->attachments ?: [])[$attachment] ?? null;
        $storage = $file ? Application::attachmentStorage($file) : null;
        abort_unless($storage && Storage::disk($storage['disk'])->exists($storage['path']), 404);

        return Storage::disk($storage['disk'])->download($storage['path'], basename($file['name'] ?? 'attachment'), [
            'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}

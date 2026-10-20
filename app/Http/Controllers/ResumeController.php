<?php

namespace App\Http\Controllers;

use App\Ai\Resumes;
use App\Billing\Credits;
use App\Models\Candidate;
use App\Models\Resume;
use App\Models\User;
use App\Support\Cv;
use App\Support\CvTemplates;
use App\Support\LanguageGuess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

// A candidate keeps up to 10 resumes: uploaded PDFs, exports of their profile and AI translations. The main one is shared,
// read to fill the profile and given to the AI for kits.
class ResumeController extends Controller
{
    // Reads the main resume with AI (one credit) and returns suggestions; the candidate reviews them and saves from Settings.
    public function read(Request $request, Resumes $resumes, Credits $credits)
    {
        $resume = $this->candidate($request)->mainResume;
        abort_unless($resume?->hasFile(), 404, 'Upload your resume first.');
        abort_unless($resumes->enabled(), 503, 'Reading resumes is not available right now.');
        if (($charge = $credits->spend($request->user(), 'resume_read', $resume)) === false) {
            return self::noCredits();
        }

        try {
            $suggestions = $resumes->read($resume);
            if (! $resume->locale && $suggestions['language']) {
                $resume->update(['locale' => $suggestions['language']]);
            }
            // A phone number on another account can't be saved, so don't suggest it.
            if ($suggestions['phone_number'] !== '' && User::where('phone_number', $suggestions['phone_number'])->whereKeyNot($request->user()->id)->exists()) {
                $suggestions['phone_number'] = '';
            }
            return response()->json(['suggestions' => $suggestions]);
        } catch (\Throwable $e) {
            $credits->refundSpend($charge);
            Log::warning('Resume reading failed.', ['resume_id' => $resume->id, 'error' => Str::limit($e->getMessage(), 300)]);
            return response()->json(['message' => 'We could not read your resume right now. Please try again in a minute. Your credit was given back.'], 502);
        }
    }

    // Downloads the main resume.
    public function main(Request $request)
    {
        $resume = $this->candidate($request)->mainResume;
        abort_unless($resume?->hasFile(), 404);

        return Storage::disk('local')->download($resume->path, $resume->file_name ?: 'resume.pdf');
    }

    // A new resume laid out from the profile, shown in the editor before anything is saved.
    public function create(Request $request)
    {
        $candidate = $this->candidate($request);
        $main = $candidate->mainResume;

        return Inertia::render('Resumes/Show', [
            'resume' => null,
            'draft' => [
                'name' => 'Resume from my profile',
                'locale' => $main?->locale && in_array($main->locale, CvTemplates::LOCALES, true) ? $main->locale : 'en',
                'style' => CvTemplates::style($candidate->resumes()->whereNotNull('style')->latest('updated_at')->value('style')),
                'content' => Cv::fromProfile($candidate),
            ],
            'main' => $main?->only('id', 'name'),
            'full' => $candidate->resumes()->count() >= Resume::LIMIT,
            ...self::editorProps(),
        ]);
    }

    public function store(Request $request)
    {
        $candidate = $this->candidate($request);
        $request->validate(['resume' => 'required|file|mimes:pdf|max:5120']);
        $this->checkLimit($candidate);

        $file = $request->file('resume');
        $path = $file->store('resumes', 'local');
        DB::transaction(function () use ($candidate, $file, $path) {
            Candidate::whereKey($candidate->id)->lockForUpdate()->first();
            Resume::create([
                'candidate_id' => $candidate->id,
                'name' => Str::limit(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) ?: 'Resume', 150, ''),
                'source' => 'uploaded',
                'status' => 'ready',
                'path' => $path,
                'file_name' => mb_substr($file->getClientOriginalName(), 0, 200),
                'is_main' => ! $candidate->resumes()->where('is_main', true)->exists(),
            ]);
        });

        return back()->with('toast', ['type' => 'update', 'message' => 'Resume saved.']);
    }

    // Saves a resume made from the profile (free, no AI) and makes it the main one.
    public function fromProfile(Request $request)
    {
        $candidate = $this->candidate($request);
        $data = $request->validate([
            'name' => 'nullable|string|max:150',
            'locale' => 'required|in:'.implode(',', CvTemplates::LOCALES),
            'content' => 'sometimes|array',
            'replace' => 'boolean',
            ...CvTemplates::rules(),
        ]);
        $main = $candidate->mainResume;
        $replace = $request->boolean('replace') && $main;
        if (! $replace) {
            $this->checkLimit($candidate);
        }

        $resume = Resume::create([
            'candidate_id' => $candidate->id,
            'name' => trim($data['name'] ?? '') ?: 'Resume from my profile',
            'locale' => $data['locale'],
            'source' => 'profile',
            'status' => 'queued',
            'style' => CvTemplates::style($data['style'] ?? null),
            'content' => Cv::clean($request->has('content') ? $request->input('content') : Cv::fromProfile($candidate)),
        ]);
        $resume->render();
        $resume->makeMain();
        if ($replace) {
            $main->refresh()->remove();
        }

        return redirect()->route('resumes.show', $resume)->with('toast', ['type' => 'update', 'message' => $replace ? 'Your new resume replaced the old one.' : 'Your new resume is saved and is now your main resume.']);
    }

    public function show(Request $request, Resume $resume)
    {
        $this->authorizeResume($request, $resume);
        if ($request->wantsJson()) {
            return response()->json(['resume' => $this->detail($resume)]);
        }

        return Inertia::render('Resumes/Show', ['resume' => $this->detail($resume), 'draft' => null, 'main' => null, 'full' => false, ...self::editorProps()]);
    }

    public function update(Request $request, Resume $resume)
    {
        $this->authorizeResume($request, $resume);
        abort_if($resume->status === 'queued', 409, 'This resume is still being written.');
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:150',
            'content' => 'sometimes|array',
            'locale' => 'sometimes|in:'.implode(',', CvTemplates::LOCALES),
            'main' => 'sometimes|boolean',
            ...CvTemplates::rules(),
        ]);

        $changes = array_filter(['name' => isset($data['name']) ? trim($data['name']) : null]);
        if ($resume->isEditable()) {
            if ($request->has('content')) { $changes['content'] = Cv::clean($request->input('content', [])); }
            if (isset($data['style'])) { $changes['style'] = CvTemplates::style([...($resume->style ?? []), ...$data['style']]); }
            // A translation keeps its language; a profile export can change its section names.
            if (isset($data['locale']) && $resume->source === 'profile') { $changes['locale'] = $data['locale']; }
        }
        $resume->fill($changes);
        $layout = $resume->isDirty(['content', 'style', 'locale']) || ($resume->isEditable() && $resume->status !== 'ready');
        $resume->save();
        if ($layout) {
            $resume->render();
        }
        if ($request->boolean('main') || ! $resume->candidate->resumes()->where('is_main', true)->exists()) {
            $resume->status === 'ready' && $resume->makeMain();
        }

        if ($request->wantsJson()) {
            return response()->json(['resume' => $this->detail($resume->fresh())]);
        }

        return back()->with('toast', ['type' => 'update', 'message' => 'Resume saved.']);
    }

    public function makeMain(Request $request, Resume $resume)
    {
        $this->authorizeResume($request, $resume);
        abort_unless($resume->status === 'ready' && $resume->path, 409, 'Save this resume first.');
        $resume->makeMain();

        return back()->with('toast', ['type' => 'update', 'message' => "“{$resume->name}” is now your main resume."]);
    }

    // Lays the resume out again from the profile as it is now.
    public function refresh(Request $request, Resume $resume)
    {
        $this->authorizeResume($request, $resume);
        abort_unless($resume->source === 'profile', 404);
        $resume->update(['content' => Cv::fromProfile($resume->candidate)]);
        $resume->render();

        return back()->with('toast', ['type' => 'update', 'message' => 'Your resume now matches your profile.']);
    }

    public function download(Request $request, Resume $resume)
    {
        $this->authorizeResume($request, $resume);
        abort_unless($resume->hasFile(), 404);

        return Storage::disk('local')->download($resume->path, $resume->file_name ?: 'resume.pdf');
    }

    public function destroy(Request $request, Resume $resume)
    {
        $this->authorizeResume($request, $resume);
        $resume->remove();

        return redirect()->to(route('profile.show').'#resumes')->with('toast', ['type' => 'destroy', 'message' => 'Resume deleted.']);
    }

    // The live preview and theme thumbnails: the same HTML the PDF is printed from.
    public function preview(Request $request, \App\Support\CvPdf $pdf)
    {
        $data = $request->validate([
            'cv' => 'present|array',
            'locale' => 'nullable|string|max:5',
            'letter' => 'nullable|string|max:20000',
            ...CvTemplates::rules(),
        ]);
        $html = isset($data['letter'])
            ? $pdf->letterHtml($data['cv'], $data['letter'], $data['style'] ?? null, $data['locale'] ?? 'en')
            : $pdf->html($data['cv'], $data['style'] ?? null, $data['locale'] ?? 'en');

        return response($html)->header('Content-Type', 'text/html; charset=UTF-8')->header('X-Robots-Tag', 'noindex');
    }

    public static function editorProps(): array
    {
        return [
            'themes' => CvTemplates::options(),
            'locales' => collect(CvTemplates::LOCALES)->map(fn ($code) => ['code' => $code, 'label' => LanguageGuess::name($code), 'labels' => CvTemplates::labels($code)])->all(),
            'ai_enabled' => app(Resumes::class)->enabled(),
        ];
    }

    public static function noCredits()
    {
        return response()->json(['message' => 'You have no credits left. Buy a pack to keep using the AI tools.', 'billing_url' => route('billing.index')], 402);
    }

    private function detail(Resume $resume): array
    {
        $credits = app(Credits::class);

        return [
            ...$resume->summary(),
            'content' => $resume->isEditable() ? Cv::clean($resume->content ?? []) : null,
            'labels' => CvTemplates::labels($resume->locale),
            'rewrites_left' => $resume->source === 'translation' && $credits->enabled() ? max(0, $resume->credits_used * $credits->draftsPerCredit() - $resume->generations) : null,
        ];
    }

    private function checkLimit(Candidate $candidate): void
    {
        if ($candidate->resumes()->count() >= Resume::LIMIT) {
            throw ValidationException::withMessages(['resume' => 'You can keep up to '.Resume::LIMIT.' resumes. Delete one first.']);
        }
    }

    private function authorizeResume(Request $request, Resume $resume): void
    {
        abort_unless($request->user()->isCandidate() && $resume->candidate_id === $request->user()->userable_id, 404);
    }

    private function candidate(Request $request): Candidate
    {
        return $request->user()->ensureCandidateProfile() ?? abort(403);
    }
}

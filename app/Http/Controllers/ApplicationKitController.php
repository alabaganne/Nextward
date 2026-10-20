<?php

namespace App\Http\Controllers;

use App\Ai\ApplicationKits;
use App\Billing\Credits;
use App\Jobs\GenerateApplicationKit;
use App\Models\ApplicationKit;
use App\Models\Opportunity;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

// Tailored CVs and cover letters: one kit per candidate, role and language.
class ApplicationKitController extends Controller
{
    public function index(Request $request)
    {
        $candidate = $request->user()->ensureCandidateProfile() ?? abort(403);
        $kits = ApplicationKit::where('candidate_id', $candidate->id)->with('opportunity.company.user', 'opportunity.translations')->latest('updated_at')->get();

        return Inertia::render('Kits/Index', ['kits' => $kits->map(fn ($kit) => $this->summary($kit))]);
    }

    public function store(Request $request, Opportunity $opportunity, ApplicationKits $writer, Credits $credits)
    {
        $user = $request->user();
        $candidate = $user->ensureCandidateProfile() ?? abort(403);
        abort_unless($opportunity->publication_status === 'published' && $opportunity->isOpen(), 404);
        $data = $request->validate(['locale' => 'required|in:en,fr']);
        abort_unless($writer->enabled(), 503, 'Tailored CVs and letters are not available right now.');

        $kit = ApplicationKit::firstOrCreate(['candidate_id' => $candidate->id, 'opportunity_id' => $opportunity->id, 'locale' => $data['locale']]);
        if ($kit->wasRecentlyCreated || $kit->status === 'failed') {
            if ($response = $this->start($user, $kit, $credits)) { return $response; }
        }

        return response()->json(['kit' => $this->summary($kit->fresh('opportunity')), 'url' => route('kits.show', $kit)]);
    }

    public function show(Request $request, ApplicationKit $kit)
    {
        $this->authorizeKit($request, $kit);
        $kit->load('opportunity.company.user', 'opportunity.translations');
        if ($request->wantsJson()) {
            return response()->json(['kit' => $this->detail($kit)]);
        }

        return Inertia::render('Kits/Show', ['kit' => $this->detail($kit)]);
    }

    public function update(Request $request, ApplicationKit $kit)
    {
        $this->authorizeKit($request, $kit);
        abort_if($kit->status === 'queued', 409, 'Your CV and letter are still being written.');
        $data = $request->validate([
            'cover_letter' => 'nullable|string|max:20000',
            'cv' => 'sometimes|array',
            'cv.name' => 'nullable|string|max:150',
            'cv.contact' => 'nullable|array|max:8',
            'cv.contact.*' => 'nullable|string|max:255',
        ]);
        // The listing page saves the letter alone, without the CV.
        $kit->update([
            'cover_letter' => trim((string) ($data['cover_letter'] ?? '')),
            ...(isset($data['cv']) ? ['cv' => ['name' => trim((string) ($data['cv']['name'] ?? '')), 'contact' => array_values(array_filter(array_map('trim', $data['cv']['contact'] ?? []))), ...ApplicationKits::cleanCv($request->input('cv', []))]] : []),
        ]);
        if ($request->wantsJson()) {
            return response()->json(['saved' => true]);
        }

        return back()->with('toast', ['type' => 'update', 'message' => 'Your CV and letter are saved.']);
    }

    public function regenerate(Request $request, ApplicationKit $kit, ApplicationKits $writer, Credits $credits)
    {
        $this->authorizeKit($request, $kit);
        abort_unless($writer->enabled(), 503, 'Tailored CVs and letters are not available right now.');
        if ($kit->status !== 'queued' && ($response = $this->start($request->user(), $kit, $credits))) {
            return $response;
        }

        return response()->json(['kit' => $this->summary($kit->fresh('opportunity')), 'url' => route('kits.show', $kit)]);
    }

    public function destroy(Request $request, ApplicationKit $kit)
    {
        $this->authorizeKit($request, $kit);
        $kit->delete();

        return redirect()->route('kits.index')->with('toast', ['type' => 'destroy', 'message' => 'The CV and letter were deleted.']);
    }

    public function cv(Request $request, ApplicationKit $kit)
    {
        $this->authorizeKit($request, $kit);
        abort_unless($kit->cv, 404);

        return Pdf::loadView('pdf.cv', ['cv' => $kit->cv, 'locale' => $kit->locale])->download($this->fileName($kit, 'CV'));
    }

    public function letter(Request $request, ApplicationKit $kit)
    {
        $this->authorizeKit($request, $kit);
        abort_unless($kit->cover_letter, 404);

        return Pdf::loadView('pdf.letter', ['kit' => $kit, 'name' => $kit->cv['name'] ?? '', 'contact' => $kit->cv['contact'] ?? []])
            ->download($this->fileName($kit, $kit->locale === 'fr' ? 'Lettre' : 'Cover letter'));
    }

    // Takes a credit when needed and queues the draft. Returns an error response when the user has no credit.
    private function start($user, ApplicationKit $kit, Credits $credits)
    {
        if (! $credits->chargeFor($user, $kit)) {
            return response()->json(['message' => 'You have no credits left. Buy a pack to tailor more CVs and letters.', 'billing_url' => route('billing.index')], 402);
        }
        $kit->update(['status' => 'queued', 'error' => null, 'generations' => $kit->generations + 1]);
        GenerateApplicationKit::dispatch($kit->id);

        return null;
    }

    private function authorizeKit(Request $request, ApplicationKit $kit): void
    {
        abort_unless($request->user()->isCandidate() && $kit->candidate_id === $request->user()->userable_id, 404);
    }

    private function fileName(ApplicationKit $kit, string $kind): string
    {
        return Str::slug(($kit->cv['name'] ?? 'nextward').' '.$kind.' '.$kit->opportunity?->company?->display_name).'.pdf';
    }

    private function summary(ApplicationKit $kit): array
    {
        $role = $kit->opportunity;
        return [
            'id' => $kit->id,
            'locale' => $kit->locale,
            'status' => $kit->status,
            'error' => $kit->error,
            'updated_at' => $kit->updated_at?->toIso8601String(),
            'updated_label' => $kit->updated_at?->diffForHumans(),
            'role' => $role ? [
                'id' => $role->id,
                'title' => $role->titleFor($kit->locale),
                'company' => $role->company?->display_name,
                'url' => $role->publicUrl($kit->locale),
                'is_open' => $role->isOpen(),
                'application_mode' => $role->application_mode,
                'application_url' => $role->application_url,
            ] : null,
        ];
    }

    private function detail(ApplicationKit $kit): array
    {
        $credits = app(Credits::class);
        return [
            ...$this->summary($kit),
            'cv' => $kit->cv,
            'cover_letter' => $kit->cover_letter,
            'rewrites_left' => $credits->enabled() ? max(0, $kit->credits_used * $credits->draftsPerCredit() - $kit->generations) : null,
        ];
    }
}

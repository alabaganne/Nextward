<?php

namespace App\Http\Controllers;

use App\Ai\Resumes;
use App\Billing\Credits;
use App\Jobs\TranslateResume;
use App\Models\Resume;
use App\Support\LanguageGuess;
use Illuminate\Http\Request;

// AI translations of a resume (English <-> French). One credit covers the first draft and a few rewrites; the candidate checks the draft before saving.
class ResumeTranslationController extends Controller
{
    public function store(Request $request, Resume $resume, Resumes $ai, Credits $credits)
    {
        $this->authorizeResume($request, $resume);
        abort_unless($resume->status === 'ready' && $resume->path, 409, 'Save this resume before translating it.');
        $data = $request->validate(['locale' => 'required|in:en,fr']);
        abort_if($data['locale'] === $resume->locale, 422, 'This resume is already in that language.');
        abort_unless($ai->enabled(), 503, 'Translating resumes is not available right now.');

        $draft = Resume::where('translated_from_id', $resume->id)->where('locale', $data['locale'])->first();
        if ($draft && $draft->status !== 'failed') {
            return $this->found($draft);
        }
        if (! $draft && Resume::where('candidate_id', $resume->candidate_id)->count() >= Resume::LIMIT) {
            return response()->json(['message' => 'You can keep up to '.Resume::LIMIT.' resumes. Delete one first.'], 422);
        }

        $draft ??= Resume::create([
            'candidate_id' => $resume->candidate_id,
            'translated_from_id' => $resume->id,
            'name' => mb_substr($resume->name.' ('.LanguageGuess::name($data['locale']).')', 0, 150),
            'locale' => $data['locale'],
            'source' => 'translation',
            'status' => 'queued',
            'style' => $resume->style,
        ]);
        if ($response = $this->start($request->user(), $draft, $credits)) {
            return $response;
        }

        return $this->found($draft);
    }

    // Writes a draft or a failed translation again. Saved translations are edited by hand.
    public function regenerate(Request $request, Resume $resume, Resumes $ai, Credits $credits)
    {
        $this->authorizeResume($request, $resume);
        abort_unless($resume->source === 'translation' && in_array($resume->status, ['draft', 'failed'], true), 409, 'Only drafts can be written again.');
        abort_unless($resume->translatedFrom?->path, 409, 'The resume it was translated from was deleted.');
        abort_unless($ai->enabled(), 503, 'Translating resumes is not available right now.');
        if ($response = $this->start($request->user(), $resume, $credits)) {
            return $response;
        }

        return $this->found($resume);
    }

    // Takes a credit when needed and queues the draft. A new draft with no credit behind it is deleted.
    private function start($user, Resume $draft, Credits $credits)
    {
        if (! $credits->chargeFor($user, $draft)) {
            if (! $draft->content && ! $draft->generations) {
                $draft->delete();
            }
            return ResumeController::noCredits();
        }
        $draft->update(['status' => 'queued', 'error' => null, 'generations' => $draft->generations + 1]);
        TranslateResume::dispatch($draft->id);

        return null;
    }

    private function found(Resume $draft)
    {
        return response()->json(['resume' => $draft->fresh()->summary(), 'url' => route('resumes.show', $draft)]);
    }

    private function authorizeResume(Request $request, Resume $resume): void
    {
        abort_unless($request->user()->isCandidate() && $resume->candidate_id === $request->user()->userable_id, 404);
    }
}

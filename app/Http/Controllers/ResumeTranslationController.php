<?php

namespace App\Http\Controllers;

use App\Ai\Resumes;
use App\Billing\Credits;
use App\Jobs\TranslateResume;
use App\Models\Resume;
use App\Support\CvTemplates;
use App\Support\LanguageGuess;
use Illuminate\Http\Request;

// AI translations of a resume (English <-> French) and editable copies of uploaded PDFs. One credit covers the first draft
// and a few rewrites; the candidate checks the draft before saving.
class ResumeTranslationController extends Controller
{
    public function store(Request $request, Resume $resume, Resumes $ai, Credits $credits)
    {
        $this->authorizeResume($request, $resume);
        abort_unless($resume->status === 'ready' && $resume->path, 409, 'Save this resume before translating it.');
        $data = $request->validate(['locale' => 'required|in:en,fr']);
        abort_if($data['locale'] === $resume->locale, 422, 'This resume is already in that language.');
        abort_unless($ai->enabled(), 503, 'Translating resumes is not available right now.');

        return $this->draft($request, $resume, ['source' => 'translation', 'locale' => $data['locale']], [
            'name' => mb_substr($resume->name.' ('.LanguageGuess::name($data['locale']).')', 0, 150),
            'style' => $resume->style,
        ], $credits);
    }

    // An uploaded PDF can't be edited, so the AI reads it into an editable copy in its own language.
    public function copy(Request $request, Resume $resume, Resumes $ai, Credits $credits)
    {
        $this->authorizeResume($request, $resume);
        abort_unless($resume->source === 'uploaded' && $resume->status === 'ready' && $resume->path, 409, 'Only an uploaded PDF needs an editable copy.');
        abort_unless($ai->enabled(), 503, 'Reading resumes is not available right now.');

        return $this->draft($request, $resume, ['source' => 'copy'], [
            'name' => mb_substr($resume->name.' (editable)', 0, 150),
            'locale' => $resume->locale,
            'style' => CvTemplates::style(Resume::where('candidate_id', $resume->candidate_id)->whereNotNull('style')->latest('updated_at')->value('style')),
        ], $credits);
    }

    // Writes a draft or a failed translation again. Saved translations are edited by hand.
    public function regenerate(Request $request, Resume $resume, Resumes $ai, Credits $credits)
    {
        $this->authorizeResume($request, $resume);
        abort_unless(in_array($resume->source, ['translation', 'copy'], true) && in_array($resume->status, ['draft', 'failed'], true), 409, 'Only drafts can be written again.');
        abort_unless($resume->translatedFrom?->path, 409, 'The resume it was made from was deleted.');
        abort_unless($ai->enabled(), 503, 'Translating resumes is not available right now.');
        if ($response = $this->start($request->user(), $resume, $credits)) {
            return $response;
        }

        return $this->found($resume);
    }

    // Opens the draft made from this resume, or starts one. Asking twice makes one call.
    private function draft(Request $request, Resume $source, array $match, array $attributes, Credits $credits)
    {
        $draft = Resume::where('translated_from_id', $source->id)->where($match)->first();
        if ($draft && $draft->status !== 'failed') {
            return $this->found($draft);
        }
        if (! $draft && Resume::where('candidate_id', $source->candidate_id)->count() >= Resume::LIMIT) {
            return response()->json(['message' => 'You can keep up to '.Resume::LIMIT.' resumes. Delete one first.'], 422);
        }

        $draft ??= Resume::create(['candidate_id' => $source->candidate_id, 'translated_from_id' => $source->id, 'status' => 'queued', ...$match, ...$attributes]);
        if ($response = $this->start($request->user(), $draft, $credits)) {
            return $response;
        }

        return $this->found($draft);
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

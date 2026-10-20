<?php

namespace App\Jobs;

use App\Ai\Resumes;
use App\Billing\Credits;
use App\Models\Resume;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

// Writes the AI translation of a resume as a draft the candidate checks before saving.
class TranslateResume implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;
    public int $timeout = 300;

    public function __construct(public int $resumeId) {}

    public function handle(Resumes $resumes, Credits $credits): void
    {
        $draft = Resume::find($this->resumeId);
        if (! $draft || $draft->status !== 'queued') { return; }
        try {
            $result = $resumes->translate($draft);
            $draft->update([...collect($result)->except('source_language')->all(), 'status' => 'draft', 'error' => null]);
            $source = $draft->translatedFrom;
            if ($source && ! $source->locale && $result['source_language']) {
                $source->update(['locale' => $result['source_language']]);
            }
        } catch (\Throwable $e) {
            Log::warning('Resume translation failed.', ['resume_id' => $draft->id, 'error' => Str::limit($e->getMessage(), 300)]);
            $this->fail($draft, $credits);
        }
    }

    public function failed(?\Throwable $e): void
    {
        if ($draft = Resume::find($this->resumeId)) {
            $this->fail($draft, app(Credits::class));
        }
    }

    // A failed draft does not count, and its credit goes back.
    private function fail(Resume $draft, Credits $credits): void
    {
        if ($draft->status !== 'queued') { return; }
        $draft->update(['status' => $draft->content ? 'draft' : 'failed', 'error' => 'We could not translate your resume right now. Please try again in a minute.']);
        if ($draft->generations > 0) { $draft->decrement('generations'); }
        if ($user = $draft->candidate?->user) { $credits->refund($user, $draft->fresh()); }
    }
}

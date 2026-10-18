<?php

namespace App\Http\Controllers;

use App\Ai\Resumes;
use App\Models\Candidate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// A candidate keeps one resume (PDF) on their profile; it fills the profile and feeds tailored CVs and letters.
class ResumeController extends Controller
{
    // Reads the resume with AI and returns suggestions; the candidate reviews them and saves from Settings.
    public function read(Request $request, Resumes $resumes)
    {
        $candidate = $this->candidate($request);
        abort_unless($candidate->resume_path && Storage::disk('local')->exists($candidate->resume_path), 404, 'Upload your resume first.');
        abort_unless($resumes->enabled(), 503, 'Reading resumes is not available right now.');

        try {
            $suggestions = $resumes->read($candidate);
            // A phone number on another account can't be saved, so don't suggest it.
            if ($suggestions['phone_number'] !== '' && User::where('phone_number', $suggestions['phone_number'])->whereKeyNot($request->user()->id)->exists()) {
                $suggestions['phone_number'] = '';
            }
            return response()->json(['suggestions' => $suggestions]);
        } catch (\Throwable $e) {
            Log::warning('Resume reading failed.', ['candidate_id' => $candidate->id, 'error' => Str::limit($e->getMessage(), 300)]);
            return response()->json(['message' => 'We could not read your resume right now. Please try again in a minute.'], 502);
        }
    }

    public function store(Request $request)
    {
        $candidate = $this->candidate($request);
        $request->validate(['resume' => 'required|file|mimes:pdf|max:5120']);

        $file = $request->file('resume');
        $path = $file->store('resumes', 'local');
        if ($candidate->resume_path) { Storage::disk('local')->delete($candidate->resume_path); }
        $candidate->update(['resume_path' => $path, 'resume_name' => mb_substr($file->getClientOriginalName(), 0, 200)]);

        return back()->with('toast', ['type' => 'update', 'message' => 'Resume saved.']);
    }

    public function show(Request $request)
    {
        $candidate = $this->candidate($request);
        abort_unless($candidate->resume_path && Storage::disk('local')->exists($candidate->resume_path), 404);

        return Storage::disk('local')->download($candidate->resume_path, $candidate->resume_name ?: 'resume.pdf');
    }

    public function destroy(Request $request)
    {
        $candidate = $this->candidate($request);
        if ($candidate->resume_path) { Storage::disk('local')->delete($candidate->resume_path); }
        $candidate->update(['resume_path' => null, 'resume_name' => null]);

        return back()->with('toast', ['type' => 'update', 'message' => 'Resume removed.']);
    }

    private function candidate(Request $request): Candidate
    {
        return $request->user()->ensureCandidateProfile() ?? abort(403);
    }
}

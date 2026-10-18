<?php

namespace App\Http\Controllers;

use App\Ai\Resumes;
use App\Models\Student;
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
        $student = $this->student($request);
        abort_unless($student->resume_path && Storage::disk('local')->exists($student->resume_path), 404, 'Upload your resume first.');
        abort_unless($resumes->enabled(), 503, 'Reading resumes is not available right now.');

        try {
            return response()->json(['suggestions' => $resumes->read($student)]);
        } catch (\Throwable $e) {
            Log::warning('Resume reading failed.', ['student_id' => $student->id, 'error' => Str::limit($e->getMessage(), 300)]);
            return response()->json(['message' => 'We could not read your resume right now. Please try again in a minute.'], 502);
        }
    }

    public function store(Request $request)
    {
        $student = $this->student($request);
        $request->validate(['resume' => 'required|file|mimes:pdf|max:5120']);

        $file = $request->file('resume');
        $path = $file->store('resumes', 'local');
        if ($student->resume_path) { Storage::disk('local')->delete($student->resume_path); }
        $student->update(['resume_path' => $path, 'resume_name' => mb_substr($file->getClientOriginalName(), 0, 200)]);

        return back()->with('toast', ['type' => 'update', 'message' => 'Resume saved.']);
    }

    public function show(Request $request)
    {
        $student = $this->student($request);
        abort_unless($student->resume_path && Storage::disk('local')->exists($student->resume_path), 404);

        return Storage::disk('local')->download($student->resume_path, $student->resume_name ?: 'resume.pdf');
    }

    public function destroy(Request $request)
    {
        $student = $this->student($request);
        if ($student->resume_path) { Storage::disk('local')->delete($student->resume_path); }
        $student->update(['resume_path' => null, 'resume_name' => null]);

        return back()->with('toast', ['type' => 'update', 'message' => 'Resume removed.']);
    }

    private function student(Request $request): Student
    {
        return $request->user()->ensureCandidateProfile() ?? abort(403);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

// A candidate keeps one resume (PDF) on their profile; it feeds cover letter drafts.
class ResumeController extends Controller
{
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
        abort_unless($request->user()->isCandidate(), 403);
        return $request->user()->userable;
    }
}

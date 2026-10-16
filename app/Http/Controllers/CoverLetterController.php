<?php

namespace App\Http\Controllers;

use App\Ai\CoverLetters;
use App\Models\Internship;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class CoverLetterController extends Controller
{
    public function store(Request $request, Internship $internship, CoverLetters $letters)
    {
        abort_unless($request->user()->isCandidate(), 403);
        abort_unless($internship->publication_status === 'published', 404);
        if (! $letters->enabled()) {
            return response()->json(['message' => 'Cover letter drafts are not available right now.'], 503);
        }

        try {
            $letter = $letters->write($request->user(), $internship);
        } catch (Throwable $e) {
            Log::warning('Cover letter draft failed.', ['internship_id' => $internship->id, 'error' => Str::limit($e->getMessage(), 300)]);
            return response()->json(['message' => 'We could not write a draft right now. Please try again in a minute.'], 502);
        }

        return $letter === ''
            ? response()->json(['message' => 'We could not write a draft right now. Please try again in a minute.'], 502)
            : response()->json(['letter' => $letter, 'has_resume' => filled($request->user()->userable->resume_path)]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Resources\InternshipResource;
use App\Models\Internship;
use Inertia\Inertia;

class LikeController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->isCandidate(), 403);
        return Inertia::render('Internships/Likes', [
            'likes' => InternshipResource::collection(
                auth()->user()->userable->likes()->withCandidateState(auth()->user())->with('company', 'field', 'city', 'skills', 'translations')->get()
            )
        ]);
    }

    public function store(Internship $internship)
    {
        abort_unless(auth()->user()->isCandidate(), 403);
        \Illuminate\Support\Facades\DB::transaction(function () use ($internship) {
            $candidate = auth()->user()->userable()->lockForUpdate()->first();
            $alreadySaved = $candidate->likes()->where('internships.id', $internship->id)->exists();
            abort_unless($alreadySaved || ($internship->publication_status === 'published' && !$internship->merged_into_id), 404);
            $candidate->likes()->toggle($internship);
        });

        return back();
    }
}

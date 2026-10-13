<?php

namespace App\Http\Controllers;

use App\Http\Resources\InternshipResource;
use App\Models\Internship;
use Inertia\Inertia;

class LikeController extends Controller // only students are authorized to access this controller
{
    public function index()
    {
        abort_unless(auth()->user()->isStudent(), 403);
        return Inertia::render('Internships/Likes', [
            'likes' => InternshipResource::collection(
                auth()->user()->userable->likes()->with('company', 'field', 'city', 'skills')->get()
            )
        ]);
    }

    public function store(Internship $internship)
    {
        abort_unless(auth()->user()->isStudent(), 403);
        auth()->user()->userable->likes()->toggle($internship);

        return back();
    }
}

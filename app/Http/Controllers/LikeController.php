<?php

namespace App\Http\Controllers;

use App\Http\Resources\OpportunityResource;
use App\Models\Opportunity;
use Inertia\Inertia;

class LikeController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->isCandidate(), 403);
        return Inertia::render('Opportunities/Likes', [
            'likes' => OpportunityResource::collection(
                auth()->user()->userable->likes()->withCandidateState(auth()->user())->with('company', 'field', 'city', 'skills', 'translations')->get()
            )
        ]);
    }

    public function store(Opportunity $opportunity)
    {
        abort_unless(auth()->user()->isCandidate(), 403);
        \Illuminate\Support\Facades\DB::transaction(function () use ($opportunity) {
            $candidate = auth()->user()->userable()->lockForUpdate()->first();
            $alreadySaved = $candidate->likes()->where('opportunities.id', $opportunity->id)->exists();
            abort_unless($alreadySaved || ($opportunity->publication_status === 'published' && !$opportunity->merged_into_id), 404);
            $candidate->likes()->toggle($opportunity);
        });

        return back();
    }
}

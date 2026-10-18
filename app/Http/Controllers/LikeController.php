<?php

namespace App\Http\Controllers;

use App\Http\Resources\OpportunityResource;
use App\Models\Opportunity;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LikeController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->isCandidate(), 403);
        $likes = $request->user()->userable->likes()->withCandidateState($request->user())
            ->with(['company.user', 'company.city', 'field', 'city', 'skills', 'sources.importSource', 'translations'])
            ->orderByRaw('COALESCE(opportunities.posted_at, opportunities.first_seen_at, opportunities.created_at) DESC')->orderByDesc('opportunities.id')
            ->paginate(20);
        // Unsaving the last role on a page sends people back to a page that is now empty.
        if ($likes->currentPage() > $likes->lastPage()) {
            return redirect()->route('likes.index', $likes->lastPage() > 1 ? ['page' => $likes->lastPage()] : []);
        }

        return Inertia::render('Opportunities/Likes', [
            'likes' => OpportunityResource::collection($likes),
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

<?php

namespace App\Http\Controllers;

use App\Http\Resources\InternshipResource;
use App\Models\Internship;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PublicPageController extends Controller
{
    public function home(Request $request)
    {
        return Inertia::render('Welcome', [
            'locale' => $request->is('fr', 'fr/*') ? 'fr' : 'en',
            'opportunities' => InternshipResource::collection($this->recent()->limit(6)->get()),
            'posts' => collect(config('nextward_blog'))->take(3)->values(),
        ]);
    }

    public function audience(Request $request, string $audience)
    {
        abort_unless(in_array($audience, ['students', 'professionals'], true), 404);
        $query = $this->recent();
        if ($audience === 'students') {
            $query->where(fn ($query) => $query->where('kind', 'internship')->orWhereIn('seniority', ['entry', 'graduate', 'junior']));
        } else {
            $query->where('kind', 'job');
        }
        return Inertia::render('Audience', [
            'audience' => $audience,
            'locale' => $request->is('fr', 'fr/*') ? 'fr' : 'en',
            'opportunities' => InternshipResource::collection($query->limit(6)->get()),
            'posts' => collect(config('nextward_blog'))->filter(fn ($post) => $audience === 'students' ? in_array($post['category'], ['Students', 'Career planning']) : in_array($post['category'], ['Professionals', 'Career planning']))->take(3)->values(),
        ]);
    }

    private function recent()
    {
        return Internship::available()->with('company.user', 'company.city', 'city', 'field', 'skills', 'sources.importSource')->withCandidateState(request()->user())->orderByRaw('COALESCE(posted_at, first_seen_at, created_at) DESC')->orderByDesc('id');
    }
}

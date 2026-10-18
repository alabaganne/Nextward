<?php

namespace App\Http\Controllers;

use App\Http\Resources\InternshipResource;
use App\Models\Internship;
use App\Support\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Inertia;

class PublicPageController extends Controller
{
    public function home(Request $request)
    {
        return Inertia::render('Welcome', [
            'locale' => $request->is('fr', 'fr/*') ? 'fr' : 'en',
            'opportunities' => InternshipResource::collection($this->recent()->limit(6)->get()),
            'posts' => $this->posts($request, ['students', 'professionals', 'all']),
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
        return Inertia::render($audience === 'students' ? 'Audience/Students' : 'Audience/Professionals', [
            'locale' => $request->is('fr', 'fr/*') ? 'fr' : 'en',
            'opportunities' => InternshipResource::collection($query->limit(6)->get()),
            'posts' => $this->posts($request, [$audience, 'all']),
            'shortcuts' => $this->shortcuts($audience),
        ]);
    }

    private function shortcuts(string $audience): array
    {
        $shortcuts = $audience === 'students' ? [
            'pfe' => ['category' => 'internships', 'program_type' => 'pfe'],
            'summer' => ['category' => 'internships', 'program_type' => 'summer'],
            'graduate' => ['program_type' => 'graduate'],
            'first-jobs' => ['category' => 'jobs', 'seniority' => 'entry'],
            'remote' => ['category' => 'internships', 'work_type' => 'remote'],
            'tunisia' => ['category' => 'tunisia', 'kind' => 'internship'],
        ] : [
            'full-time' => ['category' => 'jobs', 'employment_type' => 'FULL_TIME'],
            'part-time' => ['category' => 'jobs', 'employment_type' => 'PART_TIME'],
            'contract' => ['category' => 'jobs', 'employment_type' => 'CONTRACTOR'],
            'remote' => ['category' => 'jobs', 'work_type' => 'remote'],
            'senior' => ['category' => 'jobs', 'seniority' => 'senior'],
            'tunisia' => ['category' => 'tunisia', 'kind' => 'job'],
            'remote-from-tunisia' => ['category' => 'remote-from-tunisia', 'kind' => 'job'],
            'relocation' => ['category' => 'relocation', 'kind' => 'job'],
        ];

        return collect($shortcuts)->map(fn ($filters, $key) => [
            'key' => $key,
            'path' => '/opportunities'.(isset($filters['category']) ? '/'.$filters['category'] : ''),
            'query' => Arr::query(Arr::except($filters, 'category')),
            'count' => Internship::available()->searchFilters($filters)->count(),
        ])->values()->all();
    }

    private function posts(Request $request, array $audiences): array
    {
        return collect(Blog::all($request->is('fr', 'fr/*') ? 'fr' : 'en'))->whereIn('audience', $audiences)
            ->reject(fn ($post) => $post['draft'])->take(3)->map(fn ($post) => Blog::summary($post))->values()->all();
    }

    private function recent()
    {
        return Internship::available()->with('company.user', 'company.city', 'city', 'field', 'skills', 'sources.importSource', 'translations')->withCandidateState(request()->user())->orderByRaw('COALESCE(posted_at, first_seen_at, created_at) DESC')->orderByDesc('id');
    }
}

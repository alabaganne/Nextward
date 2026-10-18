<?php

namespace App\Http\Controllers;

use App\Http\Resources\OpportunityResource;
use App\Models\Opportunity;
use App\Support\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Inertia;

class PublicPageController extends Controller
{
    public function home(Request $request)
    {
        $locale = $request->is('fr', 'fr/*') ? 'fr' : 'en';
        return Inertia::render('Welcome', [
            'locale' => $locale,
            'opportunities' => OpportunityResource::collection($this->recent()->limit(6)->get()),
            'posts' => $this->posts($request, ['students', 'professionals', 'all']),
            'shortcuts' => $this->shortcuts(),
            'faqs' => config('nextward_public.faq.'.$locale),
        ]);
    }

    // Popular searches with live counts; empty ones are left out.
    private function shortcuts(): array
    {
        $shortcuts = [
            'internships' => ['category' => 'internships'],
            'pfe' => ['category' => 'internships', 'program_type' => 'pfe'],
            'graduate' => ['program_type' => 'graduate'],
            'jobs' => ['category' => 'jobs'],
            'tunisia' => ['category' => 'tunisia'],
            'remote-from-tunisia' => ['category' => 'remote-from-tunisia'],
            'relocation' => ['category' => 'relocation'],
        ];

        return collect($shortcuts)->map(fn ($filters, $key) => [
            'key' => $key,
            'path' => '/opportunities'.(isset($filters['category']) ? '/'.$filters['category'] : ''),
            'query' => Arr::query(Arr::except($filters, 'category')),
            'count' => Opportunity::available()->searchFilters($filters)->count(),
        ])->filter(fn ($shortcut) => $shortcut['count'] > 0)->values()->all();
    }

    private function posts(Request $request, array $audiences): array
    {
        return collect(Blog::all($request->is('fr', 'fr/*') ? 'fr' : 'en'))->whereIn('audience', $audiences)
            ->reject(fn ($post) => $post['draft'])->take(3)->map(fn ($post) => Blog::summary($post))->values()->all();
    }

    private function recent()
    {
        return Opportunity::available()->with('company.user', 'company.city', 'city', 'field', 'skills', 'sources.importSource', 'translations')->withCandidateState(request()->user())->orderByRaw('COALESCE(posted_at, first_seen_at, created_at) DESC')->orderByDesc('id');
    }
}

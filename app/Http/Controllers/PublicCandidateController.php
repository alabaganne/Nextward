<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Notifications\PublicProfileReviewed;
use App\Support\PublicSeo;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PublicCandidateController extends Controller
{
    public function index(Request $request)
    {
        $locale = $request->is('fr/*', 'fr') ? 'fr' : 'en';
        $candidates = Candidate::publiclyListed()->with('user', 'field', 'city', 'skills')
            ->orderByDesc('public_reviewed_at')->orderByDesc('id')
            ->paginate(24)->withQueryString()
            ->through(fn (Candidate $candidate) => $this->card($candidate, $locale));

        return Inertia::render('Candidates/Directory', [
            'candidates' => $candidates,
            'seo' => PublicSeo::candidates(),
        ]);
    }

    public function show(Request $request, string $candidateSlug)
    {
        abort_unless(preg_match('/^([1-9][0-9]*)-/', $candidateSlug, $matches), 404);
        $candidate = Candidate::with('user', 'field', 'city', 'skills')->findOrFail($matches[1]);
        $viewer = $request->user();
        // The candidate and admins can preview a profile that is not live yet.
        $canPreview = $viewer && ($viewer->isAdmin() || ($viewer->isCandidate() && $viewer->userable_id === $candidate->id));
        abort_unless($candidate->user && ($candidate->isPubliclyListed() || $canPreview), 404);

        $locale = $request->is('fr/*') ? 'fr' : 'en';
        if ($candidateSlug !== basename(parse_url($candidate->publicUrl($locale), PHP_URL_PATH))) {
            return redirect($candidate->publicUrl($locale), 301);
        }

        return Inertia::render('Candidates/Public', [
            'candidate' => [
                ...$this->card($candidate, $locale),
                'about' => $candidate->about,
                'experience_years' => $candidate->experience_years,
                'languages' => $candidate->languages ?: [],
                'website' => $candidate->website,
                'linkedin_profile_url' => $candidate->user->linkedin_profile_url,
            ],
            'status' => $candidate->public_status,
            'seo' => PublicSeo::candidate($candidate),
            'translation' => parse_url($candidate->publicUrl($locale === 'fr' ? 'en' : 'fr'), PHP_URL_PATH),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        abort_unless($user->isCandidate(), 403);
        $data = $request->validate(['public' => 'required|boolean']);
        $candidate = $user->userable;

        if (! $data['public']) {
            $candidate->forceFill(['public_status' => null, 'public_reviewed_at' => null])->save();
            $message = 'Your profile is private.';
        } elseif (in_array($candidate->public_status, ['pending', 'approved'], true)) {
            $message = 'Your request is already saved.';
        } else {
            $candidate->forceFill(['public_status' => 'pending', 'public_reviewed_at' => null])->save();
            $message = 'Request sent. Your profile goes public once the Nextward team approves it.';
        }

        return back()->with('toast', ['type' => 'update', 'message' => $message]);
    }

    public function review(Request $request, Candidate $candidate)
    {
        $data = $request->validate(['decision' => 'required|in:approve,decline']);
        if (! $candidate->public_status) {
            throw ValidationException::withMessages(['decision' => 'This candidate has not asked for a public profile.']);
        }

        $status = $data['decision'] === 'approve' ? 'approved' : 'declined';
        if ($candidate->public_status !== $status) {
            $candidate->forceFill(['public_status' => $status, 'public_reviewed_at' => now()])->save();
            $candidate->user?->notify(new PublicProfileReviewed($candidate));
        }

        return back()->with('toast', ['type' => 'update', 'message' => $status === 'approved' ? 'Profile approved. It is now public.' : 'Profile declined. It stays private.']);
    }

    private function card(Candidate $candidate, string $locale): array
    {
        return [
            'id' => $candidate->id,
            'name' => $candidate->user->name,
            'headline' => $candidate->headline,
            'field' => $candidate->field?->name,
            'location' => $candidate->location ?: (collect([$candidate->city?->name, $candidate->country_code])->filter()->implode(', ') ?: null),
            'skills' => $candidate->skills->pluck('name')->merge($candidate->preferences['skills'] ?? [])->unique()->values()->take(12),
            'url' => parse_url($candidate->publicUrl($locale), PHP_URL_PATH),
        ];
    }
}

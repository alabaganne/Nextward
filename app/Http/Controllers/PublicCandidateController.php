<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Field;
use App\Notifications\PublicProfileReviewed;
use App\Support\PublicSeo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PublicCandidateController extends Controller
{
    public function index(Request $request)
    {
        $locale = $request->is('fr/*', 'fr') ? 'fr' : 'en';
        [$candidates, $filters] = $this->search($request, $locale);

        return Inertia::render('Candidates/Directory', [
            'candidates' => $candidates,
            'filters' => $filters,
            'fields' => $this->fields(),
            'seo' => PublicSeo::candidates(),
        ]);
    }

    // The same directory inside the workspace, for admins and companies.
    public function talent(Request $request)
    {
        abort_unless($request->user()->isAdmin() || $request->user()->isCompany(), 403);
        [$candidates, $filters] = $this->search($request, 'en');

        return Inertia::render('Talent/Index', [
            'candidates' => $candidates,
            'filters' => $filters,
            'fields' => $this->fields(),
        ]);
    }

    private function search(Request $request, string $locale): array
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'field' => ['nullable', 'integer'],
            'resume' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $search = trim($filters['search'] ?? '');
        $field = isset($filters['field']) ? (int) $filters['field'] : null;
        $resume = (bool) ($filters['resume'] ?? false);

        $candidates = Candidate::publiclyListed()->with('user', 'field', 'city', 'skills', 'mainResume')
            ->when($search !== '', function ($query) use ($search) {
                $term = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';
                $query->where(fn ($query) => $query->where('headline', 'like', $term)->orWhere('location', 'like', $term)
                    ->orWhere('preferences', 'like', $term)
                    ->orWhereHas('user', fn ($query) => $query->where('name', 'like', $term))
                    ->orWhereHas('skills', fn ($query) => $query->where('name', 'like', $term))
                    ->orWhereHas('field', fn ($query) => $query->where('name', 'like', $term)));
            })
            ->when($field, fn ($query) => $query->where('field_id', $field))
            ->when($resume, fn ($query) => $query->where('public_contact', true)->whereHas('mainResume'))
            ->orderByDesc('public_reviewed_at')->orderByDesc('id')
            ->paginate(24)->withQueryString()
            ->through(fn (Candidate $candidate) => $this->card($candidate, $locale));

        return [$candidates, ['search' => $search, 'field' => $field, 'resume' => $resume]];
    }

    private function fields()
    {
        return Field::whereHas('candidates', fn ($query) => $query->publiclyListed())->orderBy('name')->get(['id', 'name']);
    }

    public function show(Request $request, string $candidateSlug)
    {
        $candidate = $this->visible($request, $candidateSlug);

        $locale = $request->is('fr/*') ? 'fr' : 'en';
        if ($candidateSlug !== basename(parse_url($candidate->publicUrl($locale), PHP_URL_PATH))) {
            return redirect($candidate->publicUrl($locale), 301);
        }

        return Inertia::render('Candidates/Public', [
            'candidate' => [
                ...$this->card($candidate, $locale),
                'skills' => $candidate->skills->pluck('name')->merge($candidate->preferences['skills'] ?? [])->unique()->values(),
                'about' => $candidate->about,
                'experience' => $candidate->experience ?: [],
                'education' => $candidate->education ?: [],
                'projects' => $candidate->projects ?: [],
                'experience_years' => $candidate->experience_years,
                'languages' => $candidate->languages ?: [],
                'website' => $candidate->website,
                'linkedin_profile_url' => $candidate->user->linkedin_profile_url,
                // Only when the candidate chose to share them.
                'contact' => $candidate->public_contact ? [
                    'email' => $candidate->user->email,
                    'phone_number' => $candidate->user->phone_number,
                    'resume_url' => $candidate->mainResume?->path ? route('candidates.public.resume', $candidateSlug, false) : null,
                ] : null,
            ],
            'status' => $candidate->public_status,
            'seo' => PublicSeo::candidate($candidate),
            'translation' => parse_url($candidate->publicUrl($locale === 'fr' ? 'en' : 'fr'), PHP_URL_PATH),
        ]);
    }

    public function resume(Request $request, string $candidateSlug)
    {
        $candidate = $this->visible($request, $candidateSlug);
        $resume = $candidate->mainResume;
        abort_unless($candidate->public_contact && $resume?->hasFile(), 404);

        return Storage::disk('local')->response($resume->path, $resume->file_name ?: 'resume.pdf', [
            'Content-Type' => 'application/pdf',
            'X-Robots-Tag' => 'noindex',
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        abort_unless($user->isCandidate(), 403);
        $data = $request->validate(['public' => 'required_without:contact|boolean', 'contact' => 'sometimes|boolean']);
        $candidate = $user->userable;

        if (array_key_exists('contact', $data)) {
            $candidate->forceFill(['public_contact' => $data['contact']])->save();
            return back()->with('toast', ['type' => 'update', 'message' => $data['contact']
                ? 'Your resume, email and phone number will show on your public profile.'
                : 'Your resume, email and phone number are hidden from your public profile.']);
        }

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

    private function visible(Request $request, string $candidateSlug): Candidate
    {
        abort_unless(preg_match('/^([1-9][0-9]*)-/', $candidateSlug, $matches), 404);
        $candidate = Candidate::with('user', 'field', 'city', 'skills', 'mainResume')->findOrFail($matches[1]);
        $viewer = $request->user();
        // The candidate and admins can preview a profile that is not live yet.
        $canPreview = $viewer && ($viewer->isAdmin() || ($viewer->isCandidate() && $viewer->userable_id === $candidate->id));
        abort_unless($candidate->user && ($candidate->isPubliclyListed() || $canPreview), 404);

        return $candidate;
    }

    private function card(Candidate $candidate, string $locale): array
    {
        $skills = $candidate->skills->pluck('name')->merge($candidate->preferences['skills'] ?? [])->unique()->values();

        return [
            'id' => $candidate->id,
            'name' => $candidate->user->name,
            'headline' => $candidate->headline,
            'field' => $candidate->field?->name,
            'location' => $candidate->location ?: (collect([$candidate->city?->name, $candidate->country_code])->filter()->implode(', ') ?: null),
            'skills' => $skills->take(12),
            'skills_count' => $skills->count(),
            'experience_years' => $candidate->experience_years,
            'summary' => $candidate->about ? Str::limit(Str::squish($candidate->about), 220) : null,
            'url' => parse_url($candidate->publicUrl($locale), PHP_URL_PATH),
            'has_resume' => $candidate->public_contact && (bool) $candidate->mainResume?->path,
        ];
    }
}

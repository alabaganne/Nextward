<?php

namespace App\Http\Controllers;

use App\Models\Student;
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
        $candidates = Student::publiclyListed()->with('user', 'field', 'city', 'skills')
            ->orderByDesc('public_reviewed_at')->orderByDesc('id')
            ->paginate(24)->withQueryString()
            ->through(fn (Student $student) => $this->card($student, $locale));

        return Inertia::render('Candidates/Index', [
            'candidates' => $candidates,
            'seo' => PublicSeo::candidates(),
        ]);
    }

    public function show(Request $request, string $candidateSlug)
    {
        abort_unless(preg_match('/^([1-9][0-9]*)-/', $candidateSlug, $matches), 404);
        $student = Student::with('user', 'field', 'city', 'skills')->findOrFail($matches[1]);
        $viewer = $request->user();
        // The candidate and admins can preview a profile that is not live yet.
        $canPreview = $viewer && ($viewer->isAdmin() || ($viewer->isCandidate() && $viewer->userable_id === $student->id));
        abort_unless($student->user && ($student->isPubliclyListed() || $canPreview), 404);

        $locale = $request->is('fr/*') ? 'fr' : 'en';
        if ($candidateSlug !== basename(parse_url($student->publicUrl($locale), PHP_URL_PATH))) {
            return redirect($student->publicUrl($locale), 301);
        }

        return Inertia::render('Candidates/Show', [
            'candidate' => [
                ...$this->card($student, $locale),
                'about' => $student->about,
                'experience_years' => $student->experience_years,
                'languages' => $student->languages ?: [],
                'website' => $student->website,
                'linkedin_profile_url' => $student->user->linkedin_profile_url,
            ],
            'status' => $student->public_status,
            'seo' => PublicSeo::candidate($student),
            'translation' => parse_url($student->publicUrl($locale === 'fr' ? 'en' : 'fr'), PHP_URL_PATH),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        abort_unless($user->isCandidate(), 403);
        $data = $request->validate(['public' => 'required|boolean']);
        $student = $user->userable;

        if (! $data['public']) {
            $student->forceFill(['public_status' => null, 'public_reviewed_at' => null])->save();
            $message = 'Your profile is private.';
        } elseif (in_array($student->public_status, ['pending', 'approved'], true)) {
            $message = 'Your request is already saved.';
        } else {
            $student->forceFill(['public_status' => 'pending', 'public_reviewed_at' => null])->save();
            $message = 'Request sent. Your profile goes public once the Nextward team approves it.';
        }

        return back()->with('toast', ['type' => 'update', 'message' => $message]);
    }

    public function review(Request $request, Student $student)
    {
        $data = $request->validate(['decision' => 'required|in:approve,decline']);
        if (! $student->public_status) {
            throw ValidationException::withMessages(['decision' => 'This candidate has not asked for a public profile.']);
        }

        $status = $data['decision'] === 'approve' ? 'approved' : 'declined';
        if ($student->public_status !== $status) {
            $student->forceFill(['public_status' => $status, 'public_reviewed_at' => now()])->save();
            $student->user?->notify(new PublicProfileReviewed($student));
        }

        return back()->with('toast', ['type' => 'update', 'message' => $status === 'approved' ? 'Profile approved. It is now public.' : 'Profile declined. It stays private.']);
    }

    private function card(Student $student, string $locale): array
    {
        return [
            'id' => $student->id,
            'name' => $student->user->name,
            'headline' => $student->headline,
            'field' => $student->field?->name,
            'location' => $student->location ?: (collect([$student->city?->name, $student->country_code])->filter()->implode(', ') ?: null),
            'skills' => $student->skills->pluck('name')->merge($student->preferences['skills'] ?? [])->unique()->values()->take(12),
            'url' => parse_url($student->publicUrl($locale), PHP_URL_PATH),
        ];
    }
}

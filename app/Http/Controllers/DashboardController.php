<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApplicationResource;
use App\Models\Application;
use App\Models\Internship;
use App\Http\Resources\InternshipResource;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke()
    {
        $user = auth()->user();

        $props = [
            'internships_count' => \App\Models\Internship::open()->count(),
            'students_count' => \App\Models\Student::count(),
            'companies_count' => \App\Models\Company::count(),
        ]; 

        if($user->isCandidate() || $user->isCompany()) {
            $query_field = $user->isCandidate() ? 'student_id' : 'company_id';
            $applications = Application::where($query_field, $user->userable_id)
                ->when($user->isCompany(), fn ($query) => $query->where('channel', 'internal'));
            $props['applications'] = ApplicationResource::collection(
                (clone $applications)->with('internship', 'internship.company', 'internship.company.user', 'internship.company.city', 'internship.field')
                    ->latest()
                    ->take(4)
                    ->get()
            );
            
            $props['applications_count'] = (clone $applications)
                ->whereNotIn('status', ['offer', 'closed'])
                ->count();
            $props['interviews_count'] = (clone $applications)
                ->where('status', 'interview')
                ->count();
        }

        if ($user->isCompany()) {
            $props['roles_count'] = Internship::where('company_id', $user->userable_id)->open()->count();
        }

        if ($user->isCandidate()) {
            $props['saved_count'] = $user->userable->likes()->count();
            $preferences = $user->userable->preferences ?: [];
            $query = Internship::open()->withCandidateState($user)->with('company.user', 'city', 'field', 'skills', 'sources.importSource')
                ->whereNotIn('id', Application::where('student_id', $user->userable_id)->select('internship_id'));
            $reasons = [];
            foreach (['kinds' => 'kind', 'work_types' => 'work_type', 'fields' => 'field_id', 'seniority' => 'seniority'] as $key => $column) {
                if (!empty($preferences[$key])) {
                    $query->whereIn($column, $preferences[$key]);
                    $reasons[] = match ($key) {
                        'kinds' => 'Matches your opportunity types',
                        'work_types' => 'Matches your work arrangement',
                        'fields' => 'Matches your fields of interest',
                        'seniority' => 'Matches your experience preference',
                    };
                }
            }
            if (!empty($preferences['countries'])) {
                $reasons[] = 'Matches a target location or stated remote eligibility';
            }
            if (!empty($preferences['skills'])) {
                $query->where(function ($query) use ($preferences) {
                    foreach ($preferences['skills'] as $skill) {
                        $term = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $skill).'%';
                        $query->orWhereHas('skills', fn ($query) => $query->where('name', $skill))
                            ->orWhere('title', 'like', $term)->orWhere('description', 'like', $term);
                    }
                });
                $reasons[] = 'Includes a skill you selected';
            }
            $recommended = $query->latest()->take(100)->get()->filter(function ($opportunity) use ($user, $preferences) {
                if (! $opportunity->hasFreshSource() || ! $opportunity->isEligibleForCountry($user->userable->country_code, (bool) ($preferences['include_unknown_eligibility'] ?? false))) {
                    return false;
                }
                $countries = $preferences['countries'] ?? [];
                if (! $countries || in_array($opportunity->country_code, $countries, true)) {
                    return true;
                }
                foreach ($countries as $country) {
                    if (collect($opportunity->locations ?: [])->contains('country_code', $country)
                        || ($opportunity->work_type === 'remote' && $opportunity->isEligibleForCountry($country))) {
                        return true;
                    }
                }
                return false;
            })->take(6)->values();
            $props['recommended'] = InternshipResource::collection($recommended);
            $props['recommendation_reasons'] = $reasons ?: ['Recent opportunities to explore. Add preferences in Settings for a closer match.'];
        }

        return \Inertia\Inertia::render('Dashboard', $props);
    }
}

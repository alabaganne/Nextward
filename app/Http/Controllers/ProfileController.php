<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\City;
use App\Models\Company;
use App\Models\Field;
use App\Models\SavedSearch;
use App\Models\Candidate;
use App\Support\ProfileHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function show()
    {
        $user = auth()->user();
        $user->ensureCandidateProfile();
        $profile = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone_number' => $user->phone_number,
            'image' => $user->image,
            'linkedin_profile_url' => $user->linkedin_profile_url,
            'is_admin' => $user->isAdmin(),
            'userable_type' => $user->userable_type,
            'userable_id' => $user->userable_id,
        ];

        if ($user->isCompany()) {
            $company = $user->userable;
            $profile += [
                'about' => $company->about,
                'website' => $company->website,
                'city' => $company->city ? ['name' => $company->city->name] : null,
                'opportunities_count' => $company->opportunities()->count(),
                'country_code' => $company->country_code,
                'location' => $company->location,
            ];
        } elseif ($user->isCandidate()) {
            $candidate = $user->userable;
            $profile += [
                'about' => $candidate->about,
                'field' => $candidate->field ? ['name' => $candidate->field->name] : null,
                'city' => $candidate->city ? ['name' => $candidate->city->name] : null,
                'skills' => $candidate->skills->pluck('name')->merge($candidate->preferences['skills'] ?? [])->unique()->values(),
                'applications_count' => $candidate->applications()->count(),
                'likes_count' => $candidate->likes()->count(),
                'headline' => $candidate->headline,
                'experience_years' => $candidate->experience_years,
                'country_code' => $candidate->country_code,
                'location' => $candidate->location,
                'languages' => $candidate->languages ?: [],
                'website' => $candidate->website,
                'resume_name' => $candidate->resume_name,
                'experience' => $candidate->experience ?: [],
                'education' => $candidate->education ?: [],
                'projects' => $candidate->projects ?: [],
            ];
        }

        return Inertia::render('Profile/Show', ['profile' => $profile]);
    }

    public function edit()
    {
        // The settings screen supersedes the old profile edit form.
        return redirect()->route('settings');
    }

    public function update(Request $request)
    {
        $user = auth()->user();
        $user->ensureCandidateProfile();

        if ($request->filled('country_code') && is_string($request->input('country_code'))) {
            $request->merge(['country_code' => strtoupper(trim($request->input('country_code')))]);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => "required|email|unique:users,email,{$user->id}",
            'phone_number' => "nullable|unique:users,phone_number,{$user->id}",
            'linkedin_profile_url' => 'nullable|url',
            'city_id' => 'nullable|exists:cities,id',
            'field_id' => 'nullable|exists:fields,id',
            'about' => 'nullable|string|max:20000',
            'website' => 'nullable|url',
            'headline' => 'nullable|string|max:255',
            'experience_years' => 'nullable|integer|min:0|max:80',
            'country_code' => 'nullable|string|size:2|regex:/^[A-Z]{2}$/',
            'location' => 'nullable|string|max:255',
            'languages' => 'nullable|array|max:20',
            'languages.*' => 'string|max:50',
            'preferences' => 'nullable|array:kinds,countries,work_types,fields,skills,seniority,include_unknown_eligibility',
            'preferences.kinds' => 'nullable|array|max:2',
            'preferences.kinds.*' => 'in:internship,job',
            'preferences.countries' => 'nullable|array|max:30',
            'preferences.countries.*' => 'string|size:2|regex:/^[A-Z]{2}$/',
            'preferences.work_types' => 'nullable|array|max:3',
            'preferences.work_types.*' => 'in:onsite,hybrid,remote',
            'preferences.fields' => 'nullable|array|max:20',
            'preferences.fields.*' => 'integer|exists:fields,id',
            'preferences.skills' => 'nullable|array|max:30',
            'preferences.skills.*' => 'string|max:80',
            'preferences.seniority' => 'nullable|array|max:10',
            'preferences.seniority.*' => 'in:entry,graduate,mid,senior,lead,executive',
            'preferences.include_unknown_eligibility' => 'nullable|boolean',
            ...ProfileHistory::rules(),
        ]);

        $user->fill(collect($data)->only('name', 'email', 'phone_number', 'linkedin_profile_url')->toArray());
        $emailChanged = $user->isDirty('email');
        if ($emailChanged) {
            $user->email_verified_at = null;
        }
        $user->save();

        if ($emailChanged) {
            try {
                $user->sendEmailVerificationNotification();
            } catch (\Throwable $exception) {
                Log::warning('Verification email failed after an email change.', ['user_id' => $user->id, 'error' => $exception->getMessage()]);
            }
        }

        if ($user->isCompany()) {
            $user->userable->update(collect($data)->only('name', 'about', 'website', 'city_id', 'country_code', 'location')->toArray());
        } elseif ($user->isCandidate()) {
            $history = [];
            foreach (array_keys(ProfileHistory::FIELDS) as $list) {
                if ($request->has($list)) { $history[$list] = ProfileHistory::clean($list, $data[$list] ?? []); }
            }
            $user->userable->update([...collect($data)->only('about', 'website', 'field_id', 'city_id', 'headline', 'experience_years', 'country_code', 'location', 'languages', 'preferences')->toArray(), ...$history]);
        }

        return Redirect::route('profile.show')->with('toast', [
            'type' => 'update',
            'message' => 'Profile updated successfully.'
        ]);
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        $files = [];

        DB::transaction(function () use ($user, &$files) {
            $profile = $user->userable;

            if ($profile instanceof Candidate) {
                $applications = Application::where('candidate_id', $profile->id)->get();
                $files = $applications->flatMap(fn ($application) => $application->attachments ?: [])
                    ->map(fn ($attachment) => is_array($attachment) ? Application::attachmentStorage($attachment) : null)
                    ->filter()->all();
                if ($profile->resume_path) { $files[] = ['disk' => 'local', 'path' => $profile->resume_path]; }

                Application::whereKey($applications->modelKeys())->delete();
                $profile->likes()->detach();
                $profile->openedOpportunities()->detach();
                $profile->skills()->detach();
                $profile->delete();
            } elseif ($profile instanceof Company) {
                // Keep the company row so candidates still see who they applied to.
                $profile->opportunities()->update(['status' => 'closed', 'publication_status' => 'suppressed']);
                $profile->update(['name' => $profile->name ?: $user->name]);
            }

            SavedSearch::where('user_id', $user->id)->delete();
            $user->notifications()->delete();
            $user->delete();
        });

        foreach ($files as $file) {
            Storage::disk($file['disk'])->delete($file['path']);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function settings()
    {
        $user = auth()->user();
        $user->ensureCandidateProfile();
        $userable = $user->userable;

        return Inertia::render('Settings', [
            'fields' => Field::all(),
            'cities' => City::all(),
            'settings' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
                'linkedin_profile_url' => $user->linkedin_profile_url,
                'userable_type' => $user->userable_type,
                'about' => $userable->about ?? null,
                'website' => $userable->website ?? null,
                'city_id' => $userable->city_id ?? null,
                'field_id' => $userable->field_id ?? null,
                'headline' => $userable->headline ?? null,
                'experience_years' => $userable->experience_years ?? null,
                'country_code' => $userable->country_code ?? null,
                'location' => $userable->location ?? null,
                'languages' => $userable->languages ?? [],
                'preferences' => $userable->preferences ?? [],
                'resume_name' => $userable->resume_name ?? null,
                'experience' => $userable->experience ?? [],
                'education' => $userable->education ?? [],
                'projects' => $userable->projects ?? [],
                'email_verified' => $user->hasVerifiedEmail(),
                'public_status' => $user->isCandidate() ? $userable->public_status : null,
                'public_url' => $user->isCandidate() ? parse_url($userable->publicUrl(), PHP_URL_PATH) : null,
            ],
        ]);
    }
}

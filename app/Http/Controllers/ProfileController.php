<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Field;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function show()
    {
        $user = auth()->user();
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
                'internships_count' => $company->internships()->count(),
            ];
        } elseif ($user->isStudent()) {
            $student = $user->userable;
            $profile += [
                'about' => $student->about,
                'field' => $student->field ? ['name' => $student->field->name] : null,
                'city' => $student->city ? ['name' => $student->city->name] : null,
                'skills' => $student->skills->pluck('name'),
                'applications_count' => $student->applications()->count(),
                'likes_count' => $student->likes()->count(),
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

        $data = $request->validate([
            'name' => 'required',
            'email' => "required|email|unique:users,email,{$user->id}",
            'phone_number' => "nullable|unique:users,phone_number,{$user->id}",
            'linkedin_profile_url' => 'nullable|url',
            'city_id' => 'nullable|exists:cities,id',
            'field_id' => 'nullable|exists:fields,id',
            'about' => 'nullable',
            'website' => 'nullable|url',
        ]);

        $user->update(collect($data)->only('name', 'email', 'phone_number', 'linkedin_profile_url')->toArray());

        if ($user->isCompany()) {
            $user->userable->update(collect($data)->only('about', 'website', 'city_id')->toArray());
        } elseif ($user->isStudent()) {
            $user->userable->update(collect($data)->only('about', 'field_id', 'city_id')->toArray());
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

        \Illuminate\Support\Facades\Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function settings()
    {
        $user = auth()->user();
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
            ],
        ]);
    }
}

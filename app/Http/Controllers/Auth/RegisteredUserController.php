<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Company;
use App\Models\Field;
use App\Models\Candidate;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     *
     * @return \Illuminate\View\View
     */
    public function create(Request $request)
    {
        return Inertia::render('Auth/Register', [
            'cities' => City::select('id', 'name')->orderBy('name')->get(),
            'fields' => Field::select('id', 'name')->orderBy('name')->get(),
            'accountType' => $request->query('account') === 'company' ? 'company' : 'candidate',
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $request->merge([
            'account_type' => $request->input('account_type', 'candidate'),
            'country_code' => is_string($request->input('country_code')) ? strtoupper(trim($request->input('country_code'))) : $request->input('country_code'),
        ]);

        $data = $request->validate([
            'account_type' => 'required|in:student,candidate,company',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|confirmed|min:8',
            'phone_number' => 'nullable|string|max:255|unique:users,phone_number',
            'linkedin_profile_url' => 'nullable|url|max:255',
            'city_id' => 'nullable|exists:cities,id',
            'field_id' => 'nullable|exists:fields,id',
            'country_code' => 'nullable|string|size:2|regex:/^[A-Z]{2}$/',
            'location' => 'nullable|string|max:255',
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
            'preferences.seniority.*' => 'string|max:30',
            'preferences.include_unknown_eligibility' => 'nullable|boolean',
            'about' => 'required_if:account_type,company|nullable|string|min:30',
            'website' => 'required_if:account_type,company|nullable|url:http,https|max:255',
            'company_logo' => 'required_if:account_type,company|nullable|image|max:2048',
            'terms' => 'accepted',
        ], [
            'terms.accepted' => __('Please agree to the terms of use and the privacy policy to create an account.'),
            'account_type.required' => __('Choose whether you want to find opportunities or hire candidates.'),
            'company_logo.required_if' => __('Upload a company logo before creating a company account.'),
            'website.required_if' => __('Add your company website before creating a company account.'),
            'about.required_if' => __('Tell candidates about your company before creating a company account.'),
            'about.min' => __('The company description should be at least 30 characters.'),
        ]);

        $user = DB::transaction(function () use ($request, $data) {
            $profile = $data['account_type'] === 'company'
                ? Company::create([
                    'name' => $data['name'],
                    'website' => $data['website'],
                    'about' => $data['about'],
                    'city_id' => $data['city_id'] ?? null,
                    'country_code' => $data['country_code'] ?? null,
                    'location' => $data['location'] ?? null,
                ])
                : Candidate::create([
                    'about' => $data['about'] ?? null,
                    'field_id' => $data['field_id'] ?? null,
                    'city_id' => $data['city_id'] ?? null,
                    'country_code' => $data['country_code'] ?? null,
                    'location' => $data['location'] ?? null,
                    'preferences' => $data['preferences'] ?? [],
                ]);

            $logoPath = $request->hasFile('company_logo')
                ? $request->file('company_logo')->store('company-logos', 'public')
                : null;

            $user = $profile->user()->make([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'phone_number' => $data['phone_number'] ?? null,
                'linkedin_profile_url' => $data['linkedin_profile_url'] ?? null,
                'image' => $logoPath,
            ]);
            $user->terms_accepted_at = now();
            $user->signup_ref = $request->session()->get('signup_ref');
            $user->save();

            return $user;
        });

        Auth::login($user);
        $request->session()->forget('signup_ref');

        event(new Registered($user));

        return $user->isCompany()
            ? redirect()->route('companies.show', $user->userable)->with('toast', [
                'type' => 'store',
                'message' => 'Company profile completed. You can now post internships and jobs.'
            ])
            : redirect(route('dashboard', absolute: false));
    }
}

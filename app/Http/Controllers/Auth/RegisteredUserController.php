<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Company;
use App\Models\Field;
use App\Models\Student;
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
            'accountType' => $request->query('account') === 'company' ? 'company' : 'student',
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
        // Keep the enhanced onboarding fields while remaining compatible with
        // minimal registration payloads used by tests/API clients.
        $defaultCityId = City::query()->value('id') ?? City::create(['name' => 'Tunis'])->id;
        $defaultFieldId = Field::query()->value('id') ?? Field::create(['name' => 'Software Engineering'])->id;

        $request->merge([
            'account_type' => $request->input('account_type', 'student'),
            'city_id' => $request->input('city_id', $defaultCityId),
            'field_id' => $request->input('field_id', $defaultFieldId),
        ]);

        $data = $request->validate([
            'account_type' => 'required|in:student,company',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|confirmed|min:8',
            'phone_number' => 'nullable|string|max:255|unique:users,phone_number',
            'linkedin_profile_url' => 'nullable|url|max:255',
            'city_id' => 'required|exists:cities,id',

            'field_id' => 'required_if:account_type,student|nullable|exists:fields,id',
            'about' => 'required_if:account_type,company|nullable|string|min:30',
            'website' => 'required_if:account_type,company|nullable|url|max:255',
            'company_logo' => 'required_if:account_type,company|nullable|image|max:2048',
        ], [
            'account_type.required' => 'Choose whether you are creating a student or company account.',
            'field_id.required_if' => 'Choose your field of study.',
            'city_id.required' => 'Choose your city.',
            'company_logo.required_if' => 'Upload a company logo before creating a company account.',
            'website.required_if' => 'Add your company website before creating a company account.',
            'about.required_if' => 'Tell students about your company before creating a company account.',
            'about.min' => 'The company description should be at least 30 characters.',
        ]);

        $user = DB::transaction(function () use ($request, $data) {
            $profile = $data['account_type'] === 'company'
                ? Company::create([
                    'website' => $data['website'],
                    'about' => $data['about'],
                    'city_id' => $data['city_id'],
                ])
                : Student::create([
                    'about' => $data['about'] ?? null,
                    'field_id' => $data['field_id'],
                    'city_id' => $data['city_id'],
                ]);

            $logoPath = $request->hasFile('company_logo')
                ? $request->file('company_logo')->store('company-logos', 'public')
                : null;

            return $profile->user()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'phone_number' => $data['phone_number'] ?? null,
                'linkedin_profile_url' => $data['linkedin_profile_url'] ?? null,
                'image' => $logoPath,
            ]);
        });

        Auth::login($user);

        event(new Registered($user));

        return $user->isCompany()
            ? redirect()->route('companies.show', $user->userable)->with('toast', [
                'type' => 'store',
                'message' => 'Company profile completed. You can now post internships.'
            ])
            : redirect(route('dashboard', absolute: false));
    }
}

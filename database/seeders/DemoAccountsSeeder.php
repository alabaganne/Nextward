<?php

namespace Database\Seeders;

use App\Importing\JobKey;
use App\Models\Application;
use App\Models\Candidate;
use App\Models\City;
use App\Models\Company;
use App\Models\CreditTransaction;
use App\Models\Field;
use App\Models\Opportunity;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

// Two demo accounts for trying the employer and student sides, safe to run in production:
//   php artisan db:seed --class=DemoAccountsSeeder --force
// company@example.com and student@example.com, both with the password "password" (reset on every run).
// The company gets four published listings and a draft; the student applies to three of them.
// Model events stay off, so nothing is emailed, translated by the AI or sent for indexing. Repeat runs add nothing new.
class DemoAccountsSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        DB::transaction(function () {
            $tunis = City::firstOrCreate(['name' => 'Tunis']);
            $company = $this->company($tunis);
            $candidate = $this->student($tunis);

            $listings = [];
            foreach ($this->listings() as $key => $listing) {
                $listings[$key] = $this->listing($company, $tunis, $listing);
            }

            foreach ([
                ['fullstack', 'interview', 6, 'I build Laravel and Vue apps in my free time and would love to do it full time with your team.'],
                ['mobile', 'review', 3, 'I have shipped two small React Native apps and want to grow as a mobile developer.'],
                ['internship', 'submitted', 1, 'I am in my final year of computer science and free from June to August.'],
            ] as [$key, $status, $daysAgo, $message]) {
                $application = Application::firstOrNew(['candidate_id' => $candidate->id, 'opportunity_id' => $listings[$key]->id]);
                if ($application->exists) { continue; }
                $application->forceFill([
                    'company_id' => $company->id,
                    'channel' => 'internal',
                    'status' => $status,
                    'message' => $message,
                    'cover_letter' => "Dear Demo Company team,\n\nI would like to apply for the {$listings[$key]->title} position. {$message}\n\nKind regards,\nDemo Student",
                    'attachments' => [],
                    'applied_at' => today()->subDays($daysAgo),
                    'created_at' => now()->subDays($daysAgo),
                    'updated_at' => now()->subDays($daysAgo),
                ])->save();
            }
        });
    }

    private function company(City $city): Company
    {
        $user = User::where('email', 'company@example.com')->first();
        $company = $user?->userable instanceof Company ? $user->userable : new Company();
        $company->fill([
            'name' => 'Demo Company',
            'website' => 'https://example.com',
            'domain' => 'example.com',
            'about' => 'Fictional demo employer for trying Nextward. No real company or vacancy is represented.',
            'city_id' => $city->id,
            'country_code' => 'TN',
            'location' => 'Tunis, Tunisia',
        ])->save();
        $this->account($user, 'company@example.com', 'Demo Company', $company);

        return $company;
    }

    private function student(City $city): Candidate
    {
        $user = User::where('email', 'student@example.com')->first();
        $candidate = $user?->userable instanceof Candidate ? $user->userable : new Candidate();
        $candidate->fill([
            'headline' => 'Computer science student looking for a web development role',
            'about' => 'Fictional demo student for trying Nextward. Final-year computer science student who builds web apps with Laravel and Vue.js.',
            'field_id' => $this->fieldId('Web Development'),
            'city_id' => $city->id,
            'country_code' => 'TN',
            'location' => 'Tunis, Tunisia',
            'experience_years' => 0,
            'languages' => ['Arabic', 'French', 'English'],
            'education' => [['school' => 'Demo University', 'degree' => "Bachelor's Degree", 'field' => 'Computer Science', 'start' => '2023-09', 'end' => '2026-06']],
        ])->save();
        $candidate->skills()->syncWithoutDetaching($this->skillIds(['PHP', 'Laravel', 'JavaScript', 'HTML', 'CSS', 'SQL', 'Git']));
        $created = ! $user;
        $user = $this->account($user, 'student@example.com', 'Demo Student', $candidate);
        // The welcome credit a real sign-up gets (User::created is muted here).
        if ($created && ($credits = (int) config('billing.signup_credits')) > 0) {
            CreditTransaction::create(['user_id' => $user->id, 'amount' => $credits, 'reason' => 'welcome']);
        }

        return $candidate;
    }

    private function account(?User $user, string $email, string $name, Company|Candidate $profile): User
    {
        $user ??= new User(['email' => $email]);
        $user->fill(['name' => $name, 'password' => Hash::make('password')]);
        $user->forceFill([
            'email_verified_at' => $user->email_verified_at ?? now(),
            'terms_accepted_at' => $user->terms_accepted_at ?? now(),
            'userable_type' => $profile instanceof Company ? 'company' : 'candidate',
            'userable_id' => $profile->id,
            'is_admin' => false,
        ])->save();

        return $user;
    }

    private function listing(Company $company, City $city, array $listing): Opportunity
    {
        $opportunity = Opportunity::firstOrNew(['company_id' => $company->id, 'title' => $listing['title']]);
        if ($opportunity->exists) { return $opportunity; }

        $published = $listing['publication_status'] === 'published';
        $data = array_merge([
            'origin' => 'native',
            'application_mode' => 'internal',
            'status' => 'open',
            'city_id' => $city->id,
            'country_code' => 'TN',
            'location' => 'Tunis, Tunisia',
            'language' => 'en',
            'compensation_status' => 'paid',
            'pay_currency' => 'TND',
            'pay_unit' => 'month',
            'applicant_scope' => 'local',
            'relocation' => 'no',
            'visa_sponsorship' => 'no',
            'attachments' => [],
        ], collect($listing)->except(['skills', 'field'])->all());
        $data['field_id'] = $this->fieldId($listing['field']);
        $data['remote_eligibility'] = Opportunity::tunisiaEligibility($data) ?? 'unknown';

        $posted = now()->subDays($listing['posted_days_ago'] ?? 0);
        unset($data['posted_days_ago']);
        $opportunity->forceFill([
            ...$data,
            'slug' => Str::limit(Str::slug($listing['title']), 180, ''),
            'job_key' => JobKey::key($company->name, $listing['title'], $data['location']),
            'first_seen_at' => $posted,
            'content_updated_at' => $posted,
            'posted_at' => $published ? $posted : null,
            'added_at' => $published ? $posted : null,
            'created_at' => $posted,
            'updated_at' => $posted,
        ])->save();
        $opportunity->skills()->sync($this->skillIds($listing['skills']));

        return $opportunity;
    }

    private function fieldId(string $name): ?int
    {
        return Field::where('name', $name)->value('id');
    }

    private function skillIds(array $names): array
    {
        return Skill::whereIn('name', $names)->pluck('id')->all();
    }

    private function listings(): array
    {
        $note = "\n\nThis is a demo listing from a fictional company, made for trying Nextward.";

        return [
            'fullstack' => [
                'title' => 'Full-Stack Developer (Laravel + Vue.js)',
                'kind' => 'job', 'field' => 'Web Development', 'work_type' => 'hybrid', 'seniority' => 'mid',
                'employment_types' => ['FULL_TIME'], 'pay_min' => 2500, 'pay_max' => 3500,
                'publication_status' => 'published', 'posted_days_ago' => 10,
                'skills' => ['Laravel', 'PHP', 'JavaScript', 'SQL', 'Git'],
                'description' => "Demo Company builds booking software for small businesses across Tunisia. We are looking for a full-stack developer to work on our Laravel API and Vue.js dashboard.\n\nWhat you will do:\n- Build new features from the database to the interface\n- Review pull requests and help keep the code clean\n- Work with the product team on what to build next\n\nWhat we look for:\n- 2+ years with Laravel and a JavaScript framework\n- Good SQL skills\n- Clear written French or English\n\nHybrid: three days a week at our Tunis office.".$note,
            ],
            'mobile' => [
                'title' => 'React Native Mobile Developer',
                'kind' => 'job', 'field' => 'Mobile Development', 'work_type' => 'remote', 'seniority' => 'entry',
                'employment_types' => ['FULL_TIME'], 'pay_min' => 1800, 'pay_max' => 2600,
                'applicant_scope' => 'listed', 'eligible_countries' => ['TN'], 'remote_scope' => 'Tunisia',
                'publication_status' => 'published', 'posted_days_ago' => 7,
                'skills' => ['React Native', 'TypeScript', 'JavaScript', 'Git'],
                'description' => "Join the team behind the Demo Company mobile app, used by shop owners to manage their bookings on the go.\n\nWhat you will do:\n- Build screens and features in React Native and TypeScript\n- Fix bugs reported by our users\n- Publish releases to the App Store and Google Play\n\nWhat we look for:\n- A first year of experience with React Native, or strong side projects\n- Comfort with TypeScript\n\nFully remote from anywhere in Tunisia.".$note,
            ],
            'data' => [
                'title' => 'Junior Data Scientist',
                'kind' => 'job', 'field' => 'Data Science & AI', 'work_type' => 'onsite', 'seniority' => 'graduate',
                'employment_types' => ['FULL_TIME'], 'pay_min' => 2000, 'pay_max' => 2800,
                'publication_status' => 'published', 'posted_days_ago' => 4,
                'skills' => ['Python', 'Pandas', 'Machine Learning', 'SQL'],
                'description' => "Help Demo Company understand how customers use the product and predict which bookings will be cancelled.\n\nWhat you will do:\n- Clean and explore booking data with Python and Pandas\n- Build and test simple prediction models\n- Share what you find with the product team\n\nWhat we look for:\n- A recent degree in data science, statistics or computer science\n- Python and SQL\n\nOn site at our Tunis office.".$note,
            ],
            'internship' => [
                'title' => 'Summer Internship: Web Development',
                'kind' => 'internship', 'field' => 'Web Development', 'work_type' => 'onsite', 'program_type' => 'summer',
                'duration_weeks' => 8, 'term' => 'Summer 2027', 'employment_types' => ['INTERN'], 'pay_min' => 600, 'pay_max' => 600,
                'publication_status' => 'published', 'posted_days_ago' => 2,
                'skills' => ['HTML', 'CSS', 'JavaScript', 'PHP'],
                'description' => "An eight-week summer internship for students who want to build real features for a live product.\n\nWhat you will do:\n- Build a small feature with a mentor, from design to release\n- Learn how we review and ship code\n\nWhat we look for:\n- A student in computer science or a related field\n- The basics of HTML, CSS and JavaScript\n\nOn site in Tunis, June to August, paid.".$note,
            ],
            'devops' => [
                'title' => 'DevOps Engineer',
                'kind' => 'job', 'field' => 'DevOps & Cloud', 'work_type' => 'hybrid', 'seniority' => 'mid',
                'employment_types' => ['FULL_TIME'], 'pay_min' => 3000, 'pay_max' => 4000,
                'publication_status' => 'draft',
                'skills' => ['Docker', 'Kubernetes', 'Git'],
                'description' => "A draft listing, not published yet: run and improve the servers and deployment pipeline behind Demo Company.\n\nWhat we look for:\n- Docker and Kubernetes in production\n- CI/CD pipelines".$note,
            ],
        ];
    }
}

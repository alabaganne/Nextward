<?php

namespace Tests\Fixtures;

use App\Models\Application;
use App\Models\City;
use App\Models\Company;
use App\Models\Field;
use App\Models\Internship;
use App\Models\ImportSource;
use App\Models\OpportunitySource;
use App\Models\SavedSearch;
use App\Models\Message;
use App\Models\Skill;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Faker\Factory as FakerFactory;

class DemoSeeder extends Seeder
{
    use WithoutModelEvents;
    public function run()
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Demo data can only be seeded in local or testing environments.');
        }

        Schema::disableForeignKeyConstraints();
        try {
            foreach (['jobs', 'failed_jobs', 'job_batches', 'sessions', 'password_resets', 'password_reset_tokens',
                'indexing_notifications', 'company_claims', 'opportunity_audits', 'opportunity_reports',
                'opportunity_deliveries', 'saved_searches', 'opportunity_sources', 'import_runs', 'import_sources',
                'messages', 'notifications', 'likes', 'applications', 'skillables', 'internships',
                'users', 'students', 'companies', 'skills', 'fields', 'cities'] as $table) {
                if (Schema::hasTable($table)) { DB::table($table)->truncate(); }
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $cities = collect(['Tunis','Ariana','Sousse','Sfax','Jendouba','Nabeul','Monastir'])->mapWithKeys(fn ($name) => [$name => City::create(['name' => $name])]);
        $fields = collect(['Software Engineering','Web Development','Data Science & AI','UI/UX Design','Digital Marketing','Business Intelligence','DevOps & Cloud','Mobile Development','Cybersecurity','Network & Systems','Embedded Systems','Finance & Accounting','Other'])->mapWithKeys(fn ($name) => [$name => Field::create(['name' => $name])]);
        $skills = collect(['Laravel','Vue','Node.js','PostgreSQL','Figma','UX Research','Python','SQL','Power BI','Docker','REST','Product','PHP','JavaScript','React','TypeScript','Excel'])->mapWithKeys(fn ($name) => [$name => Skill::create(['name' => $name])]);

        $faker = FakerFactory::create();
        $password = Hash::make('password');

        $student = Student::create(['about' => 'Software engineering student focused on Laravel, Vue, and product-minded web applications.', 'field_id' => $fields['Software Engineering']->id, 'city_id' => $cities['Tunis']->id, 'headline' => 'Software engineering student seeking a PFE internship', 'experience_years' => 0, 'country_code' => 'TN', 'location' => 'Tunis', 'languages' => ['ar', 'fr', 'en'], 'preferences' => ['kinds' => ['internship'], 'countries' => ['TN', 'FR'], 'work_types' => ['onsite', 'hybrid', 'remote'], 'include_unknown_eligibility' => false]]);
        $studentUser = $student->user()->create(['name' => 'Ala Baganne', 'email' => 'student@example.com', 'password' => $password, 'phone_number' => '50101959']);

        $student->skills()->sync($skills->only(['Laravel', 'Vue', 'SQL'])->pluck('id'));
        $professional = Student::create(['about' => 'Fictional demo profile: backend engineer with five years of experience building APIs and data products.', 'field_id' => $fields['Software Engineering']->id, 'city_id' => $cities['Sousse']->id, 'headline' => 'Backend engineer, Laravel and PostgreSQL', 'experience_years' => 5, 'country_code' => 'TN', 'location' => 'Sousse', 'languages' => ['ar', 'fr', 'en'], 'preferences' => ['kinds' => ['job'], 'countries' => ['TN', 'DE', 'FR'], 'work_types' => ['remote', 'hybrid'], 'include_unknown_eligibility' => false]]);
        $professionalUser = $professional->user()->create(['name' => 'Yasmine Demo', 'email' => 'professional@example.com', 'password' => $password]);
        $professional->skills()->sync($skills->only(['Laravel', 'PostgreSQL', 'Docker'])->pluck('id'));

        User::forceCreate(['name' => 'Platform Admin', 'email' => 'admin@example.com', 'password' => $password, 'phone_number' => '00000000', 'is_admin' => true]);

        $companySpecs = [
            ['Atlas Cloud','https://atlas.example.com','Tunis','Cloud infrastructure startup building developer-first deployment tooling.'],
            ['Novabyte','https://novabyte.example.com','Tunis','Product studio designing SaaS tools for growing teams.'],
            ['Realinflo','https://realinflo.example.com','Jendouba','Real estate intelligence company using data products to improve property decisions.'],
            ['Greenfield','https://greenfield.example.com','Ariana','Analytics company helping climate and retail teams use data better.'],
            ['Lumen','https://lumen.example.com','Sousse','Fintech product company with a strong design and product culture.'],
            ['Stride Labs','https://stride.example.com','Sfax','Growth lab building marketing systems for modern startups.'],
        ];
        $companies = collect();
        foreach ($companySpecs as [$name,$website,$city,$about]) {
            $company = Company::create(['name' => $name, 'website' => $website, 'domain' => parse_url($website, PHP_URL_HOST), 'about' => 'Fictional demo employer. '.$about, 'city_id' => $cities[$city]->id, 'country_code' => 'TN', 'location' => $city]);
            $company->user()->create(['name' => $name, 'email' => strtolower(str_replace(' ', '', $name)).'@example.com', 'password' => $password, 'phone_number' => $faker->unique()->numerify('########')]);
            $companies[$name] = $company;
        }
        $companies['Realinflo']->user->update(['email' => 'company@example.com']);

        $roles = [
            ['Software Engineering Intern','Atlas Cloud','Software Engineering','Tunis','Build APIs, ship Vue interfaces, and work with senior engineers on cloud workflows. Responsibilities include API development, UI integration, code reviews, and automated tests. Requirements include JavaScript, Git, and curiosity. Benefits include mentorship, production experience, and a strong engineering culture.',['Laravel','Vue','REST']],
            ['UX Design Intern','Novabyte','UI/UX Design','Tunis','Join the product design team to research users, wireframe flows, and prototype new SaaS experiences. Responsibilities include Figma prototypes, usability testing, and design-system updates. Requirements include a portfolio and strong visual judgment.',['Figma','UX Research','Product']],
            ['Backend Developer Intern (Node.js)','Realinflo','Web Development','Jendouba','Build backend services powering real estate analytics products. Responsibilities include Node.js APIs, PostgreSQL schemas, authentication, and documentation. Requirements include SQL, REST, and clean code habits.',['Node.js','PostgreSQL','REST']],
            ['Data Analytics Intern','Greenfield','Data Science & AI','Ariana','Work with datasets, dashboards, and insights for operations teams. Responsibilities include SQL analysis, Python notebooks, and KPI reporting. Requirements include analytical thinking and clear communication.',['Python','SQL','Power BI']],
            ['Product Management Intern','Lumen','Business Intelligence','Sousse','Support roadmap planning, user interviews, and delivery rituals for fintech products. Responsibilities include specs, backlog grooming, and launch notes.',['Product','SQL','UX Research']],
            ['Growth Marketing Intern','Stride Labs','Digital Marketing','Sfax','Plan experiments, write landing pages, and analyze acquisition campaigns. Responsibilities include SEO briefs, campaign reporting, and content ops.',['Product','SQL','Figma']],
            ['DevOps Intern','Atlas Cloud','DevOps & Cloud','Tunis','Help maintain CI/CD pipelines, Docker environments, and monitoring dashboards for production systems.',['Docker','REST','SQL']],
            ['Frontend Developer Intern','Novabyte','Web Development','Tunis','Build polished Vue interfaces from design specs and connect them to Laravel APIs.',['Vue','Laravel','Figma']],
        ];

        $details = [
            ['remote', 24, 'pfe', 900, 'month'],
            ['hybrid', 12, 'summer', 700, 'month'],
            ['onsite', 24, 'pfe', 1000, 'month'],
            ['hybrid', 16, 'other', 850, 'month'],
            ['onsite', 12, 'other', 650, 'month'],
            ['onsite', 12, 'other', null, null],
            ['remote', 24, 'pfe', 1100, 'month'],
            ['hybrid', 16, 'apprenticeship', 950, 'month'],
        ];

        $internships = collect();
        foreach ($roles as $index => [$title, $company, $field, $city, $description, $skillNames]) {
            [$workType, $durationWeeks, $program, $payAmount, $payUnit] = $details[$index];
            $internship = Internship::create([
                'title' => $title, 'slug' => Str::slug($title),
                'description' => "Fictional demo opportunity. This is not an active vacancy.\n\n".$description,
                'company_id' => $companies[$company]->id, 'field_id' => $fields[$field]->id, 'city_id' => $cities[$city]->id,
                'kind' => 'internship', 'origin' => 'native', 'application_mode' => 'internal',
                'country_code' => 'TN', 'location' => $city, 'work_type' => $workType,
                'closing_at' => now()->addDays(30 + $index * 4)->toDateString(),
                'posted_at' => now()->subDays($index + 1), 'first_seen_at' => now()->subDays($index + 1), 'content_updated_at' => now()->subDays($index + 1),
                'duration_weeks' => $durationWeeks, 'program_type' => $program,
                'term' => $program === 'pfe' ? 'PFE '.now()->addMonths(3)->year : null,
                'start_date' => $program === 'summer' ? now()->addYear()->setMonth(6)->startOfMonth()->toDateString() : now()->addMonths(2)->startOfMonth()->toDateString(),
                'seniority' => 'entry', 'employment_types' => ['INTERN', 'FULL_TIME'], 'language' => 'en',
                'remote_eligibility' => $workType === 'remote' ? 'eligible' : 'unknown',
                'remote_scope' => $workType === 'remote' ? 'Tunisia' : null,
                'eligible_countries' => $workType === 'remote' ? ['TN'] : [],
                'eligibility_evidence' => $workType === 'remote' ? ['remote' => 'Fictional demo: candidates may work remotely from Tunisia.'] : [],
                'pay_min' => $payAmount, 'pay_max' => $payAmount, 'pay_currency' => $payAmount === null ? null : 'TND', 'pay_unit' => $payUnit,
                'compensation_status' => $payAmount === null ? 'unspecified' : 'paid', 'attachments' => [],
            ]);
            $internship->skills()->sync(collect($skillNames)->map(fn ($name) => $skills[$name]->id));
            $internships->push($internship);
        }

        $jobSpecs = [
            ['Backend Engineer', 'Realinflo', 'Software Engineering', 'Jendouba', 'hybrid', 'mid', 3200, 4500, ['Laravel', 'PostgreSQL', 'REST']],
            ['Senior Cloud Engineer', 'Atlas Cloud', 'DevOps & Cloud', 'Tunis', 'remote', 'senior', 5000, 6500, ['Docker', 'Python', 'SQL']],
            ['Product Designer', 'Lumen', 'UI/UX Design', 'Sousse', 'hybrid', 'mid', 2800, 4200, ['Figma', 'UX Research', 'Product']],
            ['Junior Data Analyst, draft example', 'Greenfield', 'Business Intelligence', 'Ariana', 'onsite', 'entry', 1800, 2500, ['SQL', 'Power BI', 'Excel']],
        ];
        $jobs = collect();
        foreach ($jobSpecs as $index => [$title, $company, $field, $city, $workType, $seniority, $minimum, $maximum, $skillNames]) {
            $job = Internship::create([
                'title' => $title, 'slug' => Str::slug($title), 'kind' => 'job', 'origin' => 'native', 'application_mode' => 'internal',
                'description' => "Fictional demo opportunity. This is not an active vacancy.\n\nJoin the ".$company." team to deliver reliable products, collaborate with colleagues and document your work. This example includes a clear salary range and an internal application workflow. Experience with ".implode(', ', $skillNames).' is relevant.',
                'company_id' => $companies[$company]->id, 'field_id' => $fields[$field]->id, 'city_id' => $cities[$city]->id,
                'country_code' => 'TN', 'location' => $city, 'work_type' => $workType, 'seniority' => $seniority,
                'employment_types' => ['FULL_TIME'], 'language' => 'en', 'publication_status' => $index === 3 ? 'draft' : 'published',
                'closing_at' => null, 'posted_at' => $index === 3 ? null : now()->subDays($index + 1),
                'first_seen_at' => now()->subDays($index + 1), 'content_updated_at' => now()->subDays($index + 1),
                'pay_min' => $minimum, 'pay_max' => $maximum, 'pay_currency' => 'TND', 'pay_unit' => 'month', 'compensation_status' => 'paid',
                'remote_eligibility' => $workType === 'remote' ? 'eligible' : 'unknown', 'remote_scope' => $workType === 'remote' ? 'Tunisia' : null,
                'eligible_countries' => $workType === 'remote' ? ['TN'] : [], 'attachments' => [],
            ]);
            $job->skills()->sync(collect($skillNames)->map(fn ($name) => $skills[$name]->id));
            $jobs->push($job);
        }

        $sources = collect();
        foreach ([['Northstar Demo', 'northstar.example.com', 'FR', 'Paris'], ['Harbor Demo', 'harbor.example.com', 'DE', 'Berlin'], ['Orbit Demo', 'orbit.example.com', 'CA', 'Montreal']] as [$name, $domain, $country, $location]) {
            $company = Company::create(['name' => $name, 'website' => 'https://'.$domain, 'domain' => $domain, 'about' => 'Fictional imported employer for local demonstrations. No employer account or verified claim.', 'country_code' => $country, 'location' => $location, 'city_id' => null]);
            $companies[$name] = $company;
            $sources[$name] = ImportSource::create(['key' => 'demo-'.Str::slug($name), 'name' => $name.' fictional board', 'provider' => 'jsonld', 'url' => 'https://'.$domain.'/sitemap.xml', 'allowed_domains' => [$domain], 'company_id' => $company->id, 'permission_status' => 'pending', 'permission_notes' => 'Fictional local fixture. No reuse permission has been granted. Do not enable this source.', 'publication_mode' => 'review', 'enabled' => false, 'settings' => ['employer_name' => $name, 'job_path_prefix' => '/jobs/'], 'cadence_hours' => 24, 'request_delay_ms' => 1000, 'max_requests' => 40]);
        }
        $externalSpecs = [
            ['Frontend Internship in Paris', 'Northstar Demo', 'internship', 'Web Development', 'FR', 'Paris', 'hybrid', 'unknown', [], 'yes', 'unknown', 1100, 1400, 'EUR'],
            ['Remote Research Internship', 'Orbit Demo', 'internship', 'Data Science & AI', null, 'Worldwide', 'remote', 'eligible', [], 'unknown', 'unknown', 18, 24, 'USD'],
            ['Stage PFE développement web', 'Northstar Demo', 'internship', 'Web Development', 'TN', 'Tunis', 'onsite', 'unknown', [], 'no', 'unknown', 800, 1000, 'TND'],
            ['Design Internship, closed example', 'Harbor Demo', 'internship', 'UI/UX Design', 'DE', 'Berlin', 'onsite', 'unknown', [], 'unknown', 'unknown', null, null, null],
            ['Senior Backend Engineer, global remote', 'Orbit Demo', 'job', 'Software Engineering', null, 'Worldwide', 'remote', 'eligible', [], 'unknown', 'unknown', 65000, 85000, 'USD'],
            ['Data Engineer, relocation to Berlin', 'Harbor Demo', 'job', 'Data Science & AI', 'DE', 'Berlin', 'hybrid', 'unknown', [], 'yes', 'yes', 55000, 70000, 'EUR'],
            ['Product Designer, EU remote', 'Northstar Demo', 'job', 'UI/UX Design', 'FR', 'France or Germany', 'remote', 'restricted', ['FR', 'DE'], 'no', 'no', 45000, 60000, 'EUR'],
            ['Support Engineer, location to confirm', 'Orbit Demo', 'job', 'Software Engineering', null, null, 'remote', 'unknown', [], 'unknown', 'unknown', null, null, null],
            ['Développeur Laravel à Tunis', 'Northstar Demo', 'job', 'Web Development', 'TN', 'Tunis', 'hybrid', 'unknown', [], 'no', 'unknown', 3000, 4500, 'TND'],
            ['Cloud Engineer, remote from Tunisia', 'Harbor Demo', 'job', 'DevOps & Cloud', 'TN', 'Tunisia', 'remote', 'eligible', ['TN'], 'unknown', 'unknown', 3500, 5000, 'TND'],
            ['Analytics Consultant, expired example', 'Orbit Demo', 'job', 'Business Intelligence', 'CA', 'Montreal', 'hybrid', 'unknown', [], 'yes', 'unknown', 70000, 90000, 'CAD'],
            ['Platform Engineer, suppressed example', 'Harbor Demo', 'job', 'Software Engineering', 'DE', 'Berlin', 'onsite', 'unknown', [], 'yes', 'unknown', 60000, 75000, 'EUR'],
        ];
        $external = collect();
        foreach ($externalSpecs as $index => [$title, $company, $kind, $field, $country, $location, $workType, $eligibility, $allowed, $relocation, $visa, $minimum, $maximum, $currency]) {
            $source = $sources[$company];
            $url = 'https://'.$companies[$company]->domain.'/jobs/demo-'.($index + 1);
            $evidence = [];
            if ($workType === 'remote' && $eligibility === 'eligible') { $evidence['remote'] = $allowed === ['TN'] ? 'Fictional demo: work remotely from Tunisia.' : 'Fictional demo: we hire worldwide.'; }
            if ($eligibility === 'restricted') { $evidence['remote'] = 'Fictional demo: applicants must live in France or Germany.'; }
            if ($relocation === 'yes') { $evidence['relocation'] = 'Fictional demo: relocation assistance is included.'; }
            if ($visa === 'yes') { $evidence['visa_sponsorship'] = 'Fictional demo: employer offers visa sponsorship.'; }
            $description = "Fictional demo opportunity. This is not an active vacancy and the source has not been verified.\n\nThis ".$kind." example demonstrates ".$field." work, clear responsibilities and an external application workflow. Collaborate with the team, document decisions and contribute to practical projects.\n\n".implode(' ', $evidence);
            if (in_array($index, [2, 8])) {
                $description = "Offre fictive de démonstration. Il ne s'agit pas d'un recrutement en cours et la source n'a pas été vérifiée.\n\nParticipez au développement d'applications web à Tunis. Collaborez avec l'équipe produit, développez des interfaces accessibles, documentez les fonctionnalités et testez votre travail. Cet exemple présente une candidature sur un site externe, sans transmettre de dossier depuis Nextward.";
            }
            $opportunity = Internship::create([
                'title' => $title, 'slug' => Str::slug($title), 'description' => $description, 'company_id' => $companies[$company]->id, 'field_id' => $fields[$field]->id,
                'city_id' => $country === 'TN' && $location === 'Tunis' ? $cities['Tunis']->id : null,
                'kind' => $kind, 'origin' => 'imported', 'application_mode' => 'external', 'application_url' => $url,
                'country_code' => $country, 'location' => $location, 'work_type' => $workType,
                'seniority' => $kind === 'internship' ? 'entry' : ($index === 4 ? 'senior' : 'mid'),
                'program_type' => $index === 2 ? 'pfe' : null, 'duration_weeks' => $kind === 'internship' ? 24 : null,
                'employment_types' => $kind === 'internship' ? ['INTERN', 'FULL_TIME'] : ['FULL_TIME'], 'language' => in_array($index, [2, 8]) ? 'fr' : 'en',
                'remote_eligibility' => $eligibility, 'remote_scope' => $workType === 'remote' ? $location : null,
                'eligible_countries' => $allowed, 'excluded_countries' => $eligibility === 'restricted' ? ['TN'] : [],
                'relocation' => $relocation, 'visa_sponsorship' => $visa, 'eligibility_evidence' => $evidence,
                'pay_min' => $minimum, 'pay_max' => $maximum, 'pay_currency' => $currency,
                'pay_unit' => $minimum === null ? null : ($index === 1 ? 'hour' : ($currency === 'TND' || $kind === 'internship' ? 'month' : 'year')),
                'compensation_status' => $minimum === null ? 'unspecified' : 'paid',
                'posted_at' => $index === 7 ? null : now()->subDays($index + 2), 'first_seen_at' => now()->subDays($index + 1), 'content_updated_at' => now()->subDays($index + 1),
                'closing_at' => null, 'expires_at' => $index === 10 ? now()->subDay() : null,
                'status' => $index === 3 ? 'closed' : 'open', 'publication_status' => $index === 11 ? 'suppressed' : 'published', 'attachments' => [],
            ]);
            $opportunity->skills()->sync($skills->only($field === 'UI/UX Design' ? ['Figma', 'UX Research'] : ['SQL', 'Python', 'REST'])->pluck('id'));
            OpportunitySource::create(['import_source_id' => $source->id, 'internship_id' => $opportunity->id, 'external_id' => 'demo-'.($index + 1), 'source_url' => $url, 'application_url' => $url, 'state' => $index === 3 || $index === 10 ? 'closed' : 'published', 'first_seen_at' => $opportunity->first_seen_at, 'last_discovered_at' => $opportunity->first_seen_at, 'normalized_data' => [...$opportunity->only(['title', 'description', 'kind', 'country_code', 'location', 'work_type', 'remote_eligibility', 'eligible_countries', 'excluded_countries', 'relocation', 'visa_sponsorship', 'eligibility_evidence', 'employment_types', 'language', 'pay_min', 'pay_max', 'pay_currency', 'pay_unit', 'compensation_status', 'application_url']), 'employer' => $company, 'website' => $companies[$company]->website, 'posted_at' => $opportunity->posted_at?->toIso8601String(), 'expires_at' => $opportunity->expires_at?->toIso8601String(), 'closing_at' => null]]);
            $external->push($opportunity);
        }
        OpportunitySource::create(['import_source_id' => $sources['Northstar Demo']->id, 'external_id' => 'demo-needs-review', 'source_url' => 'https://northstar.example.com/jobs/demo-needs-review', 'state' => 'review', 'error' => 'Fictional review example: description and application destination need confirmation.', 'first_seen_at' => now()]);

        $applications = collect();
        foreach ($internships->take(4) as $i => $internship) {
            $applications->push(Application::create(['student_id' => $student->id, 'internship_id' => $internship->id, 'company_id' => $internship->company_id, 'cover_letter' => 'I am excited about this role and believe my Laravel/Vue background makes me a strong fit.', 'message' => 'Available to start immediately and happy to interview this week.', 'status' => ['interview', 'review', 'submitted', 'offer'][$i], 'channel' => 'internal', 'applied_at' => now()->subDays($i + 1)->toDateString(), 'attachments' => []]));
        }
        $student->likes()->sync($internships->slice(1, 5)->pluck('id'));

        foreach ($companies->take(4) as $company) {
            Message::create(['from_id' => $company->user->id, 'to_id' => $studentUser->id, 'text' => 'Hi Ala, thanks for applying. Your profile looks strong. Are you available for a short call this week?']);
            Message::create(['from_id' => $studentUser->id, 'to_id' => $company->user->id, 'text' => 'Hi, yes absolutely. I am available tomorrow afternoon or Thursday morning.']);
        }

        // Same payload shape as App\Notifications\{ApplicationSubmitted,ApplicationReviewed}.
        foreach ($applications as $i => $application) {
            DB::table('notifications')->insert([
                'id' => (string) Str::uuid(),
                'type' => \App\Notifications\ApplicationSubmitted::class,
                'notifiable_type' => User::class,
                'notifiable_id' => $application->company->user->id,
                'data' => json_encode(['title' => "{$studentUser->name}'s Application", 'body' => "<span>{$studentUser->name}</span> has applied to <span>{$application->internship->title}</span>.", 'action' => "/applications/{$application->id}"]),
                'read_at' => $i > 1 ? now()->subDays($i) : null,
                'created_at' => now()->subDays($i)->subHours(2),
                'updated_at' => now()->subDays($i)->subHours(2),
            ]);
        }
        foreach ($applications->filter(fn ($application) => $application->status !== 'submitted')->values() as $i => $application) {
            DB::table('notifications')->insert([
                'id' => (string) Str::uuid(),
                'type' => \App\Notifications\ApplicationReviewed::class,
                'notifiable_type' => User::class,
                'notifiable_id' => $studentUser->id,
                'data' => json_encode(['title' => "{$application->company->user->name}'s Reply", 'body' => "<span>{$application->company->user->name}</span> has reviewed your application for <span>{$application->internship->title}</span>.", 'action' => "/applications/{$application->id}"]),
                'read_at' => $i > 0 ? now()->subDays($i) : null,
                'created_at' => now()->subDays($i)->subMinutes(40),
                'updated_at' => now()->subDays($i)->subMinutes(40),
            ]);
        }

        Storage::disk('local')->put('applications/demo/student-cv.txt', "Fictional demo CV. No personal documents are stored in this fixture.\nSkills: Laravel, Vue and SQL.\n");
        $applications->first()->update(['attachments' => [['disk' => 'local', 'path' => 'applications/demo/student-cv.txt', 'name' => 'Demo CV.txt', 'size' => Storage::disk('local')->size('applications/demo/student-cv.txt'), 'mime' => 'text/plain']]]);
        Application::create(['student_id' => $professional->id, 'internship_id' => $jobs->first()->id, 'company_id' => $jobs->first()->company_id, 'channel' => 'internal', 'status' => 'review', 'cover_letter' => 'Fictional demo application: I bring five years of backend experience with Laravel and PostgreSQL.', 'message' => 'Available for a technical interview next week.', 'applied_at' => now()->subDays(2)->toDateString(), 'attachments' => []]);
        foreach ([[$student, $external[0], 'submitted'], [$professional, $external[4], 'interview'], [$professional, $external[10], 'closed']] as [$candidate, $opportunity, $status]) {
            Application::create(['student_id' => $candidate->id, 'internship_id' => $opportunity->id, 'company_id' => $opportunity->company_id, 'channel' => 'external', 'status' => $status, 'applied_at' => now()->subDays(3)->toDateString(), 'candidate_notes' => 'Private fictional tracking note. Applied on the example employer site; no application was sent by Nextward.', 'cover_letter' => null, 'message' => null, 'attachments' => []]);
        }
        $professional->likes()->sync([$jobs[0]->id, $jobs[1]->id, $external[4]->id, $external[5]->id]);
        foreach ([[$studentUser, 'PFE internships in Tunisia', ['kind' => 'internship', 'country' => 'TN', 'program_type' => 'pfe']], [$professionalUser, 'Remote jobs from Tunisia', ['kind' => 'job', 'remote_from_tunisia' => true]], [$professionalUser, 'Jobs with relocation', ['kind' => 'job', 'relocation' => true]]] as [$user, $name, $filters]) {
            SavedSearch::create(['user_id' => $user->id, 'name' => $name, 'filters' => $filters, 'frequency' => 'off', 'timezone' => 'Africa/Tunis', 'subscribed_at' => null]);
        }
        DB::table('opportunity_reports')->insert(['internship_id' => $external[7]->id, 'user_id' => $professionalUser->id, 'reason' => 'incorrect', 'details' => 'Fictional report example: confirm the countries eligible for this remote role.', 'status' => 'open', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('company_claims')->insert(['company_id' => $companies['Northstar Demo']->id, 'user_id' => $companies['Realinflo']->user->id, 'evidence' => 'Fictional pending claim for demonstration only. No employer ownership has been verified.', 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);

        for ($index = 0; $index < 18; $index++) {
            $factory = Student::factory();
            if ($index % 3 === 1) { $factory = $factory->professional(); }
            if ($index % 3 === 2) { $factory = $factory->graduate(); }
            $profile = $factory->create();
            $profile->skills()->sync($skills->random(3)->pluck('id'));
            $profile->user()->create(['name' => $faker->name(), 'email' => 'candidate'.($index + 1).'@example.com', 'password' => $password, 'phone_number' => $faker->unique()->numerify('########')]);
        }

        User::query()->whereNull('email_verified_at')->update(['email_verified_at' => now()]);
    }
}

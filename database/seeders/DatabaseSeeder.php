<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\City;
use App\Models\Company;
use App\Models\Field;
use App\Models\Internship;
use App\Models\Message;
use App\Models\Skill;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Faker\Factory as FakerFactory;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Demo data can only be seeded in local or testing environments.');
        }

        Schema::disableForeignKeyConstraints();
        foreach (['messages','notifications','likes','applications','skillables','internships','users','students','companies','skills','fields','cities'] as $table) {
            DB::table($table)->truncate();
        }
        Schema::enableForeignKeyConstraints();

        $cities = collect(['Tunis','Ariana','Sousse','Sfax','Jendouba','Nabeul','Monastir','Remote'])->mapWithKeys(fn ($name) => [$name => City::create(['name' => $name])]);
        $fields = collect(['Software Engineering','Web Development','Data Science & AI','UI/UX Design','Digital Marketing','Business Intelligence','DevOps & Cloud'])->mapWithKeys(fn ($name) => [$name => Field::create(['name' => $name])]);
        $skills = collect(['Laravel','Vue','Node.js','PostgreSQL','Figma','UX Research','Python','SQL','Power BI','Docker','REST','Product'])->mapWithKeys(fn ($name) => [$name => Skill::create(['name' => $name])]);

        $faker = FakerFactory::create();
        $password = Hash::make('password');

        $student = Student::create(['about' => 'Software engineering student focused on Laravel, Vue, and product-minded web applications.', 'field_id' => $fields['Software Engineering']->id, 'city_id' => $cities['Tunis']->id]);
        $studentUser = $student->user()->create(['name' => 'Ala Baganne', 'email' => 'student@example.com', 'password' => $password, 'phone_number' => '50101959', 'is_admin' => false]);

        User::create(['name' => 'Platform Admin', 'email' => 'admin@example.com', 'password' => $password, 'phone_number' => '00000000', 'is_admin' => true]);

        $companySpecs = [
            ['Atlas Cloud','https://atlas.example.com','Remote','Cloud infrastructure startup building developer-first deployment tooling.'],
            ['Novabyte','https://novabyte.example.com','Tunis','Product studio designing SaaS tools for growing teams.'],
            ['Realinflo','https://realinflo.example.com','Jendouba','Real estate intelligence company using data products to improve property decisions.'],
            ['Greenfield','https://greenfield.example.com','Ariana','Analytics company helping climate and retail teams use data better.'],
            ['Lumen','https://lumen.example.com','Sousse','Fintech product company with a strong design and product culture.'],
            ['Stride Labs','https://stride.example.com','Sfax','Growth lab building marketing systems for modern startups.'],
        ];
        $companies = collect();
        foreach ($companySpecs as [$name,$website,$city,$about]) {
            $company = Company::create(['website' => $website, 'about' => $about, 'city_id' => $cities[$city]->id]);
            $company->user()->create(['name' => $name, 'email' => strtolower(str_replace(' ', '', $name)).'@example.com', 'password' => $password, 'phone_number' => $faker->unique()->numerify('########'), 'is_admin' => false]);
            $companies[$name] = $company;
        }
        $companies['Realinflo']->user->update(['email' => 'company@example.com']);

        $roles = [
            ['Software Engineering Intern','Atlas Cloud','Software Engineering','Remote','Build APIs, ship Vue interfaces, and work with senior engineers on cloud workflows. Responsibilities include API development, UI integration, code reviews, and automated tests. Requirements include JavaScript, Git, and curiosity. Benefits include mentorship, production experience, and a strong engineering culture.',['Laravel','Vue','REST']],
            ['UX Design Intern','Novabyte','UI/UX Design','Tunis','Join the product design team to research users, wireframe flows, and prototype new SaaS experiences. Responsibilities include Figma prototypes, usability testing, and design-system updates. Requirements include a portfolio and strong visual judgment.',['Figma','UX Research','Product']],
            ['Backend Developer Intern (Node.js)','Realinflo','Web Development','Jendouba','Build backend services powering real estate analytics products. Responsibilities include Node.js APIs, PostgreSQL schemas, authentication, and documentation. Requirements include SQL, REST, and clean code habits.',['Node.js','PostgreSQL','REST']],
            ['Data Analytics Intern','Greenfield','Data Science & AI','Ariana','Work with datasets, dashboards, and insights for operations teams. Responsibilities include SQL analysis, Python notebooks, and KPI reporting. Requirements include analytical thinking and clear communication.',['Python','SQL','Power BI']],
            ['Product Management Intern','Lumen','Business Intelligence','Sousse','Support roadmap planning, user interviews, and delivery rituals for fintech products. Responsibilities include specs, backlog grooming, and launch notes.',['Product','SQL','UX Research']],
            ['Growth Marketing Intern','Stride Labs','Digital Marketing','Sfax','Plan experiments, write landing pages, and analyze acquisition campaigns. Responsibilities include SEO briefs, campaign reporting, and content ops.',['Product','SQL','Figma']],
            ['DevOps Intern','Atlas Cloud','DevOps & Cloud','Remote','Help maintain CI/CD pipelines, Docker environments, and monitoring dashboards for production systems.',['Docker','REST','SQL']],
            ['Frontend Developer Intern','Novabyte','Web Development','Tunis','Build polished Vue interfaces from design specs and connect them to Laravel APIs.',['Vue','Laravel','Figma']],
        ];

        $details = [
            ['remote', 16, 'Summer 2026', 28, 'hour'],
            ['remote', 12, 'Summer 2026', 24, 'hour'],
            ['onsite', 16, 'Fall 2026', 25, 'hour'],
            ['hybrid', 16, 'Fall 2026', 22, 'hour'],
            ['remote', 12, 'Summer 2026', 3200, 'month'],
            ['onsite', 12, 'Summer 2026', null, null],
            ['remote', 24, 'Fall 2026', 23, 'hour'],
            ['hybrid', 12, 'Summer 2026', 2000, 'month'],
        ];

        $internships = collect();
        foreach ($roles as $index => [$title,$company,$field,$city,$description,$skillNames]) {
            [$workType, $durationWeeks, $term, $payAmount, $payUnit] = $details[$index % count($details)];
            $internship = Internship::create([
                'title' => $title,
                'description' => $description,
                'company_id' => $companies[$company]->id,
                'field_id' => $fields[$field]->id,
                'city_id' => $cities[$city]->id,
                'closing_at' => now()->addDays(rand(24, 90)),
                'work_type' => $workType,
                'duration_weeks' => $durationWeeks,
                'term' => $term,
                'pay_amount' => $payAmount,
                'pay_unit' => $payUnit,
            ]);
            $internship->skills()->sync(collect($skillNames)->map(fn ($name) => $skills[$name]->id));
            $internships->push($internship);
        }

        $applications = collect();
        foreach ($internships->take(4) as $i => $internship) {
            $applications->push(Application::create(['student_id' => $student->id, 'internship_id' => $internship->id, 'company_id' => $internship->company_id, 'cover_letter' => 'I am excited about this role and believe my Laravel/Vue background makes me a strong fit.', 'message' => 'Available to start immediately and happy to interview this week.', 'status' => ['interview', 'review', 'submitted', 'offer'][$i]]));
        }
        $student->likes()->sync($internships->slice(1, 5)->pluck('id'));

        foreach ($companies->take(4) as $company) {
            Message::create(['from_id' => $company->user->id, 'to_id' => $studentUser->id, 'text' => 'Hi Ala, thanks for applying. Your profile looks strong — are you available for a short call this week?']);
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

        Student::factory(18)->create()->each(function ($profile) use ($password, $faker) {
            $profile->user()->create(['name' => $faker->name(), 'email' => $faker->unique()->safeEmail(), 'password' => $password, 'phone_number' => $faker->unique()->numerify('########'), 'is_admin' => false]);
        });
    }
}

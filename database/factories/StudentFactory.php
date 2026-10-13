<?php

namespace Database\Factories;

use App\Models\Student;
use App\Models\City;
use App\Models\Field;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition()
    {
        $abouts = [
            "Final year Computer Science student at ESPRIT with a passion for full-stack web development. Experienced with Laravel, Vue.js, and MySQL. Completed two academic projects including an e-commerce platform and a student management system. Looking for an internship to apply my skills in a professional environment.",
            "Engineering student specializing in Data Science at INSAT. Proficient in Python, Pandas, and scikit-learn. Completed a machine learning project predicting student dropout rates. Eager to work on real-world data problems and contribute to analytics-driven teams.",
            "Telecommunications student at SUP'COM passionate about network infrastructure and cybersecurity. Familiar with Cisco packet tracer, TCP/IP protocols, and Linux administration. Preparing for CCNA certification and seeking hands-on network engineering experience.",
            "Software engineering student at FST with strong backend development skills. Experienced with Java Spring Boot, RESTful APIs, and PostgreSQL. Contributed to open-source projects on GitHub and looking for a professional internship to grow further.",
            "Computer science student at ENSI interested in mobile development. Built two personal apps in React Native published on the Play Store. Comfortable with JavaScript, TypeScript, Firebase, and REST APIs. Seeking a mobile development internship.",
            "Cybersecurity enthusiast studying at SESAME University. Active on HackTheBox and CTF competitions. Familiar with Kali Linux, Burp Suite, and OWASP Top 10. Looking for an internship in penetration testing or security operations.",
            "Business informatics student at ISG Tunis with a focus on business intelligence. Skilled in SQL, Power BI, and Excel. Completed an internship at a local bank working on financial reporting. Seeking a BI analyst internship.",
            "Multimedia and web design student at ISAMM. Proficient in Figma, Adobe XD, and front-end development (HTML, CSS, JavaScript). Strong portfolio of UI design projects. Interested in UX research and product design internships.",
            "Computer engineering student at ENIT specializing in embedded systems. Experienced with STM32 microcontrollers, FreeRTOS, and C/C++. Built an IoT-based greenhouse monitoring system as a final year project.",
            "Digital marketing student at ISG Sousse. Managed social media accounts for two local businesses gaining 3000+ followers. Familiar with Google Ads, SEMrush, and content creation. Looking for a digital marketing internship.",
            "Student at Higher Institute of Technological Studies in Nabeul pursuing a degree in Computer Science. Comfortable with PHP, Laravel, and MySQL. Participated in coding competitions and hackathons. Motivated to grow as a developer.",
            "IT student at ISET Sfax with interests in DevOps and cloud computing. Self-taught in Docker, GitHub Actions, and basic AWS services. Completed a personal project deploying a Node.js app on AWS EC2. Seeking a DevOps internship.",
        ];

        $city = City::where('name', '!=', 'Remote')->inRandomOrder()->first();
        $field = Field::inRandomOrder()->first();

        return [
            'about' => 'Fictional demo profile. '.$this->faker->randomElement($abouts),
            'field_id' => $field?->id,
            'city_id' => $city?->id,
            'career_stage' => 'student',
            'headline' => 'Student seeking an internship',
            'experience_years' => 0,
            'country_code' => 'TN',
            'location' => $city?->name,
            'languages' => ['fr', 'en'],
            'preferences' => ['kinds' => ['internship'], 'countries' => ['TN'], 'include_unknown_eligibility' => false],
        ];
    }

    public function professional()
    {
        return $this->state(fn () => [
            'career_stage' => 'professional',
            'headline' => 'Experienced specialist seeking a new role',
            'about' => 'Fictional demo professional with experience delivering projects, collaborating with teams and mentoring colleagues.',
            'experience_years' => $this->faker->numberBetween(3, 12),
            'preferences' => ['kinds' => ['job'], 'countries' => ['TN', 'FR', 'DE'], 'work_types' => ['remote', 'hybrid'], 'include_unknown_eligibility' => false],
        ]);
    }

    public function graduate()
    {
        return $this->state(fn () => [
            'career_stage' => 'graduate',
            'headline' => 'Recent graduate seeking a first role',
            'about' => 'Fictional demo graduate with practical academic projects and an interest in entry-level opportunities.',
            'experience_years' => 0,
            'preferences' => ['kinds' => ['internship', 'job'], 'countries' => ['TN'], 'seniority' => ['entry', 'graduate'], 'include_unknown_eligibility' => false],
        ]);
    }
}

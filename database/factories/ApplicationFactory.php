<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\Internship;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

class ApplicationFactory extends Factory
{
    protected $model = Application::class;

    public function definition()
    {
        $coverLetters = [
            "I am a final year Computer Science student at ESPRIT with strong web development skills in Laravel and Vue.js. I am excited about this opportunity as it aligns perfectly with my academic background and career goals. I have worked on several projects involving RESTful APIs and responsive front-end interfaces, and I am confident I can contribute meaningfully to your team from day one.",
            "As a Data Science student at INSAT, I have developed solid skills in Python, machine learning, and data visualization. Your company's use of advanced analytics directly matches my interests and coursework. I am eager to apply my knowledge to real-world datasets and grow under the mentorship of experienced data professionals.",
            "I am passionate about mobile development and have built two personal React Native applications currently live on the Play Store. Your internship offer is exactly the kind of hands-on experience I am seeking to strengthen my skills and contribute to a real product used by real users.",
            "Cybersecurity is my passion. I actively participate in CTF competitions and practice on HackTheBox. Your company's security-focused environment is the perfect place for me to develop professionally. I am familiar with OWASP Top 10, penetration testing tools, and I am motivated to contribute to your security operations.",
            "I have been self-studying DevOps practices for the past year, including Docker, CI/CD, and AWS. Your internship offer is an incredible opportunity to bridge the gap between my academic background and real infrastructure experience. I am a fast learner and a highly motivated team player.",
            "With a strong interest in UI/UX design and a portfolio of mobile and web prototypes built in Figma, I believe I can add real value to your design team. I care deeply about user experience and accessibility, and I am excited to collaborate with your engineers to bring designs to life.",
            "As a Business Intelligence student with hands-on experience in SQL and Power BI, I am excited about the opportunity to work on real business data. I enjoy transforming raw data into actionable insights, and I am confident my analytical skills will make a positive contribution to your analytics team.",
            "I am a motivated Computer Engineering student specializing in embedded systems with experience in STM32 microcontrollers and FreeRTOS. Your IoT projects are exactly the kind of challenges I want to work on, and I am eager to contribute to your hardware-software integration efforts.",
        ];

        $messages = [
            "I am available to start immediately and can commit full-time for the internship duration. Please feel free to contact me for any additional information or to schedule an interview.",
            "I am flexible regarding the start date and duration. I am highly motivated and ready to invest fully in this opportunity.",
            "I would love to discuss how my background aligns with your needs. I am available for an interview at your convenience.",
            "Thank you for considering my application. I am confident that my technical skills and enthusiasm will make me a valuable addition to your team.",
            "I have attached my CV and portfolio for your review. I look forward to hearing from you.",
        ];

        $internship = Internship::available()->inRandomOrder()->first() ?? Internship::factory()->create();
        $student = Student::inRandomOrder()->first() ?? Student::factory()->create();

        return [
            'internship_id' => $internship->id,
            'student_id' => $student->id,
            'company_id' => fn (array $attributes) => Internship::findOrFail($attributes['internship_id'])->company_id,
            'channel' => fn (array $attributes) => Internship::findOrFail($attributes['internship_id'])->application_mode === 'external' ? 'external' : 'internal',
            'applied_at' => now()->subDays($this->faker->numberBetween(0, 7))->toDateString(),
            'status' => 'submitted',
            'cover_letter' => fn (array $attributes) => $attributes['channel'] === 'external' ? null : 'Fictional demo application. '.$this->faker->randomElement($coverLetters),
            'message' => fn (array $attributes) => $attributes['channel'] === 'external' ? null : $this->faker->randomElement($messages),
            'candidate_notes' => fn (array $attributes) => $attributes['channel'] === 'external' ? 'Private fictional tracking note. No application has been submitted by Nextward.' : null,
            'attachments' => [],
        ];
    }
}

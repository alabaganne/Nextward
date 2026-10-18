<?php

namespace Database\Factories;

use App\Models\Opportunity;
use App\Models\Company;
use App\Models\Field;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

class OpportunityFactory extends Factory
{
    protected $model = Opportunity::class;

    public function definition()
    {
        $opportunities = [
            ['title' => 'Full-Stack Web Developer Intern', 'field' => 'Web Development', 'description' => "Join our engineering team to build modern, scalable web applications using Laravel and Vue.js.\n\nResponsibilities:\n- Develop and maintain RESTful APIs\n- Build responsive front-end interfaces with Vue.js and Tailwind CSS\n- Collaborate with the product team on new feature development\n- Write unit and integration tests\n- Participate in code reviews\n\nRequirements:\n- Currently pursuing a degree in Computer Science or related field\n- Familiarity with PHP, JavaScript, HTML/CSS\n- Basic knowledge of Git\n- Strong problem-solving skills\n\nWhat you'll gain:\n- Real-world experience on a production SaaS product\n- Mentorship from senior engineers\n- Agile/Scrum workflow experience"],
            ['title' => 'Data Science Intern', 'field' => 'Data Science & AI', 'description' => "Work alongside our data team to extract insights from large datasets and build machine learning models.\n\nResponsibilities:\n- Clean and preprocess datasets\n- Explore and visualize data using Pandas and Matplotlib\n- Train and evaluate ML models using scikit-learn\n- Document findings and present results to stakeholders\n\nRequirements:\n- Studying Computer Science, Statistics, or Mathematics\n- Knowledge of Python, Pandas, NumPy\n- Familiarity with machine learning concepts\n- Strong analytical mindset\n\nBenefits:\n- Hands-on ML project experience\n- Access to real business data\n- Collaborative and learning-focused environment"],
            ['title' => 'Mobile Developer Intern (React Native)', 'field' => 'Mobile Development', 'description' => "Help us build our cross-platform mobile application serving thousands of users.\n\nResponsibilities:\n- Develop new features in React Native\n- Debug and fix issues across iOS and Android\n- Integrate REST APIs into the mobile app\n- Collaborate with UI/UX designers\n\nRequirements:\n- Knowledge of JavaScript/TypeScript\n- Experience with React or React Native (personal projects count)\n- Understanding of mobile UX principles\n\nBenefits:\n- Published app experience\n- AppStore/PlayStore deployment process\n- Modern mobile development stack"],
            ['title' => 'Cybersecurity Intern', 'field' => 'Cybersecurity', 'description' => "Assist our security team in protecting infrastructure and applications from threats.\n\nResponsibilities:\n- Conduct vulnerability assessments on web applications\n- Monitor security logs and alerts\n- Research emerging threats and mitigation strategies\n- Assist in penetration testing exercises\n- Document security findings\n\nRequirements:\n- Studying Cybersecurity, Networks, or Computer Science\n- Basic knowledge of networking (TCP/IP, DNS, HTTP)\n- Familiarity with Linux\n- Interest in ethical hacking and CTFs is a plus\n\nBenefits:\n- Real security tooling experience (Burp Suite, Nmap, Wireshark)\n- Mentoring from certified security professionals"],
            ['title' => 'UI/UX Design Intern', 'field' => 'UI/UX Design', 'description' => "Join our product design team and help shape the user experience of our platform.\n\nResponsibilities:\n- Create wireframes, mockups, and interactive prototypes\n- Conduct user research and usability testing\n- Collaborate with developers to implement designs\n- Maintain and evolve the design system\n\nRequirements:\n- Studying Design, HCI, or related field\n- Proficiency in Figma or Adobe XD\n- Portfolio demonstrating UI/UX work\n- Eye for detail and strong communication skills\n\nBenefits:\n- Portfolio-worthy design projects\n- Access to premium design tools\n- Collaboration with engineering and product teams"],
            ['title' => 'DevOps Intern', 'field' => 'DevOps & Cloud', 'description' => "Work with our infrastructure team to automate, monitor, and scale our cloud systems.\n\nResponsibilities:\n- Help maintain CI/CD pipelines (GitHub Actions, Jenkins)\n- Monitor system health using Grafana and Prometheus\n- Assist in Docker and Kubernetes deployments\n- Write infrastructure-as-code with Terraform\n- Document infrastructure setup and runbooks\n\nRequirements:\n- Familiarity with Linux and shell scripting\n- Basic knowledge of Docker\n- Interest in cloud platforms (AWS, GCP, or Azure)\n- Ability to work independently\n\nBenefits:\n- Cloud certification study support\n- Exposure to production infrastructure"],
            ['title' => 'Backend Developer Intern (Node.js)', 'field' => 'Software Engineering', 'description' => "Build and maintain server-side applications powering our digital platform.\n\nResponsibilities:\n- Develop REST and GraphQL APIs in Node.js\n- Design efficient database schemas in PostgreSQL\n- Implement authentication and authorization\n- Write clean, documented, testable code\n\nRequirements:\n- Knowledge of JavaScript/TypeScript and Node.js\n- Familiarity with SQL databases\n- Understanding of RESTful API design\n- Git proficiency\n\nBenefits:\n- Production-grade backend experience\n- Code review culture\n- Modern Node.js ecosystem (Fastify, Prisma, Jest)"],
            ['title' => 'Machine Learning Intern', 'field' => 'Data Science & AI', 'description' => "Contribute to our AI team building intelligent features for our enterprise product.\n\nResponsibilities:\n- Research and implement ML algorithms\n- Build data pipelines for model training\n- Evaluate and optimize model performance\n- Collaborate with backend engineers to deploy models\n\nRequirements:\n- Strong Python skills\n- Knowledge of TensorFlow or PyTorch\n- Understanding of supervised and unsupervised learning\n- Linear algebra and statistics background\n\nBenefits:\n- GPU compute access\n- Research-focused environment\n- Co-authorship opportunity on internal papers"],
            ['title' => 'Network Engineer Intern', 'field' => 'Network & Systems', 'description' => "Support our network operations team in designing and maintaining enterprise networks.\n\nResponsibilities:\n- Configure and troubleshoot routers, switches, and firewalls\n- Monitor network performance and resolve incidents\n- Document network topology and procedures\n- Assist in network upgrade projects\n\nRequirements:\n- Studying Telecommunications or Networks\n- Basic Cisco or Juniper knowledge\n- Understanding of TCP/IP, VLANs, BGP\n- CCNA in progress is a plus\n\nBenefits:\n- Enterprise-level Cisco equipment exposure\n- CCNA preparation support"],
            ['title' => 'Embedded Systems Intern', 'field' => 'Embedded Systems', 'description' => "Work on firmware and hardware integration projects for IoT devices.\n\nResponsibilities:\n- Develop firmware in C/C++ for microcontrollers (STM32, ESP32)\n- Integrate sensors and communication modules\n- Debug hardware/software issues using oscilloscopes and logic analyzers\n- Write technical documentation\n\nRequirements:\n- Background in Electronics, Embedded Systems, or Computer Engineering\n- Proficiency in C/C++\n- Basic electronics knowledge\n- Experience with RTOS is a plus\n\nBenefits:\n- Access to hardware lab\n- PCB design exposure\n- IoT product lifecycle experience"],
            ['title' => 'Business Intelligence Intern', 'field' => 'Business Intelligence', 'description' => "Help our analytics team build dashboards and reports that drive business decisions.\n\nResponsibilities:\n- Develop interactive dashboards in Power BI or Tableau\n- Write complex SQL queries for data extraction\n- Collaborate with business stakeholders to define KPIs\n- Automate reporting workflows\n\nRequirements:\n- Studying Business, Finance, or Computer Science\n- Strong SQL skills\n- Experience with Excel or BI tools\n- Analytical thinking and attention to detail\n\nBenefits:\n- End-to-end BI project experience\n- Stakeholder presentation skills\n- Power BI / Tableau certification support"],
            ['title' => 'Digital Marketing Intern', 'field' => 'Digital Marketing', 'description' => "Join our marketing team and help grow our online presence across digital channels.\n\nResponsibilities:\n- Create and schedule content for LinkedIn, Instagram, and Twitter\n- Manage Google Ads and Meta Ads campaigns\n- Analyze campaign performance using Google Analytics\n- Assist in SEO optimization and blog content creation\n\nRequirements:\n- Studying Marketing, Communications, or Business\n- Familiarity with social media platforms and tools\n- Good writing skills in French and/or English\n- Creative mindset\n\nBenefits:\n- Hands-on digital marketing experience\n- Access to premium marketing tools\n- Real budget to manage"],
        ];

        $opportunity = $this->faker->randomElement($opportunities);
        $company = Company::whereHas('user')->inRandomOrder()->first();
        if (! $company) {
            $company = Company::factory()->create();
            $company->user()->save(User::factory()->make());
        }
        $field = Field::firstOrCreate(['name' => $opportunity['field']]);

        return [
            'title' => $opportunity['title'],
            'slug' => Str::slug($opportunity['title']),
            'description' => "Fictional demo opportunity. This is not an active vacancy.\n\n".$opportunity['description'],
            'field_id' => $field->id,
            'company_id' => $company->id,
            'city_id' => $company->city_id,
            'country_code' => $company->country_code,
            'location' => $company->location,
            'kind' => 'internship',
            'origin' => 'native',
            'application_mode' => 'internal',
            'publication_status' => 'published',
            'status' => 'open',
            'work_type' => 'onsite',
            'seniority' => 'entry',
            'employment_types' => ['INTERN', 'FULL_TIME'],
            'program_type' => 'pfe',
            'duration_weeks' => 24,
            'language' => 'en',
            'compensation_status' => 'unspecified',
            'attachments' => [],
            'closing_at' => $this->faker->dateTimeBetween('+1 month', '+6 months'),
            'posted_at' => now()->subDays(2),
            'first_seen_at' => now()->subDays(2),
            'content_updated_at' => now()->subDays(2),
        ];
    }

    public function job()
    {
        return $this->state(function (array $attributes) {
            $title = str_replace(' Intern', '', $attributes['title']);
            return ['kind' => 'job', 'title' => $title, 'slug' => Str::slug($title),
                'description' => "Fictional demo opportunity. This is not an active vacancy.\n\nJoin the team as an experienced specialist. Deliver practical projects, review technical decisions and collaborate with product and engineering colleagues. Prior professional experience is relevant to this job.",
                'seniority' => 'mid', 'employment_types' => ['FULL_TIME'], 'program_type' => null, 'duration_weeks' => null];
        });
    }
}

<?php

return [
    'brand' => [
        'description' => 'Nextward is a job and internship platform for students and professionals in Tunisia. It lists internships (PFE, summer), first jobs and experienced roles in Tunisia and remote roles open to Tunisia, from employers and approved public sources.',
        'email' => 'alabaganne9@gmail.com',
        'logo' => '/brand/nextward/Nextward-colorful-symbol.png',
        'og_image' => '/brand/nextward/og-default.png',
        // Brand profiles (LinkedIn, Facebook, Instagram...), comma separated
        'same_as' => array_values(array_filter(array_map('trim', explode(',', (string) env('NEXTWARD_SOCIAL_LINKS', ''))))),
        'founder' => [
            'name' => 'Ala Baganne',
            'url' => 'https://www.alabaganne.com/',
            'same_as' => ['https://www.linkedin.com/in/alabaganne/'],
        ],
    ],

    'en' => [
        'home' => ['title' => 'Internships and jobs in Tunisia and worldwide | Nextward', 'description' => 'Find internships and jobs for students and professionals in Tunisia and worldwide. Explore locations, eligibility, sources, and application details.'],
        'students' => ['title' => 'Nextward for students: internships and first jobs', 'description' => 'Find a PFE, summer internship, graduate programme, or first job. Explore opportunities in Tunisia and abroad with clear requirements.'],
        'professionals' => ['title' => 'Nextward for professionals: your next career opportunity', 'description' => 'Explore full-time, part-time, contract, and freelance jobs in Tunisia and worldwide. Compare experience, work arrangements, and eligibility.'],
        'opportunities' => ['title' => 'Browse internships and jobs | Nextward', 'description' => 'Search internships and jobs by role, employer, country, work arrangement, and experience. Find opportunities in Tunisia and worldwide.'],
        'internships' => ['title' => 'Internships in Tunisia and worldwide | Nextward', 'description' => 'Explore internships, PFE projects, and summer placements. Check the programme, duration, requirements, and application deadline.'],
        'jobs' => ['title' => 'Jobs in Tunisia and worldwide | Nextward', 'description' => 'Find first jobs and experienced roles across full-time, part-time, and contract employment. Compare locations, salary, and requirements.'],
        'tunisia' => ['title' => 'Internships and jobs in Tunisia | Nextward', 'description' => 'Find local internships and jobs in Tunisia. Search by field, city, work arrangement, and experience level.'],
        'remote-from-tunisia' => ['title' => 'Remote jobs open to candidates in Tunisia | Nextward', 'description' => 'Explore remote opportunities with stated eligibility to work from Tunisia. Unknown eligibility is excluded unless you choose to include it.'],
        'relocation' => ['title' => 'Opportunities abroad with relocation support | Nextward', 'description' => 'Explore opportunities with stated relocation assistance. Check visa sponsorship separately and read the employer requirements before applying.'],
        'about' => ['title' => 'About Nextward | Opportunities for students and professionals', 'description' => 'Nextward connects students and professionals with internships and jobs in Tunisia and worldwide, with clear sources and application details.'],
        'contact' => ['title' => 'Contact Nextward', 'description' => 'Contact Nextward about opportunities, source partnerships, employer hiring, or a problem with a listing.'],
        'privacy' => ['title' => 'Privacy policy | Nextward', 'description' => 'What data Nextward keeps, why, who sees it, how long it is kept, and how to delete your account. Covers Tunisian law 2004-63 and the GDPR.'],
        'terms' => ['title' => 'Terms of use | Nextward', 'description' => 'The rules for using Nextward: accounts, applications, company listings, imported listings from other sites, and what is not allowed.'],
        'blog' => ['title' => 'Career guides for students and professionals | Nextward', 'description' => 'Practical advice for finding internships and jobs, preparing applications, assessing eligibility, and hiring candidates.'],
    ],
    'fr' => [
        'home' => ['title' => 'Stages et emplois en Tunisie et dans le monde | Nextward', 'description' => 'Trouvez des stages et des emplois pour étudiants et professionnels en Tunisie et à l’international. Consultez les conditions et les sources.'],
        'students' => ['title' => 'Nextward pour les étudiants : stages et premiers emplois', 'description' => 'Trouvez un PFE, un stage d’été ou votre premier emploi en Tunisie et à l’étranger. Consultez les critères et les dates de candidature.'],
        'professionals' => ['title' => 'Nextward pour les professionnels : votre prochaine opportunité', 'description' => 'Explorez les emplois à temps plein, à temps partiel, en contrat et en freelance en Tunisie et à l’international.'],
        'opportunities' => ['title' => 'Consulter les stages et les emplois | Nextward', 'description' => 'Recherchez des stages et des emplois par métier, entreprise, pays, mode de travail et expérience, en Tunisie et dans le monde.'],
        'internships' => ['title' => 'Stages en Tunisie et à l’international | Nextward', 'description' => 'Découvrez les stages, les PFE et les stages d’été. Vérifiez la durée, les compétences demandées et la date limite.'],
        'jobs' => ['title' => 'Emplois en Tunisie et à l’international | Nextward', 'description' => 'Trouvez un premier emploi ou un poste expérimenté. Comparez les lieux, la rémunération et les conditions de candidature.'],
        'tunisia' => ['title' => 'Stages et emplois en Tunisie | Nextward', 'description' => 'Consultez les stages et les emplois en Tunisie. Recherchez par domaine, ville, mode de travail et niveau d’expérience.'],
        'remote-from-tunisia' => ['title' => 'Télétravail accessible depuis la Tunisie | Nextward', 'description' => 'Découvrez les offres à distance dont les conditions autorisent explicitement le travail depuis la Tunisie. Vérifiez les restrictions de chaque offre.'],
        'relocation' => ['title' => 'Emplois à l’étranger avec aide à la mobilité | Nextward', 'description' => 'Découvrez les offres avec une aide à la mobilité annoncée. Le parrainage de visa est indiqué séparément lorsqu’il est confirmé.'],
        'about' => ['title' => 'À propos de Nextward | Stages et emplois en Tunisie', 'description' => 'Nextward aide les étudiants et les professionnels à trouver des stages et des emplois en Tunisie et à l’international, avec des sources claires.'],
        'contact' => ['title' => 'Contacter Nextward', 'description' => 'Contactez Nextward au sujet d’une offre, d’un partenariat de source, d’un recrutement ou d’un problème avec une annonce.'],
        'privacy' => ['title' => 'Politique de confidentialité | Nextward', 'description' => 'Les données que Nextward garde, pourquoi, qui y a accès, leur durée de conservation et comment supprimer votre compte. Loi tunisienne 2004-63 et RGPD.'],
        'terms' => ['title' => 'Conditions d’utilisation | Nextward', 'description' => 'Les règles d’utilisation de Nextward : comptes, candidatures, offres des entreprises, offres importées d’autres sites et usages interdits.'],
        'blog' => ['title' => 'Guides carrière pour étudiants et professionnels | Nextward', 'description' => 'Conseils pratiques pour trouver un stage ou un emploi en Tunisie : PFE, SIVP, CV, lettre de motivation, entretien et télétravail.'],
    ],
];

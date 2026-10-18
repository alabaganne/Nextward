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
        'opportunities' => ['title' => 'Browse internships and jobs | Nextward', 'description' => 'Search internships and jobs by role, employer, country, work arrangement, and experience. Find opportunities in Tunisia and worldwide.'],
        'internships' => ['title' => 'Internships in Tunisia and worldwide | Nextward', 'description' => 'Explore internships, PFE projects, and summer placements. Check the programme, duration, requirements, and application deadline.'],
        'jobs' => ['title' => 'Jobs in Tunisia and worldwide | Nextward', 'description' => 'Find first jobs and experienced roles across full-time, part-time, and contract employment. Compare locations, salary, and requirements.'],
        'tunisia' => ['title' => 'Internships and jobs in Tunisia | Nextward', 'description' => 'Find local internships and jobs in Tunisia. Search by field, city, work arrangement, and experience level.'],
        'remote-from-tunisia' => ['title' => 'Remote jobs open to candidates in Tunisia | Nextward', 'description' => 'Explore remote opportunities with stated eligibility to work from Tunisia. Unknown eligibility is excluded unless you choose to include it.'],
        'relocation' => ['title' => 'Opportunities abroad with relocation support | Nextward', 'description' => 'Explore opportunities with stated relocation assistance. Check visa sponsorship separately and read the employer requirements before applying.'],
        'about' => ['title' => 'About Nextward | Our mission and what is free', 'description' => 'Why Nextward exists, what is free for everyone, the AI tools that tailor your CV and cover letter, and where every listing comes from.'],
        'contact' => ['title' => 'Contact Nextward', 'description' => 'Contact Nextward about opportunities, source partnerships, employer hiring, or a problem with a listing.'],
        'privacy' => ['title' => 'Privacy policy | Nextward', 'description' => 'What data Nextward keeps, why, who sees it, how long it is kept, and how to delete your account. Covers Tunisian law 2004-63 and the GDPR.'],
        'terms' => ['title' => 'Terms of use | Nextward', 'description' => 'The rules for using Nextward: accounts, applications, company listings, imported listings from other sites, and what is not allowed.'],
        'blog' => ['title' => 'Career guides for students and professionals | Nextward', 'description' => 'Practical advice for finding internships and jobs, preparing applications, assessing eligibility, and hiring candidates.'],
    ],
    'fr' => [
        'home' => ['title' => 'Stages et emplois en Tunisie et dans le monde | Nextward', 'description' => 'Trouvez des stages et des emplois pour étudiants et professionnels en Tunisie et à l’international. Consultez les conditions et les sources.'],
        'opportunities' => ['title' => 'Consulter les stages et les emplois | Nextward', 'description' => 'Recherchez des stages et des emplois par métier, entreprise, pays, mode de travail et expérience, en Tunisie et dans le monde.'],
        'internships' => ['title' => 'Stages en Tunisie et à l’international | Nextward', 'description' => 'Découvrez les stages, les PFE et les stages d’été. Vérifiez la durée, les compétences demandées et la date limite.'],
        'jobs' => ['title' => 'Emplois en Tunisie et à l’international | Nextward', 'description' => 'Trouvez un premier emploi ou un poste expérimenté. Comparez les lieux, la rémunération et les conditions de candidature.'],
        'tunisia' => ['title' => 'Stages et emplois en Tunisie | Nextward', 'description' => 'Consultez les stages et les emplois en Tunisie. Recherchez par domaine, ville, mode de travail et niveau d’expérience.'],
        'remote-from-tunisia' => ['title' => 'Télétravail accessible depuis la Tunisie | Nextward', 'description' => 'Découvrez les offres à distance dont les conditions autorisent explicitement le travail depuis la Tunisie. Vérifiez les restrictions de chaque offre.'],
        'relocation' => ['title' => 'Emplois à l’étranger avec aide à la mobilité | Nextward', 'description' => 'Découvrez les offres avec une aide à la mobilité annoncée. Le parrainage de visa est indiqué séparément lorsqu’il est confirmé.'],
        'about' => ['title' => 'À propos de Nextward | Notre mission et ce qui est gratuit', 'description' => 'Pourquoi Nextward existe, ce qui est gratuit pour tous, les outils d’IA qui adaptent votre CV et votre lettre, et d’où vient chaque offre.'],
        'contact' => ['title' => 'Contacter Nextward', 'description' => 'Contactez Nextward au sujet d’une offre, d’un partenariat de source, d’un recrutement ou d’un problème avec une annonce.'],
        'privacy' => ['title' => 'Politique de confidentialité | Nextward', 'description' => 'Les données que Nextward garde, pourquoi, qui y a accès, leur durée de conservation et comment supprimer votre compte. Loi tunisienne 2004-63 et RGPD.'],
        'terms' => ['title' => 'Conditions d’utilisation | Nextward', 'description' => 'Les règles d’utilisation de Nextward : comptes, candidatures, offres des entreprises, offres importées d’autres sites et usages interdits.'],
        'blog' => ['title' => 'Guides carrière pour étudiants et professionnels | Nextward', 'description' => 'Conseils pratiques pour trouver un stage ou un emploi en Tunisie : PFE, SIVP, CV, lettre de motivation, entretien et télétravail.'],
    ],

    // Home page questions, shown on the page and sent as FAQPage data.
    'faq' => [
        'en' => [
            ['q' => 'Do I need an account to look at opportunities?', 'a' => 'No. You can search and read every listing without an account. You need one to save roles, apply on Nextward, track your applications and use the AI tools.'],
            ['q' => 'Is Nextward free?', 'a' => 'Yes. Browsing, saving, applying, alerts and tracking are free for everyone. The AI tools are free too while we launch. Later, tailored CVs and cover letters will use paid credits, every account gets one free, and prices will be shown before anything is charged.'],
            ['q' => 'What is a PFE internship?', 'a' => 'A PFE (projet de fin d’études) is the final-year internship that many engineering and business programmes in Tunisia require. Use the PFE search to see only these roles.'],
            ['q' => 'What does a company see when I apply?', 'a' => 'On Nextward, the company sees your profile (headline, field and skills), the cover letter you send and your CV and files. Roles on other websites use their own forms, and opening their link does not send your CV.'],
            ['q' => 'Can I work remotely from Tunisia?', 'a' => 'Only when the listing accepts people based in Tunisia. Remote does not always mean worldwide, so we show eligibility as unknown when the source does not say.'],
            ['q' => 'Why is a listing in English and French?', 'a' => 'We show every listing in both languages. AI translates it from the original, without changing what the employer wrote, and the original text is always one click away under "Original listing".'],
            ['q' => 'Where do the listings come from?', 'a' => 'Some are posted by employers on Nextward. Others come from approved public sources, and each one links to the original listing and shows when it was last checked.'],
        ],
        'fr' => [
            ['q' => 'Faut-il un compte pour consulter les offres ?', 'a' => 'Non. Vous pouvez rechercher et lire toutes les offres sans compte. Il vous en faut un pour sauvegarder des offres, candidater sur Nextward, suivre vos candidatures et utiliser les outils d’IA.'],
            ['q' => 'Nextward est-il gratuit ?', 'a' => 'Oui. Consulter, sauvegarder, candidater, créer des alertes et suivre vos candidatures est gratuit pour tous. Les outils d’IA sont aussi gratuits pendant le lancement. Plus tard, les CV et lettres sur mesure utiliseront des crédits payants, chaque compte en recevra un gratuit, et les prix seront affichés avant tout paiement.'],
            ['q' => 'Qu’est-ce qu’un stage PFE ?', 'a' => 'Le PFE (projet de fin d’études) est le stage de dernière année exigé par de nombreuses écoles d’ingénieurs et de commerce en Tunisie. Utilisez la recherche PFE pour n’afficher que ces offres.'],
            ['q' => 'Que voit l’entreprise quand je candidate ?', 'a' => 'Sur Nextward, l’entreprise voit votre profil (titre, domaine et compétences), la lettre que vous envoyez, ainsi que votre CV et vos fichiers. Les offres d’autres sites utilisent leur propre formulaire, et ouvrir leur lien n’envoie pas votre CV.'],
            ['q' => 'Puis-je travailler à distance depuis la Tunisie ?', 'a' => 'Seulement si l’offre accepte les personnes basées en Tunisie. Le télétravail n’est pas toujours ouvert à tous les pays : nous indiquons une éligibilité inconnue quand la source ne la précise pas.'],
            ['q' => 'Pourquoi une offre est-elle en français et en anglais ?', 'a' => 'Nous affichons chaque offre dans les deux langues. L’IA la traduit depuis l’original sans changer ce que l’employeur a écrit, et le texte d’origine reste accessible sous « Offre d’origine ».'],
            ['q' => 'D’où viennent les offres ?', 'a' => 'Certaines sont publiées par les employeurs sur Nextward. Les autres viennent de sources publiques autorisées : chacune renvoie vers l’annonce originale et indique la date du dernier contrôle.'],
        ],
    ],
];

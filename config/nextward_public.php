<?php

return [
    'brand' => [
        'description' => 'Nextward is a job and internship platform for students and professionals around the world. It lists internships (PFE, summer), graduate programmes, first jobs and experienced roles, on site and remote, from employers and approved public sources, and says who can apply to each one.',
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
        'home' => ['title' => 'Internships and jobs worldwide | Nextward', 'description' => 'Find internships and jobs for students and professionals around the world. See who can apply, where the listing comes from and how to apply.'],
        'opportunities' => ['title' => 'Browse internships and jobs | Nextward', 'description' => 'Search internships and jobs by role, field, country, work arrangement, and experience, or show only roles open worldwide.'],
        'internships' => ['title' => 'Internships worldwide | Nextward', 'description' => 'Explore internships, PFE projects, and summer placements. Check the programme, duration, requirements, and application deadline.'],
        'jobs' => ['title' => 'Jobs worldwide | Nextward', 'description' => 'Find first jobs and experienced roles across full-time, part-time, and contract employment. Compare locations, salary, and requirements.'],
        'companies' => ['title' => 'Companies hiring in IT | Nextward', 'description' => 'Find companies hiring IT interns and professionals around the world. Search by name or city and see every open internship and job.'],
        'about' => ['title' => 'About Nextward | IT internships and jobs, made clearer', 'description' => 'Why Nextward exists, what it covers, where every listing comes from, and who builds it. IT internships and jobs around the world.'],
        'contact' => ['title' => 'Contact Nextward', 'description' => 'Contact Nextward about opportunities, source partnerships, employer hiring, or a problem with a listing.'],
        'privacy' => ['title' => 'Privacy policy | Nextward', 'description' => 'What data Nextward keeps, why, who sees it, how long it is kept, and how to delete your account. Covers Tunisian law 2004-63 and the GDPR.'],
        'terms' => ['title' => 'Terms of use | Nextward', 'description' => 'The rules for using Nextward: accounts, applications, company listings, imported listings from other sites, and what is not allowed.'],
        'blog' => ['title' => 'Career guides for students and professionals | Nextward', 'description' => 'Practical advice for finding internships and jobs, preparing applications, assessing eligibility, and hiring candidates.'],
    ],
    'fr' => [
        'home' => ['title' => 'Stages et emplois dans le monde entier | Nextward', 'description' => 'Trouvez des stages et des emplois pour étudiants et professionnels partout dans le monde. Voyez qui peut postuler, d’où vient l’offre et comment candidater.'],
        'opportunities' => ['title' => 'Consulter les stages et les emplois | Nextward', 'description' => 'Recherchez des stages et des emplois par métier, domaine, pays, mode de travail et expérience, ou seulement les offres ouvertes au monde entier.'],
        'internships' => ['title' => 'Stages dans le monde entier | Nextward', 'description' => 'Découvrez les stages, les PFE et les stages d’été. Vérifiez la durée, les compétences demandées et la date limite.'],
        'jobs' => ['title' => 'Emplois dans le monde entier | Nextward', 'description' => 'Trouvez un premier emploi ou un poste expérimenté. Comparez les lieux, la rémunération et les conditions de candidature.'],
        'companies' => ['title' => 'Entreprises qui recrutent en informatique | Nextward', 'description' => 'Trouvez les entreprises qui recrutent des stagiaires et des profils IT partout dans le monde. Recherchez par nom ou ville et voyez leurs offres.'],
        'about' => ['title' => 'À propos de Nextward | Les carrières IT en plus clair', 'description' => 'Pourquoi Nextward existe, ce qu’il couvre, d’où vient chaque offre et qui le construit. Stages et emplois IT partout dans le monde.'],
        'contact' => ['title' => 'Contacter Nextward', 'description' => 'Contactez Nextward au sujet d’une offre, d’un partenariat de source, d’un recrutement ou d’un problème avec une annonce.'],
        'privacy' => ['title' => 'Politique de confidentialité | Nextward', 'description' => 'Les données que Nextward garde, pourquoi, qui y a accès, leur durée de conservation et comment supprimer votre compte. Loi tunisienne 2004-63 et RGPD.'],
        'terms' => ['title' => 'Conditions d’utilisation | Nextward', 'description' => 'Les règles d’utilisation de Nextward : comptes, candidatures, offres des entreprises, offres importées d’autres sites et usages interdits.'],
        'blog' => ['title' => 'Guides carrière pour étudiants et professionnels | Nextward', 'description' => 'Conseils pratiques pour trouver un stage ou un emploi : PFE, CV, lettre de motivation, entretien et télétravail.'],
    ],

    // Home page questions, shown on the page and sent as FAQPage data.
    'faq' => [
        'en' => [
            ['q' => 'Do I need an account to look at opportunities?', 'a' => 'No. You can search and read every listing without an account. You need one to save roles, apply on Nextward, track your applications and use the AI tools.'],
            ['q' => 'Is Nextward free?', 'a' => 'Yes. Browsing, saving, applying, alerts and tracking are free for everyone. The AI tools are free too while we launch. Later, tailored CVs and cover letters will use paid credits, every account gets one free, and prices will be shown before anything is charged.'],
            ['q' => 'What is a PFE internship?', 'a' => 'A PFE (projet de fin d’études) is the final-year internship that many engineering and business programmes require, mostly in Tunisia and France. Use the PFE search to see only these roles.'],
            ['q' => 'What does a company see when I apply?', 'a' => 'On Nextward, the company sees your profile (headline, field and skills), the cover letter you send and your CV and files. Roles on other websites use their own forms, and opening their link does not send your CV.'],
            ['q' => 'Can I apply from another country?', 'a' => 'Turn on "Open worldwide" to see roles people in any country can apply to. Every other listing shows who can apply when the source says so. Remote does not always mean worldwide, so check with the employer when it is not stated.'],
            ['q' => 'Why is a listing in English and French?', 'a' => 'We show every listing in both languages. AI translates it from the original, without changing what the employer wrote, and the original text is always one click away under "Original listing".'],
            ['q' => 'Where do the listings come from?', 'a' => 'Some are posted by employers on Nextward. Others come from approved public sources, and each one links to the original listing and shows when it was last checked.'],
        ],
        'fr' => [
            ['q' => 'Faut-il un compte pour consulter les offres ?', 'a' => 'Non. Vous pouvez rechercher et lire toutes les offres sans compte. Il vous en faut un pour sauvegarder des offres, candidater sur Nextward, suivre vos candidatures et utiliser les outils d’IA.'],
            ['q' => 'Nextward est-il gratuit ?', 'a' => 'Oui. Consulter, sauvegarder, candidater, créer des alertes et suivre vos candidatures est gratuit pour tous. Les outils d’IA sont aussi gratuits pendant le lancement. Plus tard, les CV et lettres sur mesure utiliseront des crédits payants, chaque compte en recevra un gratuit, et les prix seront affichés avant tout paiement.'],
            ['q' => 'Qu’est-ce qu’un stage PFE ?', 'a' => 'Le PFE (projet de fin d’études) est le stage de dernière année exigé par de nombreuses écoles d’ingénieurs et de commerce, surtout en Tunisie et en France. Utilisez la recherche PFE pour n’afficher que ces offres.'],
            ['q' => 'Que voit l’entreprise quand je candidate ?', 'a' => 'Sur Nextward, l’entreprise voit votre profil (titre, domaine et compétences), la lettre que vous envoyez, ainsi que votre CV et vos fichiers. Les offres d’autres sites utilisent leur propre formulaire, et ouvrir leur lien n’envoie pas votre CV.'],
            ['q' => 'Puis-je postuler depuis un autre pays ?', 'a' => 'Activez « Ouvert au monde entier » pour voir les offres accessibles depuis n’importe quel pays. Les autres offres indiquent qui peut postuler quand la source le précise. Le télétravail n’est pas toujours ouvert à tous les pays : vérifiez auprès de l’employeur si rien n’est indiqué.'],
            ['q' => 'Pourquoi une offre est-elle en français et en anglais ?', 'a' => 'Nous affichons chaque offre dans les deux langues. L’IA la traduit depuis l’original sans changer ce que l’employeur a écrit, et le texte d’origine reste accessible sous « Offre d’origine ».'],
            ['q' => 'D’où viennent les offres ?', 'a' => 'Certaines sont publiées par les employeurs sur Nextward. Les autres viennent de sources publiques autorisées : chacune renvoie vers l’annonce originale et indique la date du dernier contrôle.'],
        ],
    ],
];

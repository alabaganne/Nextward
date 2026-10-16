<?php

return [
    'user_agent' => 'NextwardBot/1.0 (+'.rtrim(env('APP_URL', 'http://localhost'), '/').'/about)',
    'timezone' => 'Africa/Tunis',
    'max_run_seconds' => 240,
    'max_response_bytes' => 5 * 1024 * 1024,
    'max_sitemaps' => 30,
    'max_discoveries' => 50000,
    'freshness_hours' => 48,
    // New listings older than this are skipped; imported listings older than the retention window are closed.
    'max_age_days' => (int) env('OPPORTUNITY_MAX_AGE_DAYS', 30),
    'retention_days' => (int) env('OPPORTUNITY_RETENTION_DAYS', 60),
    // Title keyword groups for `opportunities:fetch --focus=`. Lowercase, no accents, regex alternatives.
    'focus' => [
        'tech' => ['include' => [
            'software', 'developer', 'developpeur', 'programmer', 'programmeur', 'full ?stack', 'front ?end', 'back ?end',
            'web', 'mobile', 'android', 'ios', 'flutter', 'react( native)?', 'angular', 'vue(js)?', 'node(js)?', 'laravel',
            'symfony', 'php', 'java', 'javascript', 'typescript', 'python', 'django', 'golang', 'kotlin', 'swift', 'ruby',
            'rails', 'asp\\.?net', 'dotnet', '\\.net', 'wordpress', 'integrateur', 'ingenieur (logiciel|developpement|informatique)',
        ], 'fields' => ['Software Engineering', 'Web Development', 'Mobile Development'], 'exclude' => [
            'business ?develop\\w*', 'commercial\\w*', 'sales', 'vente', 'marketing', 'teleconseill\\w*', 'call ?cent\\w*', 'seo', 'referenceur\\w*',
        ]],
    ],
    // Skills tagged on imported listings, matched as whole words. A plain name matches itself in any case;
    // a pattern is a regex where (?-i:...) marks words that are skills only when written that way.
    'skills' => [
        'PHP', 'Laravel', 'Symfony', 'CodeIgniter', 'JavaScript' => 'javascript|(?-i:JS|ES6)', 'TypeScript',
        'React' => 'react\\.?js|(?-i:React)(?! ?native)', 'React Native' => 'react ?native', 'Vue' => 'vue\\.?js|(?-i:Vue)(?= ?(?:[,.;:/)&\\n]|$)| (?:and|or|et|ou|3)\\b)',
        'Angular' => 'angular(?: ?js)?', 'Next.js' => 'next\\.?js', 'Node.js' => 'node\\.? ?js', 'Express.js' => 'express\\.?js', 'NestJS' => 'nest\\.?js',
        'jQuery', 'HTML' => 'html5?', 'CSS' => 'css3?', 'Sass' => 'sass|scss', 'Tailwind CSS' => 'tailwind(?: ?css)?', 'Bootstrap' => '(?-i:Bootstrap)',
        'WordPress', 'Shopify', 'Magento', 'PrestaShop', 'Drupal', 'Python', 'Django', 'Flask' => '(?-i:Flask)', 'FastAPI',
        'Java', 'Spring' => 'spring ?boot|spring (?:framework|mvc|cloud|security|batch|data)|java ?/ ?spring', 'Hibernate' => '(?-i:Hibernate)', 'Kotlin',
        'Swift' => '(?-i:Swift(?:UI)?)', 'Objective-C' => 'objective[- ]?c', 'Flutter', 'Dart' => '(?-i:Dart)', 'Android', 'iOS' => '(?-i:iOS)',
        'Ionic' => '(?-i:Ionic)', 'Xamarin', 'C#' => 'c#|c sharp', '.NET' => 'asp\\.net(?: core| mvc)?|\\.net(?: core| framework)?|dotnet',
        'C++' => 'c\\+\\+', 'Go' => 'golang|(?-i:Go)(?= ?(?:[,./)]|$)| (?:and|or|et|ou)\\b)', 'Rust' => '(?-i:Rust)', 'Ruby' => '(?-i:Ruby)(?! on)',
        'Ruby on Rails' => 'ruby on rails|(?-i:Rails)', 'Scala' => '(?-i:Scala)', 'Elixir', 'SQL' => 'sql|t-sql|pl/sql', 'PostgreSQL' => 'postgres(?:ql)?',
        'MySQL', 'MariaDB', 'SQL Server' => 'sql server|mssql', 'Oracle Database' => 'oracle (?:db|database|pl/sql|\\d+c?)', 'SQLite',
        'MongoDB' => 'mongo(?:db)?', 'Redis', 'Elasticsearch' => 'elastic ?search', 'Firebase', 'Supabase', 'NoSQL', 'GraphQL', 'gRPC',
        'REST' => '(?-i:REST)(?:ful)?|restful|rest api|api rest', 'Microservices' => 'micro-?services?', 'Kafka', 'RabbitMQ',
        'Docker', 'Kubernetes' => 'kubernetes|(?-i:K8s)', 'Terraform', 'Ansible', 'Jenkins', 'CI/CD' => 'ci ?/ ?cd', 'Git' => 'git',
        'GitHub Actions', 'GitLab CI' => 'gitlab[- ]ci', 'AWS' => '(?-i:AWS)|amazon web services', 'Azure', 'Google Cloud' => 'google cloud(?: platform)?|(?-i:GCP)',
        'Linux', 'Bash' => '(?-i:Bash)|shell scripting', 'PowerShell', 'Nginx', 'Prometheus', 'Grafana', 'VMware', 'Windows Server',
        'Active Directory', 'Cisco' => '(?-i:Cisco)|ccna|ccnp', 'Machine Learning' => 'machine learning|apprentissage automatique',
        'Deep Learning' => 'deep learning|apprentissage profond', 'LLM' => '(?-i:LLMs?)|large language models?|grands? mod[eè]les? de langage',
        'Generative AI' => 'generative ai|gen ?ai|ia g[ée]n[ée]rative', 'NLP' => '(?-i:NLP)|natural language processing',
        'Computer Vision' => 'computer vision|vision par ordinateur', 'TensorFlow', 'PyTorch', 'scikit-learn' => 'scikit[- ]?learn|sklearn',
        'Pandas' => 'pandas', 'NumPy', 'Spark' => 'pyspark|apache spark|(?-i:Spark)', 'Hadoop', 'Airflow' => '(?-i:Airflow)', 'Snowflake' => '(?-i:Snowflake)',
        'Databricks', 'ETL' => '(?-i:ETL)', 'Power BI' => 'power ?bi', 'Tableau' => '(?-i:Tableau)(?! de)', 'Looker', 'Excel' => 'microsoft excel|ms excel|(?-i:Excel)(?! (?:in|at)\\b)',
        'MATLAB', 'Selenium', 'Cypress' => '(?-i:Cypress)', 'Playwright',
        'Jest' => '(?-i:Jest)', 'JUnit', 'PHPUnit', 'Postman', 'Jira', 'Confluence' => '(?-i:Confluence)', 'Scrum',
        'Agile' => 'agile methodolog\\w*|m[ée]thod\\w* agiles?|(?-i:Agile)(?= ?/ ?scrum)', 'Figma', 'Adobe XD', 'Photoshop', 'Illustrator' => '(?-i:Illustrator)',
        'InDesign', 'UI/UX' => 'ui ?/ ?ux|ux ?/ ?ui', 'UX Research' => 'ux research|user research', 'SEO' => '(?-i:SEO)|r[ée]f[ée]rencement naturel',
        'Google Analytics', 'Google Ads', 'HubSpot', 'Salesforce', 'SAP' => '(?-i:SAP)', 'Odoo', 'Sage' => '(?-i:Sage)', 'ServiceNow', 'ITIL',
        'Camunda', 'QGIS', 'AutoCAD', 'SolidWorks', 'CATIA', 'LabVIEW', 'Arduino', 'Raspberry Pi', 'VHDL', 'FPGA', 'Unity' => '(?-i:Unity)(?: ?3D)?',
        'Unreal Engine' => '(?-i:Unreal)(?: engine)?', 'Penetration Testing' => 'penetration testing|pentest(?:ing)?|tests? d.intrusion',
        'Project Management' => 'project management|gestion de projets?', 'Business Analysis' => 'business analysis', 'IFRS' => '(?-i:IFRS)',
    ],
    'indexing_enabled' => (bool) env('GOOGLE_INDEXING_ENABLED', false),
    'google_credentials' => env('GOOGLE_INDEXING_CREDENTIALS'),
    'indexing_daily_limit' => (int) env('GOOGLE_INDEXING_DAILY_LIMIT', 200),
    // AI reads each new listing, searches the web for new sources and drafts cover letters for candidates.
    // OPPORTUNITY_AI_PROVIDER picks openai or anthropic; keys live in config/ai.php.
    'ai' => [
        'enabled' => (bool) env('OPPORTUNITY_AI_ENABLED', true),
        'provider' => env('OPPORTUNITY_AI_PROVIDER', 'openai'),
        'models' => [
            'openai' => [
                'review' => env('OPPORTUNITY_OPENAI_MODEL', 'gpt-5.4-mini'),
                'discovery' => env('OPPORTUNITY_OPENAI_DISCOVERY_MODEL', 'gpt-5.5'),
                'letter' => env('OPPORTUNITY_OPENAI_LETTER_MODEL', 'gpt-5.5'),
            ],
            'anthropic' => [
                'review' => env('OPPORTUNITY_AI_MODEL', 'claude-opus-5-5'),
                'discovery' => env('OPPORTUNITY_AI_MODEL', 'claude-opus-5-5'),
                'letter' => env('OPPORTUNITY_AI_MODEL', 'claude-opus-5-5'),
            ],
        ],
        'effort' => env('OPPORTUNITY_AI_EFFORT', 'low'),
        'discovery_effort' => env('OPPORTUNITY_AI_DISCOVERY_EFFORT', 'medium'),
    ],
    'csv_path' => env('OPPORTUNITY_CSV_PATH', 'storage/app/opportunities/found.csv'),
    'digests_enabled' => (bool) env('OPPORTUNITY_DIGESTS_ENABLED', false),
];

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
    // Nextward is for IT roles: every import keeps only listings in this focus group. Empty takes every field.
    'scope' => env('OPPORTUNITY_SCOPE', 'tech'),
    // Title keyword groups for the scope and `opportunities:fetch --focus=`. Lowercase, no accents, regex alternatives.
    'focus' => [
        'tech' => ['include' => [
            'software', 'developer', 'developpeur', 'programmer', 'programmeur', 'full ?stack', 'front ?end', 'back ?end',
            'web', 'mobile', 'android', 'ios', 'flutter', 'react( native)?', 'angular', 'vue(js)?', 'node(js)?', 'laravel',
            'symfony', 'php', 'java', 'javascript', 'typescript', 'python', 'django', 'golang', 'kotlin', 'swift', 'ruby',
            'rails', 'asp\\.?net', 'dotnet', '\\.net', 'wordpress', 'integrateur', 'ingenieur (logiciel|developpement|informatique)',
            'informatique', 'informaticien\\w*', 'it', 'data', 'machine learning', 'deep learning', 'ai', 'ia', 'ml', 'llm', 'nlp',
            'computer vision', 'bi', 'power bi', 'business intelligence', 'analytics engineer', 'database', 'dba', 'sql',
            'devops', 'devsecops', 'mlops', 'cloud', 'sre', 'site reliability', 'platform', 'infrastructure', 'kubernetes',
            'cyber\\w*', 'security', 'securite informatique', 'soc', 'pentest\\w*', 'infosec', 'network', 'reseaux?', 'telecom\\w*',
            'sysadmin', 'system administrator', 'systems? engineer', 'administrateur (systemes?|reseaux?|bases? de donnees)',
            'help ?desk', 'it support', 'support (informatique|it)', 'technicien informatique', 'qa', 'quality assurance',
            'test automation', 'testeur', 'tester', 'ux', 'ui', 'product designer', 'embedded', 'embarque\\w*', 'firmware', 'iot',
        ], 'fields' => [
            'Software Engineering', 'Web Development', 'Mobile Development', 'Data Science & AI', 'DevOps & Cloud', 'Cybersecurity',
            'Network & Systems', 'UI/UX Design', 'Embedded Systems', 'Business Intelligence',
        ], 'exclude' => [
            'business ?develop\\w*', 'commercial\\w*', 'sales', 'vente', 'marketing', 'teleconseill\\w*', 'call ?cent\\w*', 'seo', 'referenceur\\w*',
            'data ?entry', 'saisie', 'security (guard|officer)', 'agent de securite',
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
        'UI/UX' => 'ui ?/ ?ux|ux ?/ ?ui', 'UX Research' => 'ux research|user research', 'SEO' => '(?-i:SEO)|r[ée]f[ée]rencement naturel',
        'Google Analytics', 'Salesforce', 'SAP' => '(?-i:SAP)', 'Odoo', 'ServiceNow', 'ITIL',
        'Camunda', 'QGIS', 'LabVIEW', 'Arduino', 'Raspberry Pi', 'VHDL', 'FPGA', 'Unity' => '(?-i:Unity)(?: ?3D)?',
        'Unreal Engine' => '(?-i:Unreal)(?: engine)?', 'Penetration Testing' => 'penetration testing|pentest(?:ing)?|tests? d.intrusion',
        'Project Management' => 'project management|gestion de projets?', 'Business Analysis' => 'business analysis',
        // Front end and back end
        'Svelte', 'SvelteKit' => 'svelte ?kit', 'Nuxt' => 'nuxt(?:\\.?js)?', 'Redux', 'Webpack', 'Vite' => 'vite\\.?js|(?-i:Vite)(?= ?(?:[,/)]| (?:and|or|et|ou)\\b))', 'Three.js' => 'three\\.js',
        'Alpine.js' => 'alpine\\.?js', 'HTMX', 'Inertia.js' => 'inertia\\.?js', 'Livewire' => '(?-i:Livewire)', 'Storybook' => '(?-i:Storybook)',
        'Quarkus', 'Micronaut', 'Gradle', 'Maven' => '(?-i:Maven)', 'Deno' => '(?-i:Deno)', 'Bun' => '(?-i:Bun)(?= ?(?:[,/)]| (?:and|or|et|ou)\\b))',
        'Keycloak', 'WebSockets' => 'web ?sockets?', 'WebRTC', 'OAuth' => 'oauth ?2?(?:\\.0)?', 'JWT' => '(?-i:JWT)', 'OpenAPI' => 'openapi|swagger',
        'SOAP' => '(?-i:SOAP)', 'Protobuf' => 'protobuf|protocol buffers', 'Strapi', 'Haskell', 'Clojure', 'F#' => 'f#', 'Perl' => '(?-i:Perl)',
        'Lua' => '(?-i:Lua)', 'COBOL' => '(?-i:COBOL)', 'Assembly' => 'assembly language|langage assembleur', 'Solidity', 'Blockchain', 'Web3' => 'web ?3',
        // Mobile and desktop
        'Jetpack Compose' => 'jetpack compose', 'SwiftUI' => '(?-i:SwiftUI)', 'Kotlin Multiplatform' => 'kotlin multiplatform|(?-i:KMP)',
        'Expo' => 'expo (?:go|router|sdk|eas)|react native ?/ ?expo|(?-i:Expo)(?= ?(?:[,/)]| (?:and|or|et|ou)\\b))', 'Electron' => 'electron\\.?js|(?-i:Electron)',
        'Godot',
        // Data and AI
        'BigQuery' => 'big ?query', 'Redshift' => '(?-i:Redshift)', 'dbt' => '(?-i:dbt)', 'Cassandra' => '(?-i:Cassandra)', 'DynamoDB' => 'dynamo ?db',
        'Neo4j', 'ClickHouse' => 'click ?house', 'Keras', 'OpenCV', 'XGBoost', 'MLflow' => 'ml ?flow', 'MLOps' => 'ml ?ops', 'LangChain' => 'lang ?chain',
        'LlamaIndex' => 'llama ?index', 'Hugging Face' => 'hugging ?face', 'RAG' => '(?-i:RAG)|retrieval[- ]augmented generation',
        'OpenAI API' => 'openai(?: api)?', 'Prompt Engineering' => 'prompt engineering',
        // Cloud, DevOps and IT
        'Helm' => 'helm charts?|(?-i:Helm)', 'Argo CD' => 'argo ?cd', 'Pulumi', 'CloudFormation' => 'cloud ?formation', 'AWS Lambda' => 'aws lambda',
        'Serverless', 'OpenShift' => 'open ?shift', 'Istio', 'Datadog' => 'data ?dog', 'Sentry' => '(?-i:Sentry)', 'OpenTelemetry' => 'open ?telemetry',
        'ELK' => '(?-i:ELK)|elastic stack', 'Kibana', 'Logstash', 'SonarQube' => 'sonar ?qube', 'Azure DevOps' => 'azure devops', 'Bitbucket',
        'Vagrant' => '(?-i:Vagrant)', 'Puppet' => '(?-i:Puppet)', 'Zabbix', 'Nagios', 'Proxmox', 'Hyper-V' => 'hyper-?v', 'Cloudflare', 'Vercel',
        'Netlify', 'Heroku', 'DigitalOcean' => 'digital ?ocean', 'TCP/IP' => 'tcp ?/ ?ip', 'DNS' => '(?-i:DNS)', 'VPN' => '(?-i:VPN)',
        'Microsoft 365' => 'microsoft 365|office 365|(?-i:M365|O365)', 'Intune', 'SharePoint' => 'share ?point', 'Power Automate' => 'power automate',
        'Power Apps' => 'power ?apps', 'Dynamics 365' => 'dynamics 365',
        // Security
        'Splunk', 'SIEM' => '(?-i:SIEM)', 'SOC' => '(?-i:SOC)', 'OWASP', 'Burp Suite' => 'burp suite', 'Metasploit', 'Wireshark', 'Nmap',
        'Kali Linux' => 'kali linux', 'Fortinet' => 'fortinet|fortigate', 'pfSense', 'Firewalls' => 'firewalls?|pare-feux?', 'ISO 27001' => 'iso ?/? ?(?:iec )?27001',
        'IAM' => '(?-i:IAM)', 'Okta' => '(?-i:Okta)', 'Zero Trust' => 'zero ?trust', 'CISSP' => '(?-i:CISSP)', 'CEH' => '(?-i:CEH)', 'OSCP' => '(?-i:OSCP)',
        // Embedded and IoT
        'Embedded C' => 'embedded c(?![+#])|c embarqu[ée]', 'RTOS' => '(?-i:RTOS)|free ?rtos', 'STM32' => 'stm32\\w*', 'ESP32' => 'esp32|esp8266', 'Yocto',
        'Embedded Linux' => 'embedded linux|linux embarqu[ée]', 'MQTT', 'IoT' => '(?-i:IoT)|internet of things|internet des objets',
        'CAN bus' => 'can ?bus|(?-i:CAN) protocol',
        // Testing
        'Appium', 'JMeter', 'k6' => '(?-i:k6)', 'Pytest', 'Vitest', 'Mockito', 'Cucumber' => '(?-i:Cucumber)', 'TestNG', 'Robot Framework' => 'robot framework',
        'SoapUI' => 'soap ?ui', 'TDD' => '(?-i:TDD)|test[- ]driven development', 'BDD' => '(?-i:BDD)|behaviou?r[- ]driven development',
    ],
    'indexing_enabled' => (bool) env('GOOGLE_INDEXING_ENABLED', false),
    'google_credentials' => env('GOOGLE_INDEXING_CREDENTIALS'),
    'indexing_daily_limit' => (int) env('GOOGLE_INDEXING_DAILY_LIMIT', 200),
    // AI reads each new listing, searches the web for new sources and drafts cover letters for candidates.
    // OPPORTUNITY_AI_PROVIDER picks openai or anthropic; keys live in config/ai.php.
    'ai' => [
        'enabled' => (bool) env('OPPORTUNITY_AI_ENABLED', true),
        // Writes an English and a French version of each published listing.
        'translate' => (bool) env('OPPORTUNITY_AI_TRANSLATE', true),
        'provider' => env('OPPORTUNITY_AI_PROVIDER', 'openai'),
        'models' => [
            'openai' => [
                'review' => env('OPPORTUNITY_OPENAI_MODEL', 'gpt-5.4-mini'),
                'discovery' => env('OPPORTUNITY_OPENAI_DISCOVERY_MODEL', 'gpt-5.5'),
                'letter' => env('OPPORTUNITY_OPENAI_LETTER_MODEL', 'gpt-5.5'),
            ],
            'anthropic' => [
                'review' => env('OPPORTUNITY_AI_REVIEW_MODEL', 'claude-sonnet-5-5'),
                'discovery' => env('OPPORTUNITY_AI_MODEL', 'claude-opus-5-5'),
                'letter' => env('OPPORTUNITY_AI_MODEL', 'claude-opus-5-5'),
            ],
        ],
        // Listings read per AI call; one call for several listings costs far less than one each.
        'batch_size' => (int) env('OPPORTUNITY_AI_BATCH', 10),
        // Translation packs listings into one call up to this many characters of source text.
        'translate_batch_chars' => (int) env('OPPORTUNITY_AI_TRANSLATE_BATCH_CHARS', 12000),
        'effort' => env('OPPORTUNITY_AI_EFFORT', 'low'),
        'discovery_effort' => env('OPPORTUNITY_AI_DISCOVERY_EFFORT', 'medium'),
    ],
    'csv_path' => env('OPPORTUNITY_CSV_PATH', 'storage/app/opportunities/found.csv'),
    'digests_enabled' => (bool) env('OPPORTUNITY_DIGESTS_ENABLED', false),
];

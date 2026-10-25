<?php

// Job leads: a daily list of roles for the owner, never the same job twice and never one already applied to.
return [
    // Job board listings (Himalayas, Remote OK, Remotive, We Work Remotely, Hacker News) are only read when the title,
    // lowercased and without accents, matches this, and only while they are this many days old.
    'board_titles' => '/\\b(?:front.?end|back.?end|full.?stack|software|web|react|next\\.?js|vue|angular|typescript|javascript|node|nest|laravel|php|python|fastapi|django|ai|llm|ml|machine learning|mobile|react native|product engineer|developer|engineer)\\b/',
    'board_max_age_days' => 3,
    // The daily run: listings first seen in the last few days, at most this many new leads a day.
    'lookback_days' => 3,
    'daily_cap' => (int) env('LEADS_DAILY_CAP', 12),
    // Least points for the role and its skills alone; remote and fresh roles get more on top.
    'min_score' => 4,
    // A job first posted longer ago than this is a repost, even when its board shows a new date.
    'repost_days' => 30,
    'duplicate_days' => 90,
    'similar_title' => 0.85,
    // After applying at a company, its other roles stay hidden this long ('all') or only roles like the one applied to ('similar').
    'cooldown_days' => 60,
    'cooldown' => 'all',
    'max_years' => 8,
    // Levels left out: junior and internships below, staff and above.
    'skip_titles' => '/\b(?:intern(?:ship)?|junior|jr|graduate|trainee|apprentice|working student|entry level|staff|principal|director|vp|vice president|head of|chief|cto)\b/',
    // Weight of each kind of role, in this order of preference; a matching title counts double.
    'groups' => [
        'frontend' => ['label' => 'Frontend', 'weight' => 3, 'title' => '/\b(?:front.?end|react|next\.?js|vue|ui engineer|web developer|javascript|typescript)\b/',
            'skills' => ['React', 'Next.js', 'TypeScript', 'React Native', 'Vue', 'JavaScript', 'Tailwind']],
        'fullstack' => ['label' => 'Full stack', 'weight' => 2, 'title' => '/\b(?:full.?stack|product engineer|software engineer|software developer)\b/',
            'skills' => ['Node.js', 'NestJS', 'FastAPI', 'Laravel', 'PostgreSQL', 'Supabase', 'GraphQL']],
        'ai' => ['label' => 'Applied AI', 'weight' => 2, 'title' => '/\b(?:ai|llm|genai|machine learning|ml)\b/',
            'skills' => ['LLM', 'RAG', 'OpenAI', 'LangChain', 'embeddings', 'AI agents']],
        'backend' => ['label' => 'Backend', 'weight' => 1, 'title' => '/\b(?:back.?end|api|platform engineer)\b/',
            'skills' => ['Python', 'PHP', 'Django', 'Redis', 'Kafka', 'microservices']],
    ],
    'eligibility_points' => ['remote' => 4, 'visa' => 2, 'unclear' => 0],
    // Sends the day's leads by email to the owner after the daily run.
    'digest_email' => (bool) env('LEADS_DIGEST_EMAIL', false),
    // Other names companies go by; both sides are compared without case, punctuation or legal suffixes.
    'aliases' => [
        'Tether Operations Limited' => 'Tether',
        'Notion Labs' => 'Notion',
    ],
];

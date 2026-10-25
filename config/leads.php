<?php

// Job leads: a daily list of roles for the owner, never the same job twice and never one already applied to.
return [
    // Job board listings (Himalayas, Remote OK, Remotive, We Work Remotely, Hacker News) are only read when the title,
    // lowercased and without accents, matches this, and only while they are this many days old.
    'board_titles' => '/\\b(?:front.?end|back.?end|full.?stack|software|web|react|next\\.?js|vue|angular|typescript|javascript|node|nest|laravel|php|python|fastapi|django|ai|llm|ml|machine learning|mobile|react native|product engineer|developer|engineer)\\b/',
    'board_max_age_days' => 3,
    // Other names companies go by; both sides are compared without case, punctuation or legal suffixes.
    'aliases' => [
        'Tether Operations Limited' => 'Tether',
        'Notion Labs' => 'Notion',
    ],
];

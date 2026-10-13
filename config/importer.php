<?php

return [
    'user_agent' => 'NextwardBot/1.0 (+'.rtrim(env('APP_URL', 'http://localhost'), '/').'/about)',
    'timezone' => 'Africa/Tunis',
    'batch_size' => 40,
    'max_run_seconds' => 240,
    'max_response_bytes' => 5 * 1024 * 1024,
    'max_sitemaps' => 30,
    'max_discoveries' => 50000,
    'freshness_hours' => 48,
    'indexing_enabled' => (bool) env('GOOGLE_INDEXING_ENABLED', false),
    'google_credentials' => env('GOOGLE_INDEXING_CREDENTIALS'),
    'indexing_daily_limit' => (int) env('GOOGLE_INDEXING_DAILY_LIMIT', 200),
    'digests_enabled' => (bool) env('OPPORTUNITY_DIGESTS_ENABLED', false),
];

<?php

return [
    'log_path' => env('SECURITY_LOG_PATH', storage_path('logs/laravel.log')),

    'schedule_frequency' => env('SECURITY_SCHEDULE_FREQUENCY', 'everyMinute'),

    'confidence_threshold' => (float) env('SECURITY_CONFIDENCE_THRESHOLD', 0.85),

    'block_ttl_minutes' => (int) env('SECURITY_BLOCK_TTL_MINUTES', 60),

    'admin_emails' => array_filter(explode(',', env('SECURITY_ADMIN_EMAILS', ''))),

    'anthropic_api_key' => env('ANTHROPIC_API_KEY'),

    'anthropic_model' => env('ANTHROPIC_MODEL', 'claude-sonnet-4-6'),
];

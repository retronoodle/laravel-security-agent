<?php

return [

    'model' => env('LSA_MODEL', 'claude-sonnet-4-6'),

    'admin' => [
        'enabled' => (bool) env('LSA_ADMIN_ENABLED', true),

        'password' => env('LSA_ADMIN_PASSWORD'),

        'path' => env('LSA_ADMIN_PATH', 'lsa-admin'),

        'available_models' => [
            'claude-haiku-4-5-20251001',
            'claude-sonnet-4-6',
            'claude-opus-4-7',
        ],
    ],

];

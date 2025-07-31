<?php

// config/cors.php - FIXED VERSION

return [
    'paths' => ['api/*', 'broadcasting/auth'],

    'allowed_methods' => ['*'],

    'allowed_origins' => env('APP_ENV') === 'production' ? [
        env('APP_URL', 'https://potunakos.my.id'),
        'https://potunakos.my.id',
        'https://www.potunakos.my.id',
    ] : [
        'http://localhost:3000',
        'http://127.0.0.1:3000',
        'http://localhost:5173',
        'http://127.0.0.1:5173',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false, // ✅ FIXED: false untuk token mode

];

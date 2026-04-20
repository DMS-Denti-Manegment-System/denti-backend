<?php

// config/cors.php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        env('FRONTEND_URL', 'http://localhost:3000'),
        'http://127.0.0.1:3000',
        'http://localhost:5173', // Vite default
        'http://127.0.0.1:5173',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // DİKKAT 2: Burası kesinlikle 'true' olmalı
    'supports_credentials' => true,
];
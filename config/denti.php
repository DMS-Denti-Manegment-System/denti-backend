<?php

return [
    /*
    |--------------------------------------------------------------------------
    | System Configuration
    |--------------------------------------------------------------------------
    |
    | Denti is deployed as a single-tenant application.
    |
    */
    'system' => [
        'name' => env('DENTI_SYSTEM_NAME', 'Denti Klinik'),
        'domain' => env('DENTI_SYSTEM_DOMAIN', 'local'),
        'email' => env('DENTI_SYSTEM_EMAIL'),
        'max_users' => (int) env('DENTI_SYSTEM_MAX_USERS', 25),
    ],

    'owner' => [
        'name' => env('DENTI_OWNER_NAME', 'Klinik Yetkilisi'),
        'username' => env('DENTI_OWNER_USERNAME', 'admin'),
        'email' => env('DENTI_OWNER_EMAIL', 'admin@denti.local'),
        'password' => env('DENTI_OWNER_PASSWORD', 'admin12345'),
    ],
];

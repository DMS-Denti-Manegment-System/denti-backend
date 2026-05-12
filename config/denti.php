<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Deployment Model
    |--------------------------------------------------------------------------
    |
    | Denti is deployed as a single-company application. The company record is
    | kept as an internal boundary for permissions, indexes and data ownership;
    | users should not create or switch companies from the product UI.
    |
    */
    'deployment_model' => env('DENTI_DEPLOYMENT_MODEL', 'single_company'),

    'company' => [
        'name' => env('DENTI_COMPANY_NAME', 'Denti Klinik'),
        'code' => env('DENTI_COMPANY_CODE', 'default'),
        'domain' => env('DENTI_COMPANY_DOMAIN', 'local'),
        'email' => env('DENTI_COMPANY_EMAIL'),
        'max_users' => (int) env('DENTI_COMPANY_MAX_USERS', 25),
    ],

    'owner' => [
        'name' => env('DENTI_OWNER_NAME', 'Klinik Yetkilisi'),
        'username' => env('DENTI_OWNER_USERNAME', 'admin'),
        'email' => env('DENTI_OWNER_EMAIL', 'admin@denti.local'),
        'password' => env('DENTI_OWNER_PASSWORD', 'admin12345'),
    ],
];

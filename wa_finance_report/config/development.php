<?php

return [
    'super_admin' => [
        'name' => env('SUPER_ADMIN_NAME', 'Super Admin'),
        'email' => env('SUPER_ADMIN_EMAIL', 'admin@example.com'),
        'password' => env('SUPER_ADMIN_PASSWORD'),
    ],
    'demo_user' => [
        'name' => env('DEMO_USER_NAME', 'Demo User'),
        'email' => env('DEMO_USER_EMAIL', 'demo@example.com'),
        'password' => env('DEMO_USER_PASSWORD'),
    ],
];

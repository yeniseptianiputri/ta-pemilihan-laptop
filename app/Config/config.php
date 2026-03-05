<?php

declare(strict_types=1);

return [
    'app_name' => env('APP_NAME', 'SPK Pemilihan Laptop'),
    'db' => [
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => (int)(env('DB_PORT', '3306') ?? '3306'),
        'name' => env('DB_NAME', 'spk_laptop'),
        'user' => env('DB_USER', 'root'),
        'password' => env('DB_PASS', ''),
        'charset' => env('DB_CHARSET', 'utf8mb4'),
    ],
    'auth' => [
        'admin_email' => env('ADMIN_EMAIL', 'admin@laptop.local'),
        'admin_password' => env('ADMIN_PASSWORD', 'admin123'),
        'cashier_email' => env('CASHIER_EMAIL', 'cashier@laptop.local'),
        'cashier_password' => env('CASHIER_PASSWORD', 'cashier123'),
        'default_user_email' => env('USER_EMAIL', 'user@laptop.local'),
        'default_user_password' => env('USER_PASSWORD', 'user123'),
    ],
    'openai' => [
        'api_key' => env('OPENAI_API_KEY', ''),
        'model' => env('OPENAI_MODEL', 'gpt-4.1-mini'),
    ],
];

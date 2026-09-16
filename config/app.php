<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'SupportFlow'),
    'environment' => env('APP_ENV', 'production'),
    'debug' => filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOL),
    'url' => rtrim((string) env('APP_URL', 'http://localhost'), '/'),
    'timezone' => env('APP_TIMEZONE', 'UTC'),
];

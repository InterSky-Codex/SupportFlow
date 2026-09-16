<?php

declare(strict_types=1);

use App\Core\Environment;

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        return Environment::get($key, $default);
    }
}

if (!function_exists('e')) {
    function e(string|int|float|null $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

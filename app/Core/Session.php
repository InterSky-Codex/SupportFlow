<?php

declare(strict_types=1);

namespace App\Core;

final class Session
{
    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public function flash(string $key, string $message): void
    {
        $_SESSION['_flash'][$key] = $message;
    }

    public function consumeFlash(string $key): ?string
    {
        $message = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);

        return is_string($message) ? $message : null;
    }
}

<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public function __construct(private readonly Session $session)
    {
    }

    public function token(): string
    {
        $token = $this->session->get(self::SESSION_KEY);

        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            $this->session->put(self::SESSION_KEY, $token);
        }

        return $token;
    }

    public function validate(?string $token): void
    {
        $storedToken = $this->session->get(self::SESSION_KEY);

        if (!is_string($token) || !is_string($storedToken) || !hash_equals($storedToken, $token)) {
            throw new RuntimeException('Your session has expired. Please try again.');
        }
    }
}

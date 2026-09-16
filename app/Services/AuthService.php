<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Session;
use App\Models\User;

final class AuthService
{
    private const SESSION_KEY = 'authenticated_user_id';

    public function __construct(
        private readonly User $users,
        private readonly Session $session,
    ) {
    }

    /** @return array<string, mixed>|null */
    public function attempt(string $email, string $password): ?array
    {
        $user = $this->users->findByEmail($email);

        if ($user === null || !(bool) $user['is_active'] || !password_verify($password, $user['password'])) {
            return null;
        }

        if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
            $this->users->updatePassword((int) $user['id'], password_hash($password, PASSWORD_DEFAULT));
        }

        $this->login((int) $user['id']);
        unset($user['password']);

        return $user;
    }

    public function register(string $name, string $email, string $password): void
    {
        $this->users->create($name, $email, password_hash($password, PASSWORD_DEFAULT));
    }

    public function check(): bool
    {
        return is_int($this->session->get(self::SESSION_KEY));
    }

    /** @return array<string, mixed>|null */
    public function user(): ?array
    {
        $userId = $this->session->get(self::SESSION_KEY);

        if (!is_int($userId)) {
            return null;
        }

        $user = $this->users->findById($userId);

        if ($user === null || !(bool) $user['is_active']) {
            $this->session->forget(self::SESSION_KEY);
            return null;
        }

        return $user;
    }

    public function logout(): void
    {
        $this->session->forget(self::SESSION_KEY);
        $this->session->forget('_csrf_token');
        $this->session->regenerate();
    }

    private function login(int $userId): void
    {
        $this->session->regenerate();
        $this->session->put(self::SESSION_KEY, $userId);
    }
}

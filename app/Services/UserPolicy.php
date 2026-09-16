<?php

declare(strict_types=1);

namespace App\Services;

final class UserPolicy
{
    /** @param array<string, mixed> $user */
    public function canManage(array $user): bool
    {
        return ($user['role'] ?? '') === 'administrator';
    }

    /** @param array<string, mixed> $user */
    public function canView(array $user): bool
    {
        return $this->canManage($user);
    }

    /** @param array<string, mixed> $user */
    public function canCreate(array $user): bool
    {
        return $this->canManage($user);
    }

    /** @param array<string, mixed> $user */
    public function canEdit(array $user): bool
    {
        return $this->canManage($user);
    }

    /** @param array<string, mixed> $user */
    public function canResetPassword(array $user): bool
    {
        return $this->canManage($user);
    }

    /** @param array<string, mixed> $user */
    public function canActivate(array $user): bool
    {
        return $this->canManage($user);
    }

    /** @param array<string, mixed> $user */
    public function canDeactivate(array $user): bool
    {
        return $this->canManage($user);
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

final class ReportPolicy
{
    /** @param array<string, mixed> $user */
    public function canView(array $user): bool
    {
        return ($user['role'] ?? '') === 'administrator';
    }
}

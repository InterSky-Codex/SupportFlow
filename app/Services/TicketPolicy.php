<?php

declare(strict_types=1);

namespace App\Services;

final class TicketPolicy
{
    public const SCOPE_GLOBAL = 'global';
    public const SCOPE_CREATOR = 'creator';
    public const SCOPE_ASSIGNEE = 'assignee';

    /** @param array<string, mixed> $user */
    public function listScope(array $user): string
    {
        return match ($user['role']) {
            'administrator' => self::SCOPE_GLOBAL,
            'technician' => self::SCOPE_ASSIGNEE,
            default => self::SCOPE_CREATOR,
        };
    }

    /** @param array<string, mixed> $user */
    public function canCreate(array $user): bool
    {
        return in_array($user['role'], ['employee', 'technician', 'administrator'], true);
    }

    /** @param array<string, mixed> $user @param array<string, mixed> $ticket */
    public function canView(array $user, array $ticket): bool
    {
        return match ($user['role']) {
            'administrator' => true,
            'technician' => (int) $ticket['assigned_to'] === (int) $user['id'] || (int) $ticket['created_by'] === (int) $user['id'],
            default => (int) $ticket['created_by'] === (int) $user['id'],
        };
    }

    /** @param array<string, mixed> $user @param array<string, mixed> $ticket */
    public function canEdit(array $user, array $ticket): bool
    {
        if ($ticket['status_name'] === 'Closed') {
            return $user['role'] === 'administrator';
        }

        return $this->canView($user, $ticket);
    }

    /** @param array<string, mixed> $user */
    public function canAssign(array $user): bool
    {
        return $user['role'] === 'administrator';
    }

    /** @param array<string, mixed> $user @param array<string, mixed> $ticket */
    public function canTransition(array $user, array $ticket): bool
    {
        return $user['role'] === 'administrator'
            || ($user['role'] === 'technician' && (int) $ticket['assigned_to'] === (int) $user['id']);
    }
}

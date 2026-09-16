<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use InvalidArgumentException;

final class AuditService
{
    public const ACTION_AUTH_LOGIN = 'auth.login';
    public const ACTION_AUTH_LOGOUT = 'auth.logout';
    public const ACTION_AUTH_REGISTER = 'auth.register';

    public const ACTION_TICKET_CREATED = 'ticket.created';
    public const ACTION_TICKET_UPDATED = 'ticket.updated';
    public const ACTION_TICKET_ASSIGNED = 'ticket.assigned';
    public const ACTION_TICKET_STATUS_CHANGED = 'ticket.status_changed';
    public const ACTION_TICKET_DELETED = 'ticket.deleted';

    public const ACTION_COMMENT_CREATED = 'comment.created';
    public const ACTION_COMMENT_DELETED = 'comment.deleted';

    public const ACTION_ATTACHMENT_UPLOADED = 'attachment.uploaded';
    public const ACTION_ATTACHMENT_DELETED = 'attachment.deleted';

    public const ACTION_USER_CREATED = 'user.created';
    public const ACTION_USER_UPDATED = 'user.updated';
    public const ACTION_USER_ACTIVATED = 'user.activated';
    public const ACTION_USER_DEACTIVATED = 'user.deactivated';
    public const ACTION_USER_PASSWORD_RESET = 'user.password_reset';

    public const ACTION_SETTINGS_UPDATED = 'settings.updated';

    private const SENSITIVE_KEYS = [
        'password',
        'password_hash',
        'password_confirmation',
        'token',
        'access_token',
        'refresh_token',
        'csrf_token',
        '_token',
        'session_id',
        'cookie',
        'secret',
        'api_key',
        'authorization',
    ];

    public function __construct(private readonly AuditLog $auditLogs)
    {
    }

    /**
     * Records an audit log entry.
     * 
     * @param array<string, mixed> $metadata
     */
    public function log(
        string $action,
        ?int $actorId,
        string $description,
        string $entityType,
        ?int $entityId = null,
        ?int $ticketId = null,
        array $metadata = [],
        ?string $ip = null,
        ?string $userAgent = null
    ): int {
        $action = trim($action);
        if ($action === '') {
            throw new InvalidArgumentException('Action cannot be empty.');
        }
        if (mb_strlen($action) > 50) {
            throw new InvalidArgumentException('Action must not exceed 50 characters.');
        }

        $entityType = trim($entityType);
        if ($entityType === '') {
            throw new InvalidArgumentException('Entity type cannot be empty.');
        }
        if (mb_strlen($entityType) > 50) {
            throw new InvalidArgumentException('Entity type must not exceed 50 characters.');
        }

        $description = trim($description);
        if ($description === '') {
            throw new InvalidArgumentException('Description cannot be empty.');
        }
        if (mb_strlen($description) > 255) {
            $description = mb_substr($description, 0, 252) . '...';
        }

        $sanitizedMetadata = $this->sanitizeMetadata($metadata);

        $normalizedIp = null;
        if ($ip !== null) {
            $ip = trim($ip);
            if ($ip !== '') {
                $normalizedIp = mb_substr($ip, 0, 45);
            }
        }

        $normalizedUserAgent = null;
        if ($userAgent !== null) {
            $userAgent = trim($userAgent);
            if ($userAgent !== '') {
                $normalizedUserAgent = mb_substr($userAgent, 0, 255);
            }
        }

        return $this->auditLogs->create([
            'actor_id' => $actorId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'ticket_id' => $ticketId,
            'description' => $description,
            'metadata' => $sanitizedMetadata === [] ? null : $sanitizedMetadata,
            'ip_address' => $normalizedIp,
            'user_agent' => $normalizedUserAgent,
        ]);
    }

    /**
     * Recursively strips sensitive credentials/keys from metadata.
     * 
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function sanitizeMetadata(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            $normalizedKey = strtolower((string) $key);
            if (in_array($normalizedKey, self::SENSITIVE_KEYS, true)) {
                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeMetadata($value);
            } elseif (is_scalar($value) || $value === null) {
                $sanitized[$key] = $value;
            }
            // Objects/resources are safely omitted
        }

        return $sanitized;
    }

    /**
     * Returns chronological audit history for a specific ticket.
     * 
     * @return list<array<string, mixed>>
     */
    public function getTimelineForTicket(int $ticketId): array
    {
        return $this->auditLogs->findByTicketId($ticketId);
    }

    /**
     * Returns recent activities for dashboard widgets.
     * 
     * @return list<array<string, mixed>>
     */
    public function getRecentActivities(int $limit = 10, ?string $scope = null, ?int $userId = null): array
    {
        return $this->auditLogs->findRecent($limit, $scope, $userId);
    }

    /**
     * Paginated audit logs for administration.
     * 
     * @param array<string, mixed> $filters
     * @return array{
     *     items: list<array<string, mixed>>,
     *     total: int,
     *     page: int,
     *     per_page: int,
     *     total_pages: int
     * }
     */
    public function paginate(array $filters, int $page = 1, int $perPage = 15): array
    {
        return $this->auditLogs->paginate($filters, $page, $perPage);
    }
}

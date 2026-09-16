<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use InvalidArgumentException;
use PDO;

final class AuditLog
{
    public function __construct(private readonly Database $database)
    {
    }

    /**
     * Inserts a single audit log record.
     * 
     * @param array{
     *     actor_id?: int|null,
     *     action: string,
     *     entity_type: string,
     *     entity_id?: int|null,
     *     ticket_id?: int|null,
     *     description: string,
     *     metadata?: array<string, mixed>|string|null,
     *     ip_address?: string|null,
     *     user_agent?: string|null
     * } $data
     */
    public function create(array $data): int
    {
        $action = trim((string) ($data['action'] ?? ''));
        $entityType = trim((string) ($data['entity_type'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));

        if ($action === '') {
            throw new InvalidArgumentException('Action is required.');
        }

        if ($entityType === '') {
            throw new InvalidArgumentException('Entity type is required.');
        }

        if ($description === '') {
            throw new InvalidArgumentException('Description is required.');
        }

        $metadata = $data['metadata'] ?? null;
        $encodedMetadata = null;
        if (is_array($metadata)) {
            $encodedMetadata = json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } elseif (is_string($metadata) && $metadata !== '') {
            $encodedMetadata = $metadata;
        }

        $connection = $this->connection();
        $statement = $connection->prepare(
            'INSERT INTO audit_logs (
                actor_id, action, entity_type, entity_id, ticket_id, 
                description, metadata, ip_address, user_agent
            ) VALUES (
                :actor_id, :action, :entity_type, :entity_id, :ticket_id, 
                :description, :metadata, :ip_address, :user_agent
            )'
        );

        $statement->execute([
            'actor_id' => $data['actor_id'] ?? null,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $data['entity_id'] ?? null,
            'ticket_id' => $data['ticket_id'] ?? null,
            'description' => $description,
            'metadata' => $encodedMetadata,
            'ip_address' => $data['ip_address'] ?? null,
            'user_agent' => $data['user_agent'] ?? null,
        ]);

        return (int) $connection->lastInsertId();
    }

    /**
     * Returns chronological audit history for a specific ticket (oldest first).
     * 
     * @return list<array<string, mixed>>
     */
    public function findByTicketId(int $ticketId): array
    {
        $statement = $this->connection()->prepare(
            'SELECT a.id, a.actor_id, a.action, a.entity_type, a.entity_id, a.ticket_id,
                    a.description, a.metadata, a.ip_address, a.user_agent, a.created_at,
                    u.name AS actor_name, u.role AS actor_role
             FROM audit_logs a
             LEFT JOIN users u ON u.id = a.actor_id
             WHERE a.ticket_id = :ticket_id
             ORDER BY a.created_at ASC, a.id ASC'
        );
        $statement->execute(['ticket_id' => $ticketId]);
        $rows = $statement->fetchAll();

        return array_map([$this, 'formatRow'], $rows);
    }

    /**
     * Returns recent audit records (newest first) for dashboard or activity widgets.
     * 
     * @return list<array<string, mixed>>
     */
    public function findRecent(int $limit = 10, ?string $scope = null, ?int $userId = null): array
    {
        $limit = max(1, min(50, $limit));

        $conditions = [];
        $parameters = [];

        if ($scope !== null && $userId !== null && $scope !== 'administrator' && $scope !== 'admin') {
            if ($scope === 'assignee' || $scope === 'technician') {
                $conditions[] = '(a.actor_id = :scope_actor_id OR t.assigned_to = :scope_assigned_to OR t.created_by = :scope_created_by)';
                $parameters['scope_actor_id'] = $userId;
                $parameters['scope_assigned_to'] = $userId;
                $parameters['scope_created_by'] = $userId;
            } elseif ($scope === 'creator' || $scope === 'employee') {
                $conditions[] = '(a.actor_id = :scope_actor_id OR t.created_by = :scope_created_by)';
                $parameters['scope_actor_id'] = $userId;
                $parameters['scope_created_by'] = $userId;
            }
        }

        $where = $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions);

        $sql = 'SELECT a.id, a.actor_id, a.action, a.entity_type, a.entity_id, a.ticket_id,
                       a.description, a.metadata, a.ip_address, a.user_agent, a.created_at,
                       u.name AS actor_name, u.role AS actor_role,
                       t.ticket_number
                FROM audit_logs a
                LEFT JOIN users u ON u.id = a.actor_id
                LEFT JOIN tickets t ON t.id = a.ticket_id'
                . $where
                . ' ORDER BY a.created_at DESC, a.id DESC LIMIT :limit';

        $statement = $this->connection()->prepare($sql);
        foreach ($parameters as $key => $value) {
            $statement->bindValue(':' . $key, $value);
        }
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        $rows = $statement->fetchAll();

        return array_map([$this, 'formatRow'], $rows);
    }

    /**
     * Paginated audit logs with filtering for administrative views.
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
    public function paginate(array $filters, int $page, int $perPage): array
    {
        $perPage = max(1, min(100, $perPage));
        $page = max(1, $page);

        $conditions = [];
        $parameters = [];

        $action = trim((string) ($filters['action'] ?? ''));
        if ($action !== '') {
            $conditions[] = 'a.action = :action';
            $parameters['action'] = $action;
        }

        $entityType = trim((string) ($filters['entity_type'] ?? ''));
        if ($entityType !== '') {
            $conditions[] = 'a.entity_type = :entity_type';
            $parameters['entity_type'] = $entityType;
        }

        $actorId = filter_var($filters['actor_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($actorId !== false && $actorId !== null) {
            $conditions[] = 'a.actor_id = :actor_id';
            $parameters['actor_id'] = $actorId;
        }

        $ticketId = filter_var($filters['ticket_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($ticketId !== false && $ticketId !== null) {
            $conditions[] = 'a.ticket_id = :ticket_id';
            $parameters['ticket_id'] = $ticketId;
        }

        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $conditions[] = 'a.created_at >= :date_from';
            $parameters['date_from'] = $dateFrom . ' 00:00:00';
        }

        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $conditions[] = 'a.created_at <= :date_to';
            $parameters['date_to'] = $dateTo . ' 23:59:59';
        }

        $where = $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions);
        $from = ' FROM audit_logs a LEFT JOIN users u ON u.id = a.actor_id LEFT JOIN tickets t ON t.id = a.ticket_id';

        $countStmt = $this->connection()->prepare('SELECT COUNT(*)' . $from . $where);
        $countStmt->execute($parameters);
        $total = (int) $countStmt->fetchColumn();

        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        $dataStmt = $this->connection()->prepare(
            'SELECT a.id, a.actor_id, a.action, a.entity_type, a.entity_id, a.ticket_id,
                    a.description, a.metadata, a.ip_address, a.user_agent, a.created_at,
                    u.name AS actor_name, u.role AS actor_role,
                    t.ticket_number'
            . $from
            . $where
            . ' ORDER BY a.created_at DESC, a.id DESC LIMIT :limit OFFSET :offset'
        );

        foreach ($parameters as $key => $value) {
            $dataStmt->bindValue(':' . $key, $value);
        }
        $dataStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $dataStmt->execute();

        $items = array_map([$this, 'formatRow'], $dataStmt->fetchAll());

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages,
        ];
    }

    /**
     * Parses metadata JSON to native array if valid JSON.
     * 
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function formatRow(array $row): array
    {
        if (isset($row['metadata']) && is_string($row['metadata']) && $row['metadata'] !== '') {
            $decoded = json_decode($row['metadata'], true);
            if (is_array($decoded)) {
                $row['parsed_metadata'] = $decoded;
            }
        }

        return $row;
    }

    private function connection(): PDO
    {
        return $this->database->connection();
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Services\TicketPolicy;
use PDO;

final class Ticket
{
    public function __construct(private readonly Database $database)
    {
    }

    /** @return array{items: list<array<string, mixed>>, total: int} */
    public function paginate(array $filters, int $page, int $perPage, string $scope, int $userId): array
    {
        $conditions = [];
        $parameters = [];

        if ($filters['search'] !== '') {
            $conditions[] = '(t.ticket_number LIKE :search OR t.title LIKE :search OR creator.name LIKE :search)';
            $parameters['search'] = '%' . $filters['search'] . '%';
        }

        foreach (['category_id', 'priority_id', 'status_id'] as $field) {
            if ($filters[$field] !== null) {
                $conditions[] = "t.{$field} = :{$field}";
                $parameters[$field] = $filters[$field];
            }
        }

        if ($scope === TicketPolicy::SCOPE_CREATOR) {
            $conditions[] = 't.created_by = :scope_creator_id';
            $parameters['scope_creator_id'] = $userId;
        }
        if ($scope === TicketPolicy::SCOPE_ASSIGNEE) {
            $conditions[] = '(t.assigned_to = :scope_assignee_id OR t.created_by = :scope_creator_id)';
            $parameters['scope_assignee_id'] = $userId;
            $parameters['scope_creator_id'] = $userId;
        }

        $where = $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions);
        $from = ' FROM tickets t INNER JOIN users creator ON creator.id = t.created_by INNER JOIN categories category ON category.id = t.category_id INNER JOIN priorities priority ON priority.id = t.priority_id INNER JOIN statuses status ON status.id = t.status_id';
        $count = $this->connection()->prepare('SELECT COUNT(*)' . $from . $where);
        $count->execute($parameters);
        $total = (int) $count->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $statement = $this->connection()->prepare(
            'SELECT t.id, t.ticket_number, t.title, t.created_at, category.name AS category_name, priority.name AS priority_name, priority.color AS priority_color, status.name AS status_name, status.color AS status_color, creator.name AS creator_name' . $from . $where . ' ORDER BY t.created_at DESC LIMIT :limit OFFSET :offset'
        );

        foreach ($parameters as $key => $value) {
            $statement->bindValue(':' . $key, $value);
        }
        $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return ['items' => $statement->fetchAll(), 'total' => $total];
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $statement = $this->connection()->prepare(
            'SELECT t.*, category.name AS category_name, priority.name AS priority_name, status.name AS status_name, creator.name AS creator_name, assignee.name AS assignee_name FROM tickets t INNER JOIN categories category ON category.id = t.category_id INNER JOIN priorities priority ON priority.id = t.priority_id INNER JOIN statuses status ON status.id = t.status_id INNER JOIN users creator ON creator.id = t.created_by LEFT JOIN users assignee ON assignee.id = t.assigned_to WHERE t.id = :id LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $ticket = $statement->fetch();

        return $ticket === false ? null : $ticket;
    }

    /** @return list<array<string, mixed>> */
    public function options(string $table): array
    {
        $allowedTables = ['categories', 'priorities', 'statuses'];

        if (!in_array($table, $allowedTables, true)) {
            throw new \InvalidArgumentException('Unsupported ticket option type.');
        }

        $query = match ($table) {
            'categories' => 'SELECT id, name FROM categories WHERE is_active = 1 ORDER BY sort_order, name',
            'priorities' => 'SELECT id, name, color FROM priorities WHERE is_active = 1 ORDER BY sort_order, name',
            'statuses' => 'SELECT id, name, color FROM statuses WHERE is_active = 1 ORDER BY sort_order, name',
        };
        $statement = $this->connection()->query($query);

        return $statement->fetchAll();
    }

    public function create(array $data): int
    {
        $connection = $this->connection();
        $statement = $connection->prepare(
            'INSERT INTO tickets (ticket_number, title, description, category_id, priority_id, status_id, created_by, assigned_to, due_date) VALUES (:ticket_number, :title, :description, :category_id, :priority_id, :status_id, :created_by, :assigned_to, :due_date)'
        );
        $statement->execute($data);

        return (int) $connection->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $statement = $this->connection()->prepare(
            'UPDATE tickets SET title = :title, description = :description, category_id = :category_id, priority_id = :priority_id, due_date = :due_date WHERE id = :id'
        );
        $statement->execute(['id' => $id] + $data);
    }

    public function latestSequenceForYear(int $year): int
    {
        $statement = $this->connection()->prepare('SELECT ticket_number FROM tickets WHERE ticket_number LIKE :prefix ORDER BY ticket_number DESC LIMIT 1 FOR UPDATE');
        $statement->execute(['prefix' => "TCK-{$year}-%"]);
        $ticketNumber = $statement->fetchColumn();

        return is_string($ticketNumber) ? (int) substr($ticketNumber, -6) : 0;
    }

    /** @return array<string, mixed>|null */
    public function activeStatusById(int $id): ?array
    {
        return $this->activeLookupById('statuses', $id);
    }

    /** @return array<string, mixed>|null */
    public function activeCategoryById(int $id): ?array
    {
        return $this->activeLookupById('categories', $id);
    }

    /** @return array<string, mixed>|null */
    public function activePriorityById(int $id): ?array
    {
        return $this->activeLookupById('priorities', $id);
    }

    /** @return array<string, mixed>|null */
    public function activeTechnicianById(int $id): ?array
    {
        $statement = $this->connection()->prepare('SELECT id, name, email FROM users WHERE id = :id AND role = :role AND is_active = 1 LIMIT 1');
        $statement->execute(['id' => $id, 'role' => 'technician']);
        $user = $statement->fetch();

        return $user === false ? null : $user;
    }

    /** @return list<array<string, mixed>> */
    public function activeTechnicians(): array
    {
        return $this->connection()->query("SELECT id, name, email FROM users WHERE role = 'technician' AND is_active = 1 ORDER BY name")->fetchAll();
    }

    public function activeStatusIdByName(string $name): int
    {
        $statement = $this->connection()->prepare('SELECT id FROM statuses WHERE name = :name AND is_active = 1 LIMIT 1');
        $statement->execute(['name' => $name]);
        $id = $statement->fetchColumn();

        if ($id === false) {
            throw new \RuntimeException("The {$name} ticket status is not configured.");
        }

        return (int) $id;
    }

    /** @param list<string> $names @return list<array<string, mixed>> */
    public function activeStatusesByNames(array $names): array
    {
        if ($names === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($names), '?'));
        $statement = $this->connection()->prepare("SELECT id, name, color FROM statuses WHERE is_active = 1 AND name IN ({$placeholders}) ORDER BY sort_order");
        $statement->execute($names);

        return $statement->fetchAll();
    }

    public function updateStatus(int $id, int $statusId, ?string $resolvedAt): void
    {
        $statement = $this->connection()->prepare('UPDATE tickets SET status_id = :status_id, resolved_at = :resolved_at WHERE id = :id');
        $statement->execute(['id' => $id, 'status_id' => $statusId, 'resolved_at' => $resolvedAt]);
    }

    public function assign(int $id, int $technicianId, int $statusId): void
    {
        $statement = $this->connection()->prepare('UPDATE tickets SET assigned_to = :assigned_to, status_id = :status_id WHERE id = :id');
        $statement->execute(['id' => $id, 'assigned_to' => $technicianId, 'status_id' => $statusId]);
    }

    /** @return array<string, mixed>|null */
    private function activeLookupById(string $table, int $id): ?array
    {
        $queries = [
            'statuses' => 'SELECT id, name, color FROM statuses WHERE id = :id AND is_active = 1 LIMIT 1',
            'categories' => 'SELECT id, name FROM categories WHERE id = :id AND is_active = 1 LIMIT 1',
            'priorities' => 'SELECT id, name, color FROM priorities WHERE id = :id AND is_active = 1 LIMIT 1',
        ];
        $statement = $this->connection()->prepare($queries[$table]);
        $statement->execute(['id' => $id]);
        $item = $statement->fetch();

        return $item === false ? null : $item;
    }

    private function connection(): PDO
    {
        return $this->database->connection();
    }
}

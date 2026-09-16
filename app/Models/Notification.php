<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use InvalidArgumentException;

final class Notification
{
    public function __construct(private readonly Database $database)
    {
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): int
    {
        $userId = filter_var($data['user_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $type = trim((string) ($data['type'] ?? ''));
        $title = trim((string) ($data['title'] ?? ''));
        $message = trim((string) ($data['message'] ?? ''));

        if ($userId === false || $type === '' || $title === '' || $message === '') {
            throw new InvalidArgumentException('Notification user, type, title, and message are required.');
        }

        $connection = $this->connection();
        $statement = $connection->prepare(
            'INSERT INTO notifications (user_id, type, title, message, ticket_id, actor_id)
             VALUES (:user_id, :type, :title, :message, :ticket_id, :actor_id)'
        );
        $statement->execute([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'ticket_id' => $data['ticket_id'] ?? null,
            'actor_id' => $data['actor_id'] ?? null,
        ]);

        return (int) $connection->lastInsertId();
    }

    /** @return array<string, mixed>|null */
    public function findForUser(int $id, int $userId): ?array
    {
        $statement = $this->connection()->prepare(
            'SELECT n.*, t.ticket_number
             FROM notifications n
             LEFT JOIN tickets t ON t.id = n.ticket_id
             WHERE n.id = :id AND n.user_id = :user_id
             LIMIT 1'
        );
        $statement->execute(['id' => $id, 'user_id' => $userId]);
        $notification = $statement->fetch();

        return $notification === false ? null : $notification;
    }

    /** @return list<array<string, mixed>> */
    public function recentForUser(int $userId, int $limit = 5): array
    {
        $limit = max(1, min(20, $limit));
        $statement = $this->connection()->prepare(
            'SELECT n.*, t.ticket_number
             FROM notifications n
             LEFT JOIN tickets t ON t.id = n.ticket_id
             WHERE n.user_id = :user_id
             ORDER BY n.created_at DESC, n.id DESC
             LIMIT :limit'
        );
        $statement->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    /** @return array{items: list<array<string, mixed>>, total: int, page: int, per_page: int, total_pages: int} */
    public function paginateForUser(int $userId, int $page, int $perPage = 15): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $count = $this->connection()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :user_id');
        $count->execute(['user_id' => $userId]);
        $total = (int) $count->fetchColumn();
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        $statement = $this->connection()->prepare(
            'SELECT n.*, t.ticket_number
             FROM notifications n
             LEFT JOIN tickets t ON t.id = n.ticket_id
             WHERE n.user_id = :user_id
             ORDER BY n.created_at DESC, n.id DESC
             LIMIT :limit OFFSET :offset'
        );
        $statement->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return [
            'items' => $statement->fetchAll(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages,
        ];
    }

    public function unreadCountForUser(int $userId): int
    {
        $statement = $this->connection()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND is_read = 0');
        $statement->execute(['user_id' => $userId]);

        return (int) $statement->fetchColumn();
    }

    public function markAsRead(int $id, int $userId): bool
    {
        $statement = $this->connection()->prepare(
            'UPDATE notifications
             SET is_read = 1, read_at = CURRENT_TIMESTAMP
             WHERE id = :id AND user_id = :user_id AND is_read = 0'
        );
        $statement->execute(['id' => $id, 'user_id' => $userId]);

        return $statement->rowCount() > 0;
    }

    public function markAllAsRead(int $userId): int
    {
        $statement = $this->connection()->prepare(
            'UPDATE notifications
             SET is_read = 1, read_at = CURRENT_TIMESTAMP
             WHERE user_id = :user_id AND is_read = 0'
        );
        $statement->execute(['user_id' => $userId]);

        return $statement->rowCount();
    }

    private function connection(): PDO
    {
        return $this->database->connection();
    }
}

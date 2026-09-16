<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Comment
{
    public function __construct(private readonly Database $database)
    {
    }

    /** @return list<array<string, mixed>> */
    public function findByTicketId(int $ticketId): array
    {
        $statement = $this->connection()->prepare(
            'SELECT c.id, c.ticket_id, c.user_id, c.message, c.created_at, 
                    u.name AS user_name, u.role AS user_role
             FROM ticket_comments c
             INNER JOIN users u ON u.id = c.user_id
             WHERE c.ticket_id = :ticket_id
             ORDER BY c.created_at ASC'
        );
        $statement->execute(['ticket_id' => $ticketId]);
        
        return $statement->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $statement = $this->connection()->prepare(
            'SELECT c.*, u.name AS user_name, u.role AS user_role
             FROM ticket_comments c
             INNER JOIN users u ON u.id = c.user_id
             WHERE c.id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $comment = $statement->fetch();

        return $comment === false ? null : $comment;
    }

    public function create(int $ticketId, int $userId, string $message): int
    {
        $connection = $this->connection();
        $statement = $connection->prepare(
            'INSERT INTO ticket_comments (ticket_id, user_id, message)
             VALUES (:ticket_id, :user_id, :message)'
        );
        $statement->execute([
            'ticket_id' => $ticketId,
            'user_id' => $userId,
            'message' => $message,
        ]);

        return (int) $connection->lastInsertId();
    }

    public function delete(int $id): void
    {
        $statement = $this->connection()->prepare('DELETE FROM ticket_comments WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    private function connection(): PDO
    {
        return $this->database->connection();
    }
}

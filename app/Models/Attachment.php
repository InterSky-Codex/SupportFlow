<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Attachment
{
    public function __construct(private readonly Database $database)
    {
    }

    /** @return list<array<string, mixed>> */
    public function findByTicketId(int $ticketId): array
    {
        $statement = $this->connection()->prepare(
            'SELECT a.id, a.ticket_id, a.comment_id, a.user_id, a.original_filename, 
                    a.storage_path, a.file_size, a.mime_type, a.created_at,
                    u.name AS user_name, u.role AS user_role
             FROM ticket_attachments a
             INNER JOIN users u ON u.id = a.user_id
             WHERE a.ticket_id = :ticket_id
             ORDER BY a.created_at ASC'
        );
        $statement->execute(['ticket_id' => $ticketId]);

        return $statement->fetchAll();
    }

    /** @return list<array<string, mixed>> */
    public function findByCommentId(int $commentId): array
    {
        $statement = $this->connection()->prepare(
            'SELECT a.id, a.ticket_id, a.comment_id, a.user_id, a.original_filename, 
                    a.storage_path, a.file_size, a.mime_type, a.created_at,
                    u.name AS user_name, u.role AS user_role
             FROM ticket_attachments a
             INNER JOIN users u ON u.id = a.user_id
             WHERE a.comment_id = :comment_id
             ORDER BY a.created_at ASC'
        );
        $statement->execute(['comment_id' => $commentId]);

        return $statement->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $statement = $this->connection()->prepare(
            'SELECT a.id, a.ticket_id, a.comment_id, a.user_id, a.original_filename, 
                    a.storage_path, a.file_size, a.mime_type, a.created_at,
                    u.name AS user_name, u.role AS user_role
             FROM ticket_attachments a
             INNER JOIN users u ON u.id = a.user_id
             WHERE a.id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $attachment = $statement->fetch();

        return $attachment === false ? null : $attachment;
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): int
    {
        $connection = $this->connection();
        $statement = $connection->prepare(
            'INSERT INTO ticket_attachments (ticket_id, comment_id, user_id, original_filename, storage_path, file_size, mime_type)
             VALUES (:ticket_id, :comment_id, :user_id, :original_filename, :storage_path, :file_size, :mime_type)'
        );
        $statement->execute([
            'ticket_id' => $data['ticket_id'],
            'comment_id' => $data['comment_id'] ?? null,
            'user_id' => $data['user_id'],
            'original_filename' => $data['original_filename'],
            'storage_path' => $data['storage_path'],
            'file_size' => $data['file_size'],
            'mime_type' => $data['mime_type'],
        ]);

        return (int) $connection->lastInsertId();
    }

    public function delete(int $id): void
    {
        $statement = $this->connection()->prepare('DELETE FROM ticket_attachments WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    private function connection(): PDO
    {
        return $this->database->connection();
    }
}

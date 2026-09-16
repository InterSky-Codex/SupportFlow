<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Notification;
use PDOException;

final class NotificationService
{
    public function __construct(private readonly Notification $notifications)
    {
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): int
    {
        return $this->notifications->create($data);
    }

    /** @param list<int> $userIds */
    public function notifyUsers(array $userIds, string $type, string $title, string $message, ?int $ticketId, ?int $actorId): void
    {
        foreach (array_values(array_unique(array_filter($userIds, static fn ($id): bool => is_int($id) && $id > 0))) as $userId) {
            if ($actorId !== null && $userId === $actorId) {
                continue;
            }

            try {
                $this->create([
                    'user_id' => $userId,
                    'type' => $type,
                    'title' => $title,
                    'message' => $message,
                    'ticket_id' => $ticketId,
                    'actor_id' => $actorId,
                ]);
            } catch (PDOException $exception) {
                error_log('Notification persistence failed: ' . $exception->getMessage());
            }
        }
    }

    /** @param array<string, mixed> $ticket */
    public function notifyParticipants(array $ticket, int $actorId, string $type, string $title, string $message): void
    {
        $recipients = [(int) $ticket['created_by']];
        if (!empty($ticket['assigned_to'])) {
            $recipients[] = (int) $ticket['assigned_to'];
        }

        $this->notifyUsers($recipients, $type, $title, $message, (int) $ticket['id'], $actorId);
    }

    /** @return list<array<string, mixed>> */
    public function recent(int $userId, int $limit = 5): array
    {
        return $this->notifications->recentForUser($userId, $limit);
    }

    /** @return array<string, mixed> */
    public function paginate(int $userId, int $page = 1, int $perPage = 15): array
    {
        return $this->notifications->paginateForUser($userId, $page, $perPage);
    }

    public function unreadCount(int $userId): int
    {
        return $this->notifications->unreadCountForUser($userId);
    }

    public function markAsRead(int $id, int $userId): bool
    {
        return $this->notifications->markAsRead($id, $userId);
    }

    public function markAllAsRead(int $userId): int
    {
        return $this->notifications->markAllAsRead($userId);
    }
}

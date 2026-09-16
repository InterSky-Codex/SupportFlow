<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Comment;
use RuntimeException;

final class CommentService
{
    public function __construct(
        private readonly Comment $comments,
        private readonly TicketPolicy $policy,
        private readonly ?Database $database = null,
        private readonly ?AttachmentService $attachmentService = null,
        private readonly ?AuditService $auditService = null,
        private readonly ?NotificationService $notificationService = null,
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function listForTicket(array $ticket, array $user): array
    {
        if (!$this->policy->canView($user, $ticket)) {
            throw new RuntimeException('You do not have permission to view comments for this ticket.');
        }

        $comments = $this->comments->findByTicketId((int) $ticket['id']);

        if ($this->attachmentService !== null) {
            $allAttachments = $this->attachmentService->listForTicket((int) $ticket['id']);
            $commentAttachments = [];
            foreach ($allAttachments as $attachment) {
                if ($attachment['comment_id'] !== null) {
                    $commentAttachments[(int) $attachment['comment_id']][] = $attachment;
                }
            }

            foreach ($comments as &$comment) {
                $comment['attachments'] = $commentAttachments[(int) $comment['id']] ?? [];
            }
            unset($comment);
        }

        return $comments;
    }

    /** @param array<string, mixed> $ticket @param array<string, mixed> $user @param array<string, mixed>|null $file */
    public function create(array $ticket, array $user, string $message, ?array $file = null): int
    {
        if (!$this->canComment($user, $ticket)) {
            throw new RuntimeException('You do not have permission to comment on this ticket.');
        }

        $message = trim($message);
        if ($message === '') {
            throw new RuntimeException('Comment message cannot be empty.');
        }

        if (mb_strlen($message) > 5000) {
            throw new RuntimeException('Comment message is too long (maximum 5000 characters).');
        }

        if ($file !== null && $this->attachmentService !== null) {
            $fileErrors = $this->attachmentService->validateUpload($file);
            if ($fileErrors !== []) {
                throw new RuntimeException($fileErrors['attachment'] ?? 'File upload validation failed.');
            }
        }

        if ($this->database !== null) {
            $connection = $this->database->connection();
            $connection->beginTransaction();
            $attachmentCreated = false;

            try {
                $commentId = $this->comments->create((int) $ticket['id'], (int) $user['id'], $message);

                if ($this->auditService !== null) {
                    $this->auditService->log(
                        action: AuditService::ACTION_COMMENT_CREATED,
                        actorId: (int) $user['id'],
                        description: sprintf('%s commented on ticket %s', $user['name'] ?? 'User', $ticket['ticket_number']),
                        entityType: 'comment',
                        entityId: $commentId,
                        ticketId: (int) $ticket['id'],
                        metadata: [
                            'comment_id' => $commentId,
                            'ticket_id' => (int) $ticket['id'],
                        ]
                    );
                }

                if ($file !== null && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE && $this->attachmentService !== null) {
                    $createdAttachment = $this->attachmentService->storeAndCreate((int) $ticket['id'], $commentId, (int) $user['id'], $file);
                    $attachmentCreated = true;

                    if ($this->auditService !== null) {
                        $this->auditService->log(
                            action: AuditService::ACTION_ATTACHMENT_UPLOADED,
                            actorId: (int) $user['id'],
                            description: sprintf('%s uploaded attachment %s', $user['name'] ?? 'User', $createdAttachment['original_filename']),
                            entityType: 'attachment',
                            entityId: (int) $createdAttachment['id'],
                            ticketId: (int) $ticket['id'],
                            metadata: [
                                'original_filename' => $createdAttachment['original_filename'],
                                'file_size' => (int) $createdAttachment['file_size'],
                                'mime_type' => $createdAttachment['mime_type'],
                                'comment_id' => $commentId,
                            ]
                        );
                    }
                }

                $connection->commit();

                if ($this->notificationService !== null) {
                    $this->notificationService->notifyParticipants(
                        $ticket,
                        (int) $user['id'],
                        'comment.created',
                        'New comment on ticket',
                        sprintf('%s commented on %s.', $user['name'] ?? 'A user', $ticket['ticket_number'])
                    );
                    if ($attachmentCreated) {
                        $this->notificationService->notifyParticipants(
                            $ticket,
                            (int) $user['id'],
                            'attachment.uploaded',
                            'Attachment uploaded',
                            sprintf('An attachment was uploaded to %s.', $ticket['ticket_number'])
                        );
                    }
                }

                return $commentId;
            } catch (\Throwable $exception) {
                if ($connection->inTransaction()) {
                    $connection->rollBack();
                }
                if ($this->attachmentService !== null) {
                    $this->attachmentService->cleanupTrackedFiles();
                }
                throw $exception;
            }
        }

        $commentId = $this->comments->create((int) $ticket['id'], (int) $user['id'], $message);
        if ($this->auditService !== null) {
            $this->auditService->log(
                action: AuditService::ACTION_COMMENT_CREATED,
                actorId: (int) $user['id'],
                description: sprintf('%s commented on ticket %s', $user['name'] ?? 'User', $ticket['ticket_number']),
                entityType: 'comment',
                entityId: $commentId,
                ticketId: (int) $ticket['id'],
                metadata: [
                    'comment_id' => $commentId,
                    'ticket_id' => (int) $ticket['id'],
                ]
            );
        }
        if ($this->notificationService !== null) {
            $this->notificationService->notifyParticipants(
                $ticket,
                (int) $user['id'],
                'comment.created',
                'New comment on ticket',
                sprintf('%s commented on %s.', $user['name'] ?? 'A user', $ticket['ticket_number'])
            );
        }

        return $commentId;
    }

    public function delete(int $commentId, array $ticket, array $user): void
    {
        $comment = $this->comments->findById($commentId);

        if ($comment === null) {
            throw new RuntimeException('Comment not found.');
        }

        if ((int) $comment['ticket_id'] !== (int) $ticket['id']) {
            throw new RuntimeException('Comment does not belong to this ticket.');
        }

        if (!$this->canDelete($user, $ticket, $comment)) {
            throw new RuntimeException('You do not have permission to delete this comment.');
        }

        $deletedAttachments = [];
        if ($this->attachmentService !== null) {
            $allAttachments = $this->attachmentService->listForTicket((int) $ticket['id']);
            foreach ($allAttachments as $att) {
                if ((int) ($att['comment_id'] ?? 0) === $commentId) {
                    $deletedAttachments[] = $att;
                    $this->attachmentService->deleteAttachmentFile((string) $att['storage_path']);
                }
            }
        }

        $this->comments->delete($commentId);

        if ($this->auditService !== null) {
            foreach ($deletedAttachments as $deletedAtt) {
                $this->auditService->log(
                    action: AuditService::ACTION_ATTACHMENT_DELETED,
                    actorId: (int) $user['id'],
                    description: sprintf('%s deleted attachment %s', $user['name'] ?? 'User', $deletedAtt['original_filename']),
                    entityType: 'attachment',
                    entityId: (int) $deletedAtt['id'],
                    ticketId: (int) $ticket['id'],
                    metadata: [
                        'original_filename' => $deletedAtt['original_filename'],
                        'file_size' => (int) $deletedAtt['file_size'],
                        'mime_type' => $deletedAtt['mime_type'],
                        'comment_id' => $commentId,
                    ]
                );
            }

            $this->auditService->log(
                action: AuditService::ACTION_COMMENT_DELETED,
                actorId: (int) $user['id'],
                description: sprintf('%s deleted comment on ticket %s', $user['name'] ?? 'User', $ticket['ticket_number']),
                entityType: 'comment',
                entityId: $commentId,
                ticketId: (int) $ticket['id'],
                metadata: [
                    'comment_id' => $commentId,
                    'author_id' => (int) $comment['user_id'],
                ]
            );
        }
    }


    public function canComment(array $user, array $ticket): bool
    {
        // Reusing TicketPolicy's canEdit to enforce access and closed-ticket rules.
        return $this->policy->canEdit($user, $ticket);
    }

    public function canDelete(array $user, array $ticket, array $comment): bool
    {
        if (!$this->policy->canEdit($user, $ticket)) {
            return false;
        }

        if ($user['role'] === 'administrator') {
            return true;
        }

        return (int) $comment['user_id'] === (int) $user['id'];
    }
}

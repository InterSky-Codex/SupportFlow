<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Ticket;
use DateTimeImmutable;

final class TicketService
{
    public function __construct(
        private readonly Ticket $tickets,
        private readonly Database $database,
        private readonly TicketPolicy $policy,
        private readonly ?AttachmentService $attachmentService = null,
        private readonly ?AuditService $auditService = null,
        private readonly ?NotificationService $notificationService = null,
    ) {
    }

    /** @return array{items: list<array<string, mixed>>, total: int, pages: int, page: int} */
    public function list(array $input, array $user): array
    {
        $filters = [
            'search' => trim((string) ($input['search'] ?? '')),
            'category_id' => $this->nullablePositiveInteger($input['category_id'] ?? null),
            'priority_id' => $this->nullablePositiveInteger($input['priority_id'] ?? null),
            'status_id' => $this->nullablePositiveInteger($input['status_id'] ?? null),
        ];
        $perPage = 10;
        $page = max(1, (int) ($input['page'] ?? 1));
        $result = $this->tickets->paginate($filters, $page, $perPage, $this->policy->listScope($user), (int) $user['id']);
        $pages = max(1, (int) ceil($result['total'] / $perPage));
        $page = min($page, $pages);

        if ($page !== max(1, (int) ($input['page'] ?? 1))) {
            $result = $this->tickets->paginate($filters, $page, $perPage, $this->policy->listScope($user), (int) $user['id']);
        }

        return $result + ['pages' => $pages, 'page' => $page];
    }

    /** @return array{categories: list<array<string, mixed>>, priorities: list<array<string, mixed>>, statuses: list<array<string, mixed>>} */
    public function formOptions(): array
    {
        return [
            'categories' => $this->tickets->options('categories'),
            'priorities' => $this->tickets->options('priorities'),
            'statuses' => $this->tickets->options('statuses'),
        ];
    }

    /** @param array<string, mixed> $input @param array<string, mixed>|null $file @return array<string, string> */
    public function validate(array $input, ?array $file = null): array
    {
        $errors = [];
        $title = trim((string) ($input['title'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));

        if (mb_strlen($title) < 5 || mb_strlen($title) > 180) {
            $errors['title'] = 'Title must be between 5 and 180 characters.';
        }
        if (mb_strlen($description) < 10 || mb_strlen($description) > 5000) {
            $errors['description'] = 'Description must be between 10 and 5,000 characters.';
        }
        foreach (['category_id' => 'Category', 'priority_id' => 'Priority'] as $field => $label) {
            if ($this->nullablePositiveInteger($input[$field] ?? null) === null) {
                $errors[$field] = "{$label} is required.";
            }
        }
        if ($this->nullablePositiveInteger($input['category_id'] ?? null) !== null && $this->tickets->activeCategoryById((int) $input['category_id']) === null) {
            $errors['category_id'] = 'Select an active category.';
        }
        if ($this->nullablePositiveInteger($input['priority_id'] ?? null) !== null && $this->tickets->activePriorityById((int) $input['priority_id']) === null) {
            $errors['priority_id'] = 'Select an active priority.';
        }
        if (($input['due_date'] ?? '') !== '' && DateTimeImmutable::createFromFormat('Y-m-d', (string) $input['due_date']) === false) {
            $errors['due_date'] = 'Due date must be a valid date.';
        }

        if ($file !== null && $this->attachmentService !== null) {
            $fileErrors = $this->attachmentService->validateUpload($file);
            $errors = array_merge($errors, $fileErrors);
        }

        return $errors;
    }

    /** @param array<string, mixed> $input @param array<string, mixed> $creator @param array<string, mixed>|null $file */
    public function create(array $input, array $creator, ?array $file = null): array
    {
        if (!$this->policy->canCreate($creator)) {
            throw new \RuntimeException('You do not have permission to create tickets.');
        }

        if ($file !== null && $this->attachmentService !== null) {
            $fileErrors = $this->attachmentService->validateUpload($file);
            if ($fileErrors !== []) {
                throw new \RuntimeException($fileErrors['attachment'] ?? 'File upload validation failed.');
            }
        }

        $connection = $this->database->connection();
        $connection->beginTransaction();

        try {
            $year = (int) date('Y');
            $sequence = $this->tickets->latestSequenceForYear($year) + 1;
            $ticketNumber = sprintf('TCK-%d-%06d', $year, $sequence);
            $ticketId = $this->tickets->create($this->payload($input) + [
                'ticket_number' => $ticketNumber,
                'status_id' => $this->openStatusId(),
                'created_by' => (int) $creator['id'],
                'assigned_to' => null,
            ]);

            if ($file !== null && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE && $this->attachmentService !== null) {
                $createdAttachment = $this->attachmentService->storeAndCreate($ticketId, null, (int) $creator['id'], $file);
                if ($this->auditService !== null) {
                    $this->auditService->log(
                        action: AuditService::ACTION_ATTACHMENT_UPLOADED,
                        actorId: (int) $creator['id'],
                        description: sprintf('%s uploaded attachment %s', $creator['name'] ?? 'User', $createdAttachment['original_filename']),
                        entityType: 'attachment',
                        entityId: (int) $createdAttachment['id'],
                        ticketId: $ticketId,
                        metadata: [
                            'original_filename' => $createdAttachment['original_filename'],
                            'file_size' => (int) $createdAttachment['file_size'],
                            'mime_type' => $createdAttachment['mime_type'],
                        ]
                    );
                }
            }

            if ($this->auditService !== null) {
                $category = $this->tickets->activeCategoryById((int) $input['category_id']);
                $priority = $this->tickets->activePriorityById((int) $input['priority_id']);
                $this->auditService->log(
                    action: AuditService::ACTION_TICKET_CREATED,
                    actorId: (int) $creator['id'],
                    description: sprintf('%s created ticket %s', $creator['name'] ?? 'User', $ticketNumber),
                    entityType: 'ticket',
                    entityId: $ticketId,
                    ticketId: $ticketId,
                    metadata: [
                        'ticket_number' => $ticketNumber,
                        'category' => $category['name'] ?? '',
                        'priority' => $priority['name'] ?? '',
                        'initial_status' => 'Open',
                    ]
                );
            }

            $connection->commit();

            $ticket = $this->tickets->findById($ticketId);

            if ($ticket === null) {
                throw new \RuntimeException('The created ticket could not be loaded.');
            }

            if (
                $this->notificationService !== null
                && $file !== null
                && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE
            ) {
                $this->notificationService->notifyParticipants(
                    $ticket,
                    (int) $creator['id'],
                    'attachment.uploaded',
                    'Attachment uploaded',
                    sprintf('An attachment was uploaded to %s.', $ticket['ticket_number'])
                );
            }

            return $ticket;
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


    public function update(int $ticketId, array $input, array $actor): void
    {
        $ticket = $this->tickets->findById($ticketId);

        if ($ticket === null) {
            throw new \RuntimeException('Ticket not found.');
        }
        if (!$this->policy->canEdit($actor, $ticket)) {
            throw new \RuntimeException('You do not have permission to update this ticket.');
        }

        $payload = $this->payload($input);

        $changedFields = [];
        if ($payload['title'] !== (string) $ticket['title']) {
            $changedFields[] = 'title';
        }
        if ($payload['description'] !== (string) $ticket['description']) {
            $changedFields[] = 'description';
        }
        if ($payload['category_id'] !== (int) $ticket['category_id']) {
            $changedFields[] = 'category_id';
        }
        if ($payload['priority_id'] !== (int) $ticket['priority_id']) {
            $changedFields[] = 'priority_id';
        }
        $existingDueDate = ($ticket['due_date'] ?? null) === '' ? null : $ticket['due_date'];
        if ($payload['due_date'] !== $existingDueDate) {
            $changedFields[] = 'due_date';
        }

        $this->tickets->update($ticketId, $payload);

        if ($this->auditService !== null && $changedFields !== []) {
            $this->auditService->log(
                action: AuditService::ACTION_TICKET_UPDATED,
                actorId: (int) $actor['id'],
                description: sprintf('Ticket %s was updated by %s', $ticket['ticket_number'], $actor['name'] ?? 'User'),
                entityType: 'ticket',
                entityId: $ticketId,
                ticketId: $ticketId,
                metadata: [
                    'changed_fields' => $changedFields,
                ]
            );
        }
    }

    /** @return array<string, mixed> */
    private function payload(array $input): array
    {
        return [
            'title' => trim((string) $input['title']),
            'description' => trim((string) $input['description']),
            'category_id' => (int) $input['category_id'],
            'priority_id' => (int) $input['priority_id'],
            'due_date' => ($input['due_date'] ?? '') === '' ? null : $input['due_date'],
        ];
    }

    private function openStatusId(): int
    {
        foreach ($this->tickets->options('statuses') as $status) {
            if (strtolower($status['name']) === 'open') {
                return (int) $status['id'];
            }
        }

        throw new \RuntimeException('The Open ticket status is not configured.');
    }

    private function nullablePositiveInteger(mixed $value): ?int
    {
        $integer = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $integer === false ? null : $integer;
    }
}

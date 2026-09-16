<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Ticket;

final class TicketWorkflowService
{
    private const TRANSITIONS = [
        'Open' => ['Assigned'],
        'Assigned' => ['In Progress'],
        'In Progress' => ['Pending', 'Resolved'],
        'Pending' => ['In Progress'],
        'Resolved' => ['Closed', 'In Progress'],
        'Closed' => ['Resolved'],
    ];

    public function __construct(
        private readonly Ticket $tickets,
        private readonly TicketPolicy $policy,
        private readonly ?AuditService $auditService = null,
        private readonly ?NotificationService $notificationService = null,
    ) {
    }

    /** @param array<string, mixed> $ticket @param array<string, mixed> $actor */
    public function transition(array $ticket, array $actor, int $targetStatusId): void
    {
        if (!$this->policy->canTransition($actor, $ticket)) {
            throw new \RuntimeException('You do not have permission to update the ticket workflow.');
        }

        $target = $this->tickets->activeStatusById($targetStatusId);

        if ($target === null || !in_array($target['name'], self::TRANSITIONS[$ticket['status_name']] ?? [], true)) {
            throw new \RuntimeException('This status transition is not allowed.');
        }

        if ($ticket['status_name'] === 'Closed' && $actor['role'] !== 'administrator') {
            throw new \RuntimeException('Only administrators can reopen closed tickets.');
        }

        $oldStatusName = $ticket['status_name'];
        $newStatusName = $target['name'];

        $resolvedAt = match ($target['name']) {
            'Resolved' => date('Y-m-d H:i:s'),
            'Closed' => $ticket['resolved_at'],
            default => null,
        };
        $this->tickets->updateStatus((int) $ticket['id'], $targetStatusId, $resolvedAt);

        if ($this->auditService !== null) {
            $metadata = [
                'old_status' => $oldStatusName,
                'new_status' => $newStatusName,
            ];
            if ($resolvedAt !== null) {
                $metadata['resolved_at'] = $resolvedAt;
            }

            $this->auditService->log(
                action: AuditService::ACTION_TICKET_STATUS_CHANGED,
                actorId: (int) $actor['id'],
                description: sprintf(
                    'Ticket %s status changed from %s to %s by %s',
                    $ticket['ticket_number'],
                    $oldStatusName,
                    $newStatusName,
                    $actor['name'] ?? 'User'
                ),
                entityType: 'ticket',
                entityId: (int) $ticket['id'],
                ticketId: (int) $ticket['id'],
                metadata: $metadata
            );
        }
        if ($this->notificationService !== null) {
            $this->notificationService->notifyParticipants(
                $ticket,
                (int) $actor['id'],
                'ticket.status_changed',
                'Ticket status updated',
                sprintf('Ticket %s status changed to %s.', $ticket['ticket_number'], $newStatusName)
            );
        }
    }

    /** @param array<string, mixed> $ticket @param array<string, mixed> $actor */
    public function assign(array $ticket, array $actor, int $technicianId): void
    {
        if (!$this->policy->canAssign($actor)) {
            throw new \RuntimeException('Only administrators can assign technicians.');
        }

        $technician = $this->tickets->activeTechnicianById($technicianId);
        if ($technician === null) {
            throw new \RuntimeException('Select an active technician for this ticket.');
        }

        $assigneeChanged = (int) ($ticket['assigned_to'] ?? 0) !== $technicianId;
        $statusId = $ticket['status_name'] === 'Open' ? $this->tickets->activeStatusIdByName('Assigned') : (int) $ticket['status_id'];
        $this->tickets->assign((int) $ticket['id'], $technicianId, $statusId);

        if ($this->auditService !== null) {
            $this->auditService->log(
                action: AuditService::ACTION_TICKET_ASSIGNED,
                actorId: (int) $actor['id'],
                description: sprintf(
                    '%s assigned ticket %s to %s',
                    $actor['name'] ?? 'Administrator',
                    $ticket['ticket_number'],
                    $technician['name'] ?? 'Technician'
                ),
                entityType: 'ticket',
                entityId: (int) $ticket['id'],
                ticketId: (int) $ticket['id'],
                metadata: [
                    'technician_id' => $technicianId,
                    'technician_name' => $technician['name'],
                ]
            );
        }
        if ($this->notificationService !== null && $assigneeChanged) {
            $this->notificationService->notifyUsers(
                [$technicianId],
                'ticket.assigned',
                'Ticket assigned to you',
                sprintf('Ticket %s has been assigned to you.', $ticket['ticket_number']),
                (int) $ticket['id'],
                (int) $actor['id']
            );
        }
    }

    /** @return list<array<string, mixed>> */
    public function allowedStatuses(array $ticket, array $actor): array
    {
        if (!$this->policy->canTransition($actor, $ticket)) {
            return [];
        }

        if ($ticket['status_name'] === 'Closed' && $actor['role'] !== 'administrator') {
            return [];
        }

        return $this->tickets->activeStatusesByNames(self::TRANSITIONS[$ticket['status_name']] ?? []);
    }
}

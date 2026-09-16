<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\AuditLog;
use App\Models\Ticket;
use PDO;

final class DashboardService
{
    public function __construct(
        private readonly Database $database,
        private readonly TicketPolicy $policy,
        private readonly ?AuditService $auditService = null,
        private readonly ?Ticket $tickets = null,
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function statistics(array $user): array
    {
        $role = $user['role'];
        $userId = (int) $user['id'];
        
        $scopeCondition = match ($role) {
            'administrator' => '1=1',
            'technician' => 't.assigned_to = :user_id',
            default => 't.created_by = :user_id',
        };

        $params = [];
        if ($role !== 'administrator') {
            $params['user_id'] = $userId;
        }

        $openCount = $this->queryCount("t.status_id = (SELECT id FROM statuses WHERE name = 'Open' LIMIT 1) AND {$scopeCondition}", $params);
        $assignedCount = $this->queryCount("t.status_id = (SELECT id FROM statuses WHERE name = 'Assigned' LIMIT 1) AND {$scopeCondition}", $params);
        
        $monthStart = date('Y-m-01 00:00:00');
        $resolvedCount = $this->queryCount("t.status_id = (SELECT id FROM statuses WHERE name = 'Resolved' LIMIT 1) AND t.resolved_at >= :month_start AND {$scopeCondition}", $params + ['month_start' => $monthStart]);
        
        $criticalCount = $this->queryCount("t.priority_id = (SELECT id FROM priorities WHERE name = 'Critical' LIMIT 1) AND {$scopeCondition}", $params);

        return [
            [
                'label' => 'Open tickets',
                'value' => $openCount,
                'icon' => 'bi-inbox',
                'tone' => 'blue',
                'description' => 'Awaiting action',
            ],
            [
                'label' => $role === 'administrator' ? 'Assigned tickets' : 'Assigned to me',
                'value' => $assignedCount,
                'icon' => 'bi-person-check',
                'tone' => 'violet',
                'description' => $role === 'administrator' ? 'Total active queue' : 'Your active queue',
            ],
            [
                'label' => 'Resolved this month',
                'value' => $resolvedCount,
                'icon' => 'bi-check2-circle',
                'tone' => 'green',
                'description' => 'Completed requests',
            ],
            [
                'label' => 'Critical priority',
                'value' => $criticalCount,
                'icon' => 'bi-exclamation-octagon',
                'tone' => 'red',
                'description' => 'Needs immediate attention',
            ],
        ];
    }

    /**
     * Returns recent tickets visible to the current user (default 5).
     *
     * @param array<string, mixed> $user
     * @return array{items: list<array<string, mixed>>, total: int}
     */
     public function recentTickets(array $user, int $limit = 5): array
     {
         $ticketModel = $this->tickets ?? new Ticket($this->database);
         $scope = $this->policy->listScope($user);
         $userId = (int) $user['id'];

         $filters = [
             'search' => '',
             'category_id' => null,
             'priority_id' => null,
             'status_id' => null,
         ];

         return $ticketModel->paginate($filters, 1, $limit, $scope, $userId);
     }

    /**
     * Returns recent activity visible to the current user (default 5).
     *
     * @param array<string, mixed> $user
     * @return list<array<string, mixed>>
     */
    public function recentActivities(array $user, int $limit = 5): array
    {
        $auditService = $this->auditService ?? new AuditService(new AuditLog($this->database));
        $scope = $user['role'];
        $userId = (int) $user['id'];

        return $auditService->getRecentActivities($limit, $scope, $userId);
    }

    private function queryCount(string $where, array $params): int
    {
        $statement = $this->database->connection()->prepare("SELECT COUNT(*) FROM tickets t WHERE {$where}");
        $statement->execute($params);
        return (int) $statement->fetchColumn();
    }
}

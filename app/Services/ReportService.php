<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Ticket;
use DateInterval;
use DatePeriod;
use DateTimeImmutable;
use PDO;

final class ReportService
{
    public function __construct(
        private readonly Database $database,
        private readonly ?Ticket $tickets = null,
    ) {
    }

    /**
     * Sanitizes and normalizes incoming raw filter inputs.
     *
     * @param array<string, mixed> $raw
     * @return array{
     *     from: string,
     *     to: string,
     *     from_timestamp: string,
     *     to_timestamp: string,
     *     status_id: int|null,
     *     priority_id: int|null,
     *     category_id: int|null,
     *     technician_id: int|string|null,
     *     granularity: string
     * }
     */
    public function sanitizeFilters(array $raw): array
    {
        $defaultFrom = date('Y-m-d', strtotime('-30 days'));
        $defaultTo = date('Y-m-d');

        $rawFrom = trim((string) ($raw['from'] ?? ''));
        $rawTo = trim((string) ($raw['to'] ?? ''));

        $fromDate = $this->parseDate($rawFrom) ?? $this->parseDate($defaultFrom);
        $toDate = $this->parseDate($rawTo) ?? $this->parseDate($defaultTo);

        // Normalize if from > to by swapping
        if ($fromDate > $toDate) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }

        $fromStr = $fromDate->format('Y-m-d');
        $toStr = $toDate->format('Y-m-d');

        // Granularity calculation
        $diffDays = (int) $fromDate->diff($toDate)->days;
        if ($diffDays <= 31) {
            $granularity = 'daily';
        } elseif ($diffDays <= 120) {
            $granularity = 'weekly';
        } else {
            $granularity = 'monthly';
        }

        // Status filter
        $statusId = null;
        if (isset($raw['status_id']) && is_numeric($raw['status_id'])) {
            $val = (int) $raw['status_id'];
            if ($val > 0) {
                $statusId = $val;
            }
        }

        // Priority filter
        $priorityId = null;
        if (isset($raw['priority_id']) && is_numeric($raw['priority_id'])) {
            $val = (int) $raw['priority_id'];
            if ($val > 0) {
                $priorityId = $val;
            }
        }

        // Category filter
        $categoryId = null;
        if (isset($raw['category_id']) && is_numeric($raw['category_id'])) {
            $val = (int) $raw['category_id'];
            if ($val > 0) {
                $categoryId = $val;
            }
        }

        // Technician filter: can be positive integer or 'unassigned'
        $technicianId = null;
        if (isset($raw['technician_id'])) {
            $techRaw = trim((string) $raw['technician_id']);
            if ($techRaw === 'unassigned') {
                $technicianId = 'unassigned';
            } elseif (is_numeric($techRaw)) {
                $val = (int) $techRaw;
                if ($val > 0) {
                    $technicianId = $val;
                }
            }
        }

        return [
            'from' => $fromStr,
            'to' => $toStr,
            'from_timestamp' => $fromStr . ' 00:00:00',
            'to_timestamp' => $toStr . ' 23:59:59',
            'status_id' => $statusId,
            'priority_id' => $priorityId,
            'category_id' => $categoryId,
            'technician_id' => $technicianId,
            'granularity' => $granularity,
        ];
    }

    /**
     * Retrieves summary KPIs and resolution statistics in a single consolidated query.
     *
     * @param array<string, mixed> $filters
     * @return array{
     *     total_tickets: int,
     *     open_tickets: int,
     *     active_tickets: int,
     *     resolved_tickets: int,
     *     closed_tickets: int,
     *     resolved_count: int,
     *     avg_resolution_seconds: int|null,
     *     min_resolution_seconds: int|null,
     *     max_resolution_seconds: int|null,
     *     avg_resolution_formatted: string,
     *     min_resolution_formatted: string,
     *     max_resolution_formatted: string
     * }
     */
    public function summary(array $filters): array
    {
        [$whereSql, $params] = $this->buildTicketConditions($filters, 'sum_');

        $whereClause = $whereSql === '' ? '' : ' WHERE ' . $whereSql;

        $sql = "
            SELECT
                COUNT(t.id) AS total_tickets,
                COALESCE(SUM(CASE WHEN s.name = 'Open' THEN 1 ELSE 0 END), 0) AS open_tickets,
                COALESCE(SUM(CASE WHEN s.name NOT IN ('Resolved', 'Closed') THEN 1 ELSE 0 END), 0) AS active_tickets,
                COALESCE(SUM(CASE WHEN s.name = 'Resolved' THEN 1 ELSE 0 END), 0) AS resolved_tickets,
                COALESCE(SUM(CASE WHEN s.name = 'Closed' THEN 1 ELSE 0 END), 0) AS closed_tickets,
                COALESCE(SUM(CASE WHEN t.resolved_at IS NOT NULL THEN 1 ELSE 0 END), 0) AS resolved_count,
                AVG(CASE WHEN t.resolved_at IS NOT NULL THEN TIMESTAMPDIFF(SECOND, t.created_at, t.resolved_at) END) AS avg_sec,
                MIN(CASE WHEN t.resolved_at IS NOT NULL THEN TIMESTAMPDIFF(SECOND, t.created_at, t.resolved_at) END) AS min_sec,
                MAX(CASE WHEN t.resolved_at IS NOT NULL THEN TIMESTAMPDIFF(SECOND, t.created_at, t.resolved_at) END) AS max_sec
            FROM tickets t
            INNER JOIN statuses s ON s.id = t.status_id
            {$whereClause}
        ";

        $stmt = $this->connection()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return [
                'total_tickets' => 0,
                'open_tickets' => 0,
                'active_tickets' => 0,
                'resolved_tickets' => 0,
                'closed_tickets' => 0,
                'resolved_count' => 0,
                'avg_resolution_seconds' => null,
                'min_resolution_seconds' => null,
                'max_resolution_seconds' => null,
                'avg_resolution_formatted' => '—',
                'min_resolution_formatted' => '—',
                'max_resolution_formatted' => '—',
            ];
        }

        $avgSec = $row['avg_sec'] !== null ? (int) round((float) $row['avg_sec']) : null;
        $minSec = $row['min_sec'] !== null ? (int) $row['min_sec'] : null;
        $maxSec = $row['max_sec'] !== null ? (int) $row['max_sec'] : null;

        return [
            'total_tickets' => (int) $row['total_tickets'],
            'open_tickets' => (int) $row['open_tickets'],
            'active_tickets' => (int) $row['active_tickets'],
            'resolved_tickets' => (int) $row['resolved_tickets'],
            'closed_tickets' => (int) $row['closed_tickets'],
            'resolved_count' => (int) $row['resolved_count'],
            'avg_resolution_seconds' => $avgSec,
            'min_resolution_seconds' => $minSec,
            'max_resolution_seconds' => $maxSec,
            'avg_resolution_formatted' => $this->formatDuration($avgSec),
            'min_resolution_formatted' => $this->formatDuration($minSec),
            'max_resolution_formatted' => $this->formatDuration($maxSec),
        ];
    }

    /**
     * Generates ticket creation trend over the filtered date period.
     *
     * @param array<string, mixed> $filters
     * @return list<array{date: string, label: string, count: int}>
     */
    public function trend(array $filters): array
    {
        [$whereSql, $params] = $this->buildTicketConditions($filters, 'trd_');
        $whereClause = $whereSql === '' ? '' : ' WHERE ' . $whereSql;

        $granularity = $filters['granularity'] ?? 'daily';

        if ($granularity === 'daily') {
            $sql = "
                SELECT DATE(t.created_at) AS bucket_key, COUNT(t.id) AS ticket_count
                FROM tickets t
                {$whereClause}
                GROUP BY DATE(t.created_at)
                ORDER BY bucket_key ASC
            ";
        } elseif ($granularity === 'weekly') {
            $sql = "
                SELECT DATE_FORMAT(t.created_at, '%x-W%v') AS bucket_key,
                       MIN(DATE(t.created_at)) AS bucket_date,
                       COUNT(t.id) AS ticket_count
                FROM tickets t
                {$whereClause}
                GROUP BY bucket_key
                ORDER BY bucket_date ASC
            ";
        } else {
            $sql = "
                SELECT DATE_FORMAT(t.created_at, '%Y-%m') AS bucket_key,
                       COUNT(t.id) AS ticket_count
                FROM tickets t
                {$whereClause}
                GROUP BY bucket_key
                ORDER BY bucket_key ASC
            ";
        }

        $stmt = $this->connection()->prepare($sql);
        $stmt->execute($params);
        $dbRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $dataMap = [];
        foreach ($dbRows as $r) {
            $dataMap[(string) $r['bucket_key']] = (int) $r['ticket_count'];
        }

        // Fill missing date intervals in PHP
        $points = [];
        $from = new DateTimeImmutable($filters['from']);
        $to = new DateTimeImmutable($filters['to']);

        if ($granularity === 'daily') {
            $period = new DatePeriod($from, new DateInterval('P1D'), $to->modify('+1 day'));
            foreach ($period as $dt) {
                $k = $dt->format('Y-m-d');
                $points[] = [
                    'date' => $k,
                    'label' => $dt->format('M j'),
                    'count' => $dataMap[$k] ?? 0,
                ];
            }
        } elseif ($granularity === 'weekly') {
            // If weekly, use existing returned points or iterate weekly
            if ($dbRows === []) {
                $points[] = [
                    'date' => $from->format('Y-m-d'),
                    'label' => 'Wk ' . $from->format('W'),
                    'count' => 0,
                ];
            } else {
                foreach ($dbRows as $r) {
                    $points[] = [
                        'date' => (string) $r['bucket_key'],
                        'label' => 'Wk ' . substr((string) $r['bucket_key'], -2),
                        'count' => (int) $r['ticket_count'],
                    ];
                }
            }
        } else {
            // Monthly
            $startMonth = new DateTimeImmutable($from->format('Y-m-01'));
            $endMonth = new DateTimeImmutable($to->format('Y-m-01'));
            $curr = $startMonth;
            while ($curr <= $endMonth) {
                $k = $curr->format('Y-m');
                $points[] = [
                    'date' => $k,
                    'label' => $curr->format('M Y'),
                    'count' => $dataMap[$k] ?? 0,
                ];
                $curr = $curr->modify('+1 month');
            }
        }

        return $points;
    }

    /**
     * Returns ticket distribution by status (including 0-ticket active statuses).
     *
     * @param array<string, mixed> $filters
     * @return list<array{id: int, name: string, color: string, count: int, percentage: float}>
     */
    public function statusDistribution(array $filters): array
    {
        [$joinSql, $params] = $this->buildTicketConditions($filters, 'st_');
        $joinConditions = $joinSql === '' ? '' : ' AND ' . $joinSql;

        $sql = "
            SELECT s.id, s.name, s.color, s.sort_order,
                   COUNT(t.id) AS ticket_count
            FROM statuses s
            LEFT JOIN tickets t ON t.status_id = s.id {$joinConditions}
            WHERE s.is_active = 1
            GROUP BY s.id, s.name, s.color, s.sort_order
            ORDER BY s.sort_order ASC
        ";

        $stmt = $this->connection()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total = 0;
        foreach ($rows as $r) {
            $total += (int) $r['ticket_count'];
        }

        $result = [];
        foreach ($rows as $r) {
            $c = (int) $r['ticket_count'];
            $pct = $total > 0 ? round(($c / $total) * 100, 1) : 0.0;
            $result[] = [
                'id' => (int) $r['id'],
                'name' => (string) $r['name'],
                'color' => (string) $r['color'],
                'count' => $c,
                'percentage' => $pct,
            ];
        }

        return $result;
    }

    /**
     * Returns ticket distribution by priority (including 0-ticket active priorities).
     *
     * @param array<string, mixed> $filters
     * @return list<array{id: int, name: string, color: string, count: int, percentage: float}>
     */
    public function priorityDistribution(array $filters): array
    {
        [$joinSql, $params] = $this->buildTicketConditions($filters, 'pr_');
        $joinConditions = $joinSql === '' ? '' : ' AND ' . $joinSql;

        $sql = "
            SELECT p.id, p.name, p.color, p.sort_order,
                   COUNT(t.id) AS ticket_count
            FROM priorities p
            LEFT JOIN tickets t ON t.priority_id = p.id {$joinConditions}
            WHERE p.is_active = 1
            GROUP BY p.id, p.name, p.color, p.sort_order
            ORDER BY p.sort_order ASC
        ";

        $stmt = $this->connection()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total = 0;
        foreach ($rows as $r) {
            $total += (int) $r['ticket_count'];
        }

        $result = [];
        foreach ($rows as $r) {
            $c = (int) $r['ticket_count'];
            $pct = $total > 0 ? round(($c / $total) * 100, 1) : 0.0;
            $result[] = [
                'id' => (int) $r['id'],
                'name' => (string) $r['name'],
                'color' => (string) $r['color'],
                'count' => $c,
                'percentage' => $pct,
            ];
        }

        return $result;
    }

    /**
     * Returns ticket counts by category (top 10 descending).
     *
     * @param array<string, mixed> $filters
     * @param int $limit
     * @return list<array{id: int, name: string, count: int, percentage: float}>
     */
    public function categoryDistribution(array $filters, int $limit = 10): array
    {
        [$joinSql, $params] = $this->buildTicketConditions($filters, 'cat_');
        $joinConditions = $joinSql === '' ? '' : ' AND ' . $joinSql;

        $sql = "
            SELECT c.id, c.name, COUNT(t.id) AS ticket_count
            FROM categories c
            LEFT JOIN tickets t ON t.category_id = c.id {$joinConditions}
            WHERE c.is_active = 1
            GROUP BY c.id, c.name
            ORDER BY ticket_count DESC, c.name ASC
            LIMIT :lim
        ";

        $stmt = $this->connection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total = 0;
        foreach ($rows as $r) {
            $total += (int) $r['ticket_count'];
        }

        $result = [];
        foreach ($rows as $r) {
            $c = (int) $r['ticket_count'];
            $pct = $total > 0 ? round(($c / $total) * 100, 1) : 0.0;
            $result[] = [
                'id' => (int) $r['id'],
                'name' => (string) $r['name'],
                'count' => $c,
                'percentage' => $pct,
            ];
        }

        return $result;
    }

    /**
     * Returns technician workload metrics (active technicians).
     *
     * @param array<string, mixed> $filters
     * @return list<array{
     *     technician_id: int,
     *     technician_name: string,
     *     technician_email: string,
     *     is_active: int,
     *     total_assigned: int,
     *     active_queue: int,
     *     resolved: int,
     *     closed: int,
     *     workload_share: float
     * }>
     */
    public function technicianWorkload(array $filters): array
    {
        [$joinSql, $params] = $this->buildTicketConditions($filters, 'tech_');
        $joinConditions = $joinSql === '' ? '' : ' AND ' . $joinSql;

        $sql = "
            SELECT
                u.id AS technician_id,
                u.name AS technician_name,
                u.email AS technician_email,
                u.is_active,
                COUNT(t.id) AS total_assigned,
                COALESCE(SUM(CASE WHEN s.name NOT IN ('Resolved', 'Closed') AND t.id IS NOT NULL THEN 1 ELSE 0 END), 0) AS active_queue,
                COALESCE(SUM(CASE WHEN s.name = 'Resolved' THEN 1 ELSE 0 END), 0) AS resolved,
                COALESCE(SUM(CASE WHEN s.name = 'Closed' THEN 1 ELSE 0 END), 0) AS closed
            FROM users u
            LEFT JOIN tickets t ON t.assigned_to = u.id {$joinConditions}
            LEFT JOIN statuses s ON s.id = t.status_id
            WHERE u.role = 'technician' AND u.is_active = 1
            GROUP BY u.id, u.name, u.email, u.is_active
            ORDER BY active_queue DESC, total_assigned DESC, u.name ASC
        ";

        $stmt = $this->connection()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalActiveQueue = 0;
        foreach ($rows as $r) {
            $totalActiveQueue += (int) $r['active_queue'];
        }

        $result = [];
        foreach ($rows as $r) {
            $act = (int) $r['active_queue'];
            $share = $totalActiveQueue > 0 ? round(($act / $totalActiveQueue) * 100, 1) : 0.0;
            $result[] = [
                'technician_id' => (int) $r['technician_id'],
                'technician_name' => (string) $r['technician_name'],
                'technician_email' => (string) $r['technician_email'],
                'is_active' => (int) $r['is_active'],
                'total_assigned' => (int) $r['total_assigned'],
                'active_queue' => $act,
                'resolved' => (int) $r['resolved'],
                'closed' => (int) $r['closed'],
                'workload_share' => $share,
            ];
        }

        return $result;
    }

    /**
     * Converts a duration in seconds to a compact, human-readable string.
     */
    public function formatDuration(?int $seconds): string
    {
        if ($seconds === null || $seconds < 0) {
            return '—';
        }

        if ($seconds < 60) {
            return '< 1m';
        }

        $minutes = (int) round($seconds / 60);
        if ($minutes < 60) {
            return "{$minutes}m";
        }

        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        if ($hours < 24) {
            return $remainingMinutes > 0 ? "{$hours}h {$remainingMinutes}m" : "{$hours}h";
        }

        $days = intdiv($hours, 24);
        $remainingHours = $hours % 24;

        return $remainingHours > 0 ? "{$days}d {$remainingHours}h" : "{$days}d";
    }

    /**
     * Builds dynamic WHERE conditions on the tickets table using uniquely named placeholders.
     *
     * @param array<string, mixed> $filters
     * @param string $prefix Prefix to guarantee placeholder uniqueness across queries
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function buildTicketConditions(array $filters, string $prefix = ''): array
    {
        $conditions = [];
        $params = [];

        // Date bounds
        if (!empty($filters['from_timestamp'])) {
            $pName = $prefix . 'from_ts';
            $conditions[] = "t.created_at >= :{$pName}";
            $params[$pName] = $filters['from_timestamp'];
        }

        if (!empty($filters['to_timestamp'])) {
            $pName = $prefix . 'to_ts';
            $conditions[] = "t.created_at <= :{$pName}";
            $params[$pName] = $filters['to_timestamp'];
        }

        // Status
        if (!empty($filters['status_id'])) {
            $pName = $prefix . 'status_id';
            $conditions[] = "t.status_id = :{$pName}";
            $params[$pName] = (int) $filters['status_id'];
        }

        // Priority
        if (!empty($filters['priority_id'])) {
            $pName = $prefix . 'priority_id';
            $conditions[] = "t.priority_id = :{$pName}";
            $params[$pName] = (int) $filters['priority_id'];
        }

        // Category
        if (!empty($filters['category_id'])) {
            $pName = $prefix . 'category_id';
            $conditions[] = "t.category_id = :{$pName}";
            $params[$pName] = (int) $filters['category_id'];
        }

        // Technician
        if (isset($filters['technician_id'])) {
            if ($filters['technician_id'] === 'unassigned') {
                $conditions[] = 't.assigned_to IS NULL';
            } elseif (is_numeric($filters['technician_id']) && (int) $filters['technician_id'] > 0) {
                $pName = $prefix . 'tech_id';
                $conditions[] = "t.assigned_to = :{$pName}";
                $params[$pName] = (int) $filters['technician_id'];
            }
        }

        return [
            implode(' AND ', $conditions),
            $params,
        ];
    }

    private function parseDate(string $val): ?DateTimeImmutable
    {
        if ($val === '') {
            return null;
        }

        $dt = DateTimeImmutable::createFromFormat('Y-m-d', $val);
        if ($dt === false) {
            return null;
        }

        // Strict validation that parsed matches original formatting
        return $dt->format('Y-m-d') === $val ? $dt : null;
    }

    private function connection(): PDO
    {
        return $this->database->connection();
    }
}

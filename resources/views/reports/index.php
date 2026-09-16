<?php

declare(strict_types=1);

/**
 * @var array<string, mixed> $filters
 * @var array<string, mixed> $summary
 * @var list<array{date: string, label: string, count: int}> $trend
 * @var list<array{id: int, name: string, color: string, count: int, percentage: float}> $statusDistribution
 * @var list<array{id: int, name: string, color: string, count: int, percentage: float}> $priorityDistribution
 * @var list<array{id: int, name: string, count: int, percentage: float}> $categoryDistribution
 * @var list<array{
 *     technician_id: int,
 *     technician_name: string,
 *     technician_email: string,
 *     is_active: int,
 *     total_assigned: int,
 *     active_queue: int,
 *     resolved: int,
 *     closed: int,
 *     workload_share: float
 * }> $technicianWorkload
 * @var list<array<string, mixed>> $statusOptions
 * @var list<array<string, mixed>> $priorityOptions
 * @var list<array<string, mixed>> $categoryOptions
 * @var list<array<string, mixed>> $technicianOptions
 */

$chartPayload = [
    'trend' => [
        'labels' => array_column($trend, 'label'),
        'data' => array_column($trend, 'count'),
    ],
    'status' => [
        'labels' => array_column($statusDistribution, 'name'),
        'data' => array_column($statusDistribution, 'count'),
        'colors' => array_column($statusDistribution, 'color'),
    ],
    'priority' => [
        'labels' => array_column($priorityDistribution, 'name'),
        'data' => array_column($priorityDistribution, 'count'),
        'colors' => array_column($priorityDistribution, 'color'),
    ],
    'category' => [
        'labels' => array_column($categoryDistribution, 'name'),
        'data' => array_column($categoryDistribution, 'count'),
    ],
];
?>

<div class="reports-page">
    <!-- 1. Header -->
    <div class="page-heading mb-4">
        <div>
            <span class="eyebrow d-block mb-1">Workspace / Reports</span>
            <h2 class="mb-1">Reports &amp; Analytics</h2>
            <p class="text-muted mb-0">Operational metrics, ticket volume trends, and team performance overview.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-dark border px-3 py-2">
                <i class="bi bi-calendar-range me-1 text-primary" aria-hidden="true"></i>
                <?= e($filters['from']) ?> &mdash; <?= e($filters['to']) ?>
            </span>
        </div>
    </div>

    <!-- 2. Filter Card -->
    <div class="table-panel p-4 mb-4">
        <form method="get" action="/reports" id="reportFiltersForm">
            <!-- Quick Date Buttons -->
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3 pb-3 border-bottom">
                <span class="small fw-semibold text-muted me-1">Quick Ranges:</span>
                <button type="button" class="btn btn-sm btn-outline-secondary quick-range-btn" 
                        data-from="<?= date('Y-m-d', strtotime('-7 days')) ?>" 
                        data-to="<?= date('Y-m-d') ?>">Last 7 Days</button>
                <button type="button" class="btn btn-sm btn-outline-secondary quick-range-btn" 
                        data-from="<?= date('Y-m-d', strtotime('-30 days')) ?>" 
                        data-to="<?= date('Y-m-d') ?>">Last 30 Days</button>
                <button type="button" class="btn btn-sm btn-outline-secondary quick-range-btn" 
                        data-from="<?= date('Y-m-01') ?>" 
                        data-to="<?= date('Y-m-d') ?>">This Month</button>
                <button type="button" class="btn btn-sm btn-outline-secondary quick-range-btn" 
                        data-from="<?= date('Y-01-01') ?>" 
                        data-to="<?= date('Y-m-d') ?>">Year to Date</button>
            </div>

            <div class="row g-3 align-items-end">
                <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                    <label for="filter-from" class="form-label small fw-semibold text-muted mb-1">Date From</label>
                    <input type="date" class="form-control form-control-sm" id="filter-from" name="from" 
                           value="<?= e($filters['from']) ?>" max="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                    <label for="filter-to" class="form-label small fw-semibold text-muted mb-1">Date To</label>
                    <input type="date" class="form-control form-control-sm" id="filter-to" name="to" 
                           value="<?= e($filters['to']) ?>" max="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                    <label for="filter-status" class="form-label small fw-semibold text-muted mb-1">Status</label>
                    <select class="form-select form-select-sm" id="filter-status" name="status_id">
                        <option value="">All Statuses</option>
                        <?php foreach ($statusOptions as $st): ?>
                            <option value="<?= (int) $st['id'] ?>" <?= ($filters['status_id'] === (int) $st['id']) ? 'selected' : '' ?>>
                                <?= e($st['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                    <label for="filter-priority" class="form-label small fw-semibold text-muted mb-1">Priority</label>
                    <select class="form-select form-select-sm" id="filter-priority" name="priority_id">
                        <option value="">All Priorities</option>
                        <?php foreach ($priorityOptions as $pr): ?>
                            <option value="<?= (int) $pr['id'] ?>" <?= ($filters['priority_id'] === (int) $pr['id']) ? 'selected' : '' ?>>
                                <?= e($pr['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                    <label for="filter-category" class="form-label small fw-semibold text-muted mb-1">Category</label>
                    <select class="form-select form-select-sm" id="filter-category" name="category_id">
                        <option value="">All Categories</option>
                        <?php foreach ($categoryOptions as $cat): ?>
                            <option value="<?= (int) $cat['id'] ?>" <?= ($filters['category_id'] === (int) $cat['id']) ? 'selected' : '' ?>>
                                <?= e($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                    <label for="filter-technician" class="form-label small fw-semibold text-muted mb-1">Technician</label>
                    <select class="form-select form-select-sm" id="filter-technician" name="technician_id">
                        <option value="">All Technicians</option>
                        <option value="unassigned" <?= ($filters['technician_id'] === 'unassigned') ? 'selected' : '' ?>>Unassigned</option>
                        <?php foreach ($technicianOptions as $tech): ?>
                            <option value="<?= (int) $tech['id'] ?>" <?= ($filters['technician_id'] === (int) $tech['id']) ? 'selected' : '' ?>>
                                <?= e($tech['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-lg-4 d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1 px-3">
                        <i class="bi bi-funnel" aria-hidden="true"></i> Apply Filters
                    </button>
                    <a href="/reports" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1 px-3">
                        <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Reset
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- 3. KPI Summary Cards Grid -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-2">
            <?php
            $statistic = [
                'label' => 'Total Tickets',
                'value' => $summary['total_tickets'],
                'icon' => 'bi-ticket-detailed',
                'tone' => 'blue',
                'description' => 'Tickets in period',
            ];
            require __DIR__ . '/../components/statistic-card.php';
            ?>
        </div>
        <div class="col-12 col-sm-6 col-xl-2">
            <?php
            $statistic = [
                'label' => 'Open Tickets',
                'value' => $summary['open_tickets'],
                'icon' => 'bi-inbox',
                'tone' => 'blue',
                'description' => 'Awaiting assignment',
            ];
            require __DIR__ . '/../components/statistic-card.php';
            ?>
        </div>
        <div class="col-12 col-sm-6 col-xl-2">
            <?php
            $statistic = [
                'label' => 'Active Queue',
                'value' => $summary['active_tickets'],
                'icon' => 'bi-hourglass-split',
                'tone' => 'red',
                'description' => 'Open, Assigned, In Progress',
            ];
            require __DIR__ . '/../components/statistic-card.php';
            ?>
        </div>
        <div class="col-12 col-sm-6 col-xl-2">
            <?php
            $statistic = [
                'label' => 'Resolved Tickets',
                'value' => $summary['resolved_tickets'],
                'icon' => 'bi-check2-circle',
                'tone' => 'green',
                'description' => 'Completed requests',
            ];
            require __DIR__ . '/../components/statistic-card.php';
            ?>
        </div>
        <div class="col-12 col-sm-6 col-xl-2">
            <?php
            $statistic = [
                'label' => 'Closed Tickets',
                'value' => $summary['closed_tickets'],
                'icon' => 'bi-archive',
                'tone' => 'violet',
                'description' => 'Archived requests',
            ];
            require __DIR__ . '/../components/statistic-card.php';
            ?>
        </div>
        <div class="col-12 col-sm-6 col-xl-2">
            <?php
            $statistic = [
                'label' => 'Avg Resolution',
                'value' => $summary['avg_resolution_formatted'],
                'icon' => 'bi-stopwatch',
                'tone' => 'green',
                'description' => 'Created to resolved',
            ];
            require __DIR__ . '/../components/statistic-card.php';
            ?>
        </div>
    </div>

    <!-- 4. Visual Analytics Row 1: Trend + Status Distribution -->
    <div class="row g-4 mb-4">
        <!-- Ticket Creation Trend -->
        <div class="col-12 col-lg-8">
            <div class="dashboard-panel h-100">
                <div class="dashboard-panel__header mb-3">
                    <div>
                        <h2 class="mb-1"><i class="bi bi-graph-up me-2 text-primary"></i>Ticket Creation Trend</h2>
                        <p class="mb-0">Creation volume over the selected timeframe</p>
                    </div>
                    <span class="panel-count text-uppercase"><?= e($filters['granularity']) ?></span>
                </div>
                <div class="p-3">
                    <div style="position: relative; min-height: 260px;">
                        <canvas id="trendChart"></canvas>
                    </div>
                    <!-- Accessible Summary -->
                    <div class="mt-3 pt-3 border-top small text-muted d-flex justify-content-between">
                        <span>Period: <strong><?= e($filters['from']) ?></strong> to <strong><?= e($filters['to']) ?></strong></span>
                        <span>Total plotted tickets: <strong><?= (int) array_sum(array_column($trend, 'count')) ?></strong></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Distribution -->
        <div class="col-12 col-lg-4">
            <div class="dashboard-panel h-100">
                <div class="dashboard-panel__header mb-3">
                    <div>
                        <h2 class="mb-1"><i class="bi bi-pie-chart me-2 text-primary"></i>Status Distribution</h2>
                        <p class="mb-0">Current lifecycle states</p>
                    </div>
                </div>
                <div class="p-3">
                    <div style="position: relative; height: 180px;" class="mb-3">
                        <canvas id="statusChart"></canvas>
                    </div>
                    <!-- Accessible List -->
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0" aria-label="Status Distribution Table">
                            <tbody>
                                <?php foreach ($statusDistribution as $item): ?>
                                    <tr>
                                        <td>
                                            <span class="d-inline-block rounded-circle me-2" 
                                                  style="width: 10px; height: 10px; background-color: <?= e($item['color']) ?>;"></span>
                                            <?= e($item['name']) ?>
                                        </td>
                                        <td class="text-end fw-semibold"><?= $item['count'] ?></td>
                                        <td class="text-end text-muted small" style="width: 55px;"><?= $item['percentage'] ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 5. Visual Analytics Row 2: Priority + Category Breakdown -->
    <div class="row g-4 mb-4">
        <!-- Priority Distribution -->
        <div class="col-12 col-lg-6">
            <div class="dashboard-panel h-100">
                <div class="dashboard-panel__header mb-3">
                    <div>
                        <h2 class="mb-1"><i class="bi bi-bar-chart me-2 text-primary"></i>Priority Breakdown</h2>
                        <p class="mb-0">Tickets categorized by urgency level</p>
                    </div>
                </div>
                <div class="p-3">
                    <div style="position: relative; height: 180px;" class="mb-3">
                        <canvas id="priorityChart"></canvas>
                    </div>
                    <!-- Accessible Table -->
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0" aria-label="Priority Breakdown Table">
                            <tbody>
                                <?php foreach ($priorityDistribution as $item): ?>
                                    <tr>
                                        <td>
                                            <span class="d-inline-block rounded-circle me-2" 
                                                  style="width: 10px; height: 10px; background-color: <?= e($item['color']) ?>;"></span>
                                            <?= e($item['name']) ?>
                                        </td>
                                        <td class="text-end fw-semibold"><?= $item['count'] ?></td>
                                        <td class="text-end text-muted small" style="width: 60px;"><?= $item['percentage'] ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Categories -->
        <div class="col-12 col-lg-6">
            <div class="dashboard-panel h-100">
                <div class="dashboard-panel__header mb-3">
                    <div>
                        <h2 class="mb-1"><i class="bi bi-tags me-2 text-primary"></i>Top Categories</h2>
                        <p class="mb-0">Most frequent request categories</p>
                    </div>
                </div>
                <div class="p-3">
                    <?php if ($categoryDistribution === [] || array_sum(array_column($categoryDistribution, 'count')) === 0): ?>
                        <div class="text-center py-4 text-muted small">
                            <i class="bi bi-inbox fs-3 d-block mb-2 text-secondary"></i>
                            No ticket data available for categories in this period.
                        </div>
                    <?php else: ?>
                        <div style="position: relative; height: 180px;" class="mb-3">
                            <canvas id="categoryChart"></canvas>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0" aria-label="Category Breakdown Table">
                                <tbody>
                                    <?php foreach ($categoryDistribution as $cat): ?>
                                        <tr>
                                            <td><?= e($cat['name']) ?></td>
                                            <td class="text-end fw-semibold"><?= $cat['count'] ?></td>
                                            <td class="text-end text-muted small" style="width: 60px;"><?= $cat['percentage'] ?>%</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- 6. Technician Workload & Performance Table -->
    <div class="dashboard-panel mb-4">
        <div class="dashboard-panel__header mb-3">
            <div>
                <h2 class="mb-1"><i class="bi bi-people me-2 text-primary"></i>Technician Workload &amp; Assignment Queue</h2>
                <p class="mb-0">Current active workload distribution across active technicians (based on current assignment)</p>
            </div>
            <span class="panel-count"><?= count($technicianWorkload) ?> Technicians</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" aria-label="Technician Workload Table">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Technician</th>
                        <th>Status</th>
                        <th class="text-center">Total Assigned</th>
                        <th class="text-center">Active Queue</th>
                        <th class="text-center">Resolved</th>
                        <th class="text-center">Closed</th>
                        <th class="pe-4" style="min-width: 180px;">Active Workload Share</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($technicianWorkload === []): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                No active technicians found in the system.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($technicianWorkload as $tech): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-semibold text-dark"><?= e($tech['technician_name']) ?></div>
                                    <div class="small text-muted"><?= e($tech['technician_email']) ?></div>
                                </td>
                                <td>
                                    <?php if ($tech['is_active'] === 1): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center fw-semibold"><?= $tech['total_assigned'] ?></td>
                                <td class="text-center">
                                    <span class="badge <?= $tech['active_queue'] > 0 ? 'bg-primary' : 'bg-light text-muted border' ?>">
                                        <?= $tech['active_queue'] ?>
                                    </span>
                                </td>
                                <td class="text-center text-success fw-semibold"><?= $tech['resolved'] ?></td>
                                <td class="text-center text-muted"><?= $tech['closed'] ?></td>
                                <td class="pe-4">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height: 6px;">
                                            <div class="progress-bar bg-primary" role="progressbar" 
                                                 style="width: <?= min(100.0, $tech['workload_share']) ?>%;" 
                                                 aria-valuenow="<?= $tech['workload_share'] ?>" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100"></div>
                                        </div>
                                        <span class="small fw-semibold text-muted" style="min-width: 40px;"><?= $tech['workload_share'] ?>%</span>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 7. Resolution Performance Benchmarks -->
    <div class="dashboard-panel p-4 mb-4">
        <div class="dashboard-panel__header p-0 mb-3">
            <div>
                <h2 class="mb-1"><i class="bi bi-speedometer2 me-2 text-primary"></i>Resolution Performance Benchmarks</h2>
                <p class="mb-0">Calculated strictly from ticket creation to resolution timestamp (`created_at` &rarr; `resolved_at`)</p>
            </div>
        </div>
        <div class="row g-3 text-center">
            <div class="col-6 col-md-3">
                <div class="p-3 bg-light rounded-3 border">
                    <span class="small text-muted d-block mb-1">Fastest Resolution</span>
                    <span class="fs-4 fw-bold text-success"><?= e($summary['min_resolution_formatted']) ?></span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 bg-light rounded-3 border">
                    <span class="small text-muted d-block mb-1">Average Resolution</span>
                    <span class="fs-4 fw-bold text-primary"><?= e($summary['avg_resolution_formatted']) ?></span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 bg-light rounded-3 border">
                    <span class="small text-muted d-block mb-1">Longest Resolution</span>
                    <span class="fs-4 fw-bold text-warning"><?= e($summary['max_resolution_formatted']) ?></span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 bg-light rounded-3 border">
                    <span class="small text-muted d-block mb-1">Resolved in Period</span>
                    <span class="fs-4 fw-bold text-dark"><?= (int) $summary['resolved_count'] ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Embedded Dataset for Client-side Chart Initialization -->
<script id="report-data" type="application/json">
<?= json_encode($chartPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>
</script>

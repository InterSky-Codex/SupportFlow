<?php

/** @var array<string, mixed> $filters */
/** @var array<string, string> $errors */
/** @var array<string, mixed> $listing */
?>
<section class="page-heading">
    <div>
        <span class="section-label">Workspace / Administration</span>
        <h2>Audit Logs</h2>
        <p class="mb-0">Review read-only activity history across the workspace.</p>
    </div>
</section>

<?php if ($errors !== []): ?>
    <div class="alert alert-danger dashboard-alert" role="alert">
        <?php foreach ($errors as $error): ?>
            <div><?= e($error) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<section class="table-panel">
    <form class="ticket-filters" action="/audit-logs" method="get">
        <div>
            <label class="form-label small fw-semibold text-muted mb-1" for="audit-from">Date From</label>
            <input class="form-control" id="audit-from" type="date" name="from" value="<?= e((string) $filters['date_from']) ?>">
        </div>
        <div>
            <label class="form-label small fw-semibold text-muted mb-1" for="audit-to">Date To</label>
            <input class="form-control" id="audit-to" type="date" name="to" value="<?= e((string) $filters['date_to']) ?>">
        </div>
        <select class="form-select" name="action" aria-label="Filter by action">
            <option value="">All events</option>
            <?php foreach ($actionOptions as $action): ?>
                <option value="<?= e($action) ?>" <?= $filters['action'] === $action ? 'selected' : '' ?>><?= e($action) ?></option>
            <?php endforeach; ?>
        </select>
        <select class="form-select" name="actor_id" aria-label="Filter by actor">
            <option value="">All actors</option>
            <?php foreach ($actorOptions as $actor): ?>
                <option value="<?= (int) $actor['id'] ?>" <?= (string) $filters['actor_id'] === (string) $actor['id'] ? 'selected' : '' ?>><?= e($actor['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select class="form-select" name="entity_type" aria-label="Filter by entity">
            <option value="">All entities</option>
            <?php foreach ($entityOptions as $entityType): ?>
                <option value="<?= e($entityType) ?>" <?= $filters['entity_type'] === $entityType ? 'selected' : '' ?>><?= e(ucfirst($entityType)) ?></option>
            <?php endforeach; ?>
        </select>
        <div class="d-flex gap-2 align-items-end">
            <button class="btn btn-outline-secondary" type="submit">Apply</button>
            <a class="btn btn-light" href="/audit-logs">Clear</a>
        </div>
    </form>

    <?php if ($listing['items'] === []): ?>
        <?php
        $emptyStateIcon = 'bi-shield-check';
        $emptyStateTitle = 'No audit activity found';
        $emptyStateDescription = 'No audit logs match the selected filters.';
        require __DIR__ . '/../components/empty-state.php';
        ?>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table ticket-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>Actor</th>
                        <th>Action/Event</th>
                        <th>Entity</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($listing['items'] as $item): ?>
                        <tr>
                            <td><?= e(date('d M Y H:i', strtotime((string) $item['created_at']))) ?></td>
                            <td><?= e((string) ($item['actor_name'] ?? 'System / Anonymous')) ?></td>
                            <td><span class="badge bg-light text-dark border"><?= e($item['action']) ?></span></td>
                            <td>
                                <?= e($item['entity_type']) ?>
                                <?php if (!empty($item['ticket_number'])): ?>
                                    <span class="d-block small text-muted"><?= e($item['ticket_number']) ?></span>
                                <?php elseif ($item['entity_id'] !== null): ?>
                                    <span class="d-block small text-muted">ID <?= e($item['entity_id']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= e($item['description']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        $currentPage = $listing['page'];
        $totalPages = $listing['total_pages'];
        $paginationFilters = array_filter([
            'from' => $filters['date_from'],
            'to' => $filters['date_to'],
            'action' => $filters['action'],
            'actor_id' => $filters['actor_id'],
            'entity_type' => $filters['entity_type'],
        ], static fn ($value): bool => $value !== '');
        $paginationPath = '/audit-logs';
        require __DIR__ . '/../components/pagination.php';
        ?>
    <?php endif; ?>
</section>

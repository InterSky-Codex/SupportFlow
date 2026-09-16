<section class="page-heading">
    <div>
        <span class="section-label">Ticket management</span>
        <h2>Tickets</h2>
        <p class="mb-0">Track, organize, and follow every support request.</p>
    </div>
    <a class="btn btn-primary" href="/tickets/create"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Create ticket</a>
</section>

<?php if ($successMessage !== null): ?>
    <div class="alert alert-success dashboard-alert" role="status"><?= e($successMessage) ?></div>
<?php endif; ?>

<section class="table-panel">
    <form class="ticket-filters" action="/tickets" method="get">
        <div class="search-field">
            <i class="bi bi-search" aria-hidden="true"></i>
            <input class="form-control" type="search" name="search" value="<?= e((string) ($filters['search'] ?? '')) ?>" placeholder="Search ticket, title, or requester">
        </div>
        <select class="form-select" name="status_id" aria-label="Filter by status">
            <option value="">All statuses</option>
            <?php foreach ($options['statuses'] as $status): ?>
                <option value="<?= e($status['id']) ?>" <?= (string) ($filters['status_id'] ?? '') === (string) $status['id'] ? 'selected' : '' ?>><?= e($status['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select class="form-select" name="priority_id" aria-label="Filter by priority">
            <option value="">All priorities</option>
            <?php foreach ($options['priorities'] as $priority): ?>
                <option value="<?= e($priority['id']) ?>" <?= (string) ($filters['priority_id'] ?? '') === (string) $priority['id'] ? 'selected' : '' ?>><?= e($priority['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select class="form-select" name="category_id" aria-label="Filter by category">
            <option value="">All categories</option>
            <?php foreach ($options['categories'] as $category): ?>
                <option value="<?= e($category['id']) ?>" <?= (string) ($filters['category_id'] ?? '') === (string) $category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-outline-secondary" type="submit">Apply</button>
    </form>

    <?php if ($listing['items'] === []): ?>
        <?php
        $emptyStateIcon = 'bi-ticket-detailed';
        $emptyStateTitle = 'No matching tickets';
        $emptyStateDescription = 'Create a new ticket or adjust the filters to see support requests.';
        require __DIR__ . '/../components/empty-state.php';
        ?>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table ticket-table align-middle mb-0">
                <thead><tr><th>Ticket</th><th>Category</th><th>Priority</th><th>Status</th><th>Requester</th><th>Created</th><th><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    <?php foreach ($listing['items'] as $ticket): ?>
                        <tr>
                            <td><span class="ticket-number"><?= e($ticket['ticket_number']) ?></span><a class="ticket-title" href="/tickets/<?= e($ticket['id']) ?>"><?= e($ticket['title']) ?></a></td>
                            <td><?= e($ticket['category_name']) ?></td>
                            <td><?php $badgeLabel = $ticket['priority_name']; $badgeColor = $ticket['priority_color']; require __DIR__ . '/../components/ticket-badge.php'; ?></td>
                            <td><?php $badgeLabel = $ticket['status_name']; $badgeColor = $ticket['status_color']; require __DIR__ . '/../components/ticket-badge.php'; ?></td>
                            <td><?= e($ticket['creator_name']) ?></td>
                            <td><?= e(date('d M Y', strtotime((string) $ticket['created_at']))) ?></td>
                            <td><a class="btn btn-sm btn-light" href="/tickets/<?= e($ticket['id']) ?>" aria-label="View <?= e($ticket['ticket_number']) ?>"><i class="bi bi-arrow-right" aria-hidden="true"></i></a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php $currentPage = $listing['page']; $totalPages = $listing['pages']; $paginationFilters = $filters; $paginationPath = '/tickets'; require __DIR__ . '/../components/pagination.php'; ?>
    <?php endif; ?>
</section>

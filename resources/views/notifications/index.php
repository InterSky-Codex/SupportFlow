<section class="page-heading">
    <div>
        <span class="section-label">Workspace</span>
        <h2>Notifications</h2>
        <p class="mb-0">Review important activity related to your tickets.</p>
    </div>
    <?php if (($listing['items'] ?? []) !== []): ?>
        <form action="/notifications/read-all" method="post">
            <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
            <button class="btn btn-outline-secondary" type="submit">Mark all as read</button>
        </form>
    <?php endif; ?>
</section>

<section class="table-panel">
    <?php if ($listing['items'] === []): ?>
        <?php
        $emptyStateIcon = 'bi-bell';
        $emptyStateTitle = 'No notifications';
        $emptyStateDescription = 'Important ticket activity will appear here.';
        require __DIR__ . '/../components/empty-state.php';
        ?>
    <?php else: ?>
        <div class="list-group list-group-flush">
            <?php foreach ($listing['items'] as $notification): ?>
                <div class="list-group-item px-4 py-3 <?= (int) $notification['is_read'] === 0 ? 'bg-light' : '' ?>">
                    <div class="d-flex justify-content-between gap-3">
                        <div>
                            <h3 class="h6 mb-1">
                                <?php if (!empty($notification['ticket_id'])): ?>
                                    <a class="text-decoration-none" href="/tickets/<?= e($notification['ticket_id']) ?>"><?= e($notification['title']) ?></a>
                                <?php else: ?>
                                    <?= e($notification['title']) ?>
                                <?php endif; ?>
                            </h3>
                            <p class="mb-1"><?= e($notification['message']) ?></p>
                            <small class="text-muted"><?= e(date('d M Y H:i', strtotime((string) $notification['created_at']))) ?></small>
                        </div>
                        <?php if ((int) $notification['is_read'] === 0): ?>
                            <form action="/notifications/<?= e($notification['id']) ?>/read" method="post">
                                <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
                                <button class="btn btn-sm btn-outline-secondary" type="submit">Mark read</button>
                            </form>
                        <?php else: ?>
                            <span class="text-muted small">Read</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        $currentPage = $listing['page'];
        $totalPages = $listing['total_pages'];
        $paginationFilters = [];
        $paginationPath = '/notifications';
        require __DIR__ . '/../components/pagination.php';
        ?>
    <?php endif; ?>
</section>

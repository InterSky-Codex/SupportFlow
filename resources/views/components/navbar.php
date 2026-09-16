<header class="app-navbar">
    <button class="btn btn-icon d-lg-none" type="button" data-sidebar-toggle aria-label="Open navigation" aria-controls="app-sidebar">
        <i class="bi bi-list" aria-hidden="true"></i>
    </button>
    <div>
        <p class="eyebrow mb-0">Helpdesk workspace</p>
        <h1 class="page-title mb-0"><?= e($pageTitle ?? 'Support desk') ?></h1>
    </div>
    <div class="navbar-actions ms-auto">
        <div class="dropdown">
            <a class="btn btn-icon position-relative" href="/notifications" role="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications<?= ($notificationUnreadCount ?? 0) > 0 ? ', ' . $notificationUnreadCount . ' unread' : '' ?>">
                <i class="bi bi-bell" aria-hidden="true"></i>
                <?php if (($notificationUnreadCount ?? 0) > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= e($notificationUnreadCount) ?></span>
                <?php endif; ?>
            </a>
            <div class="dropdown-menu dropdown-menu-end p-0" style="min-width: 320px;">
                <div class="px-3 py-2 border-bottom fw-semibold">Notifications</div>
                <?php if (($notificationRecent ?? []) === []): ?>
                    <div class="px-3 py-3 text-muted small">No notifications</div>
                <?php else: ?>
                    <?php foreach ($notificationRecent as $notification): ?>
                        <a class="dropdown-item text-wrap py-2 <?= (int) $notification['is_read'] === 0 ? 'fw-semibold' : '' ?>" href="<?= !empty($notification['ticket_id']) ? '/tickets/' . e($notification['ticket_id']) : '/notifications' ?>">
                            <span class="d-block"><?= e($notification['title']) ?></span>
                            <small class="text-muted"><?= e($notification['message']) ?></small>
                            <small class="d-block text-muted"><?= e(date('d M Y H:i', strtotime((string) $notification['created_at']))) ?></small>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
                <div class="border-top px-3 py-2"><a class="small text-decoration-none" href="/notifications">View all notifications</a></div>
            </div>
        </div>
        <span class="user-avatar" aria-label="<?= e(($currentUser['name'] ?? 'SupportFlow') . ' account') ?>">
            <?= e(strtoupper(substr((string) ($currentUser['name'] ?? 'SF'), 0, 2))) ?>
        </span>
    </div>
</header>

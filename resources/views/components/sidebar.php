<aside class="app-sidebar" id="app-sidebar">
    <a class="brand" href="/" aria-label="SupportFlow dashboard">
        <span class="brand-mark"><i class="bi bi-life-preserver" aria-hidden="true"></i></span>
        <span>SupportFlow</span>
    </a>

    <nav class="sidebar-nav" aria-label="Primary navigation">
        <p class="nav-heading">Workspace</p>
        <a class="nav-link <?= ($activeNavigation ?? '') === 'dashboard' ? 'active' : '' ?>" href="/dashboard">
            <i class="bi bi-grid-1x2" aria-hidden="true"></i> Dashboard
        </a>
        <a class="nav-link <?= ($activeNavigation ?? '') === 'tickets' ? 'active' : '' ?>" href="/tickets">
            <i class="bi bi-ticket-detailed" aria-hidden="true"></i> Tickets
        </a>
        <a class="nav-link <?= ($activeNavigation ?? '') === 'knowledge-base' ? 'active' : '' ?>" href="/knowledge-base">
            <i class="bi bi-book-half" aria-hidden="true"></i> Knowledge Base
        </a>
        <?php if (($currentUser['role'] ?? '') === 'administrator'): ?>
            <a class="nav-link <?= ($activeNavigation ?? '') === 'reports' ? 'active' : '' ?>" href="/reports">
                <i class="bi bi-bar-chart" aria-hidden="true"></i> Reports
            </a>
            <a class="nav-link <?= ($activeNavigation ?? '') === 'audit-logs' ? 'active' : '' ?>" href="/audit-logs">
                <i class="bi bi-shield-check" aria-hidden="true"></i> Audit Logs
            </a>
        <?php else: ?>
            <span class="nav-link nav-link-muted"><i class="bi bi-bar-chart" aria-hidden="true"></i> Reports</span>
        <?php endif; ?>

        <p class="nav-heading mt-4">Management</p>
        <?php if (($currentUser['role'] ?? '') === 'administrator'): ?>
            <a class="nav-link <?= ($activeNavigation ?? '') === 'users' ? 'active' : '' ?>" href="/users">
                <i class="bi bi-people" aria-hidden="true"></i> Users
            </a>
            <a class="nav-link <?= ($activeNavigation ?? '') === 'settings' ? 'active' : '' ?>" href="/settings">
                <i class="bi bi-gear" aria-hidden="true"></i> Settings
            </a>
        <?php else: ?>
            <span class="nav-link nav-link-muted"><i class="bi bi-people" aria-hidden="true"></i> Users</span>
            <span class="nav-link nav-link-muted"><i class="bi bi-gear" aria-hidden="true"></i> Settings</span>
        <?php endif; ?>
    </nav>

    <div class="sidebar-status">
        <span class="status-dot"></span>
        <span>System operational</span>
    </div>
    <form class="logout-form" action="/logout" method="post">
        <input type="hidden" name="_token" value="<?= e($csrfToken ?? '') ?>">
        <button class="nav-link logout-button" type="submit">
            <i class="bi bi-box-arrow-right" aria-hidden="true"></i> Sign out
        </button>
    </form>
</aside>

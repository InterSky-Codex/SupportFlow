<section class="page-heading">
    <div>
        <span class="section-label">User management</span>
        <h2>Users</h2>
        <p class="mb-0">Manage workspace members, assign roles, and control access.</p>
    </div>
    <a class="btn btn-primary" href="/users/create"><i class="bi bi-person-plus me-1" aria-hidden="true"></i> Create user</a>
</section>

<?php if (!empty($successMessage)): ?>
    <div class="alert alert-success dashboard-alert" role="status"><?= e($successMessage) ?></div>
<?php endif; ?>

<?php if (!empty($errorMessage)): ?>
    <div class="alert alert-danger dashboard-alert" role="alert"><?= e($errorMessage) ?></div>
<?php endif; ?>

<section class="table-panel">
    <form class="ticket-filters" action="/users" method="get">
        <div class="search-field">
            <i class="bi bi-search" aria-hidden="true"></i>
            <input class="form-control" type="search" name="search" value="<?= e((string) ($filters['search'] ?? '')) ?>" placeholder="Search by name or email">
        </div>
        <select class="form-select" name="role" aria-label="Filter by role">
            <option value="">All roles</option>
            <option value="employee" <?= (string) ($filters['role'] ?? '') === 'employee' ? 'selected' : '' ?>>Employee</option>
            <option value="technician" <?= (string) ($filters['role'] ?? '') === 'technician' ? 'selected' : '' ?>>Technician</option>
            <option value="administrator" <?= (string) ($filters['role'] ?? '') === 'administrator' ? 'selected' : '' ?>>Administrator</option>
        </select>
        <select class="form-select" name="status" aria-label="Filter by status">
            <option value="">All statuses</option>
            <option value="active" <?= (string) ($filters['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="inactive" <?= (string) ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
        </select>
        <button class="btn btn-outline-secondary" type="submit">Apply</button>
    </form>

    <?php if (empty($listing['items'])): ?>
        <?php
        $emptyStateIcon = 'bi-people';
        $emptyStateTitle = 'No matching users';
        $emptyStateDescription = 'Create a new user or adjust your search filters.';
        require __DIR__ . '/../components/empty-state.php';
        ?>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table ticket-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($listing['items'] as $item): ?>
                        <tr>
                            <td>
                                <a class="fw-semibold text-decoration-none text-dark" href="/users/<?= e($item['id']) ?>"><?= e($item['name']) ?></a>
                            </td>
                            <td><?= e($item['email']) ?></td>
                            <td>
                                <?php
                                $roleBadgeClass = match ($item['role']) {
                                    'administrator' => 'bg-danger-subtle text-danger border border-danger-subtle',
                                    'technician' => 'bg-primary-subtle text-primary border border-primary-subtle',
                                    default => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
                                };
                                ?>
                                <span class="badge <?= $roleBadgeClass ?> text-capitalize"><?= e($item['role']) ?></span>
                            </td>
                            <td>
                                <?php if ((bool) $item['is_active']): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e(date('d M Y', strtotime((string) $item['created_at']))) ?></td>
                            <td>
                                <div class="d-flex gap-1 justify-content-end">
                                    <a class="btn btn-sm btn-light" href="/users/<?= e($item['id']) ?>" title="View user"><i class="bi bi-eye" aria-hidden="true"></i></a>
                                    <a class="btn btn-sm btn-light" href="/users/<?= e($item['id']) ?>/edit" title="Edit user"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        $currentPage = $listing['page'];
        $totalPages = $listing['pages'];
        $paginationFilters = $filters;
        $paginationPath = '/users';
        require __DIR__ . '/../components/pagination.php';
        ?>
    <?php endif; ?>
</section>

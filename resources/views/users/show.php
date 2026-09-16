<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="/users" class="text-decoration-none">Users</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= e($userItem['name']) ?></li>
            </ol>
        </nav>
        <h1 class="h3 mb-0"><?= e($userItem['name']) ?></h1>
    </div>
    <div class="d-flex gap-2">
        <a href="/users/<?= e($userItem['id']) ?>/edit" class="btn btn-outline-secondary">
            <i class="bi bi-pencil me-1"></i> Edit Profile
        </a>
        <a href="/users/<?= e($userItem['id']) ?>/password" class="btn btn-outline-warning">
            <i class="bi bi-key me-1"></i> Reset Password
        </a>
    </div>
</div>

<?php if (!empty($successMessage)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= e($successMessage) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (!empty($errorMessage)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= e($errorMessage) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                <h5 class="card-title mb-0">User Account Information</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted fw-normal">Full Name</dt>
                    <dd class="col-sm-8 fw-semibold"><?= e($userItem['name']) ?></dd>

                    <dt class="col-sm-4 text-muted fw-normal">Email Address</dt>
                    <dd class="col-sm-8"><?= e($userItem['email']) ?></dd>

                    <dt class="col-sm-4 text-muted fw-normal">Role</dt>
                    <dd class="col-sm-8">
                        <?php
                        $roleBadgeClass = match ($userItem['role']) {
                            'administrator' => 'bg-danger-subtle text-danger border border-danger-subtle',
                            'technician' => 'bg-primary-subtle text-primary border border-primary-subtle',
                            default => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
                        };
                        ?>
                        <span class="badge <?= $roleBadgeClass ?> text-capitalize"><?= e($userItem['role']) ?></span>
                    </dd>

                    <dt class="col-sm-4 text-muted fw-normal">Account Status</dt>
                    <dd class="col-sm-8">
                        <?php if ((bool) $userItem['is_active']): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                        <?php else: ?>
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Inactive</span>
                        <?php endif; ?>
                    </dd>

                    <dt class="col-sm-4 text-muted fw-normal">Created At</dt>
                    <dd class="col-sm-8"><?= e(date('M j, Y g:i A', strtotime((string) $userItem['created_at']))) ?></dd>

                    <?php if (!empty($userItem['updated_at'])): ?>
                        <dt class="col-sm-4 text-muted fw-normal">Last Updated</dt>
                        <dd class="col-sm-8"><?= e(date('M j, Y g:i A', strtotime((string) $userItem['updated_at']))) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm mb-4 border-secondary">
            <div class="card-header bg-light border-bottom-0 pt-3">
                <h6 class="card-title mb-0">Status Control</h6>
            </div>
            <div class="card-body">
                <?php if ((bool) $userItem['is_active']): ?>
                    <p class="small text-muted mb-3">Deactivating this user will prevent them from logging in or using an active session.</p>
                    <form action="/users/<?= e($userItem['id']) ?>/deactivate" method="post">
                        <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
                        <button type="submit" class="btn btn-outline-danger w-100" <?= (int) $currentUser['id'] === (int) $userItem['id'] ? 'disabled' : '' ?>>
                            <i class="bi bi-person-x me-1"></i> Deactivate Account
                        </button>
                    </form>
                    <?php if ((int) $currentUser['id'] === (int) $userItem['id']): ?>
                        <div class="form-text text-danger mt-1 text-center">You cannot deactivate your own account.</div>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="small text-muted mb-3">Activating this user will allow them to access the workspace again.</p>
                    <form action="/users/<?= e($userItem['id']) ?>/activate" method="POST">
                        <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
                        <button type="submit" class="btn btn-outline-success w-100">
                            <i class="bi bi-person-check me-1"></i> Activate Account
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$value = static fn (string $key): string => (string) ($values[$key] ?? '');
$selectedRole = static fn (string $role): bool => (string) ($values['role'] ?? 'employee') === $role;
?>
<section class="page-heading">
    <div>
        <span class="section-label">User management</span>
        <h2><?= e($pageTitle) ?></h2>
        <p class="mb-0"><?= $editing ? 'Update workspace member profile and role.' : 'Create a new user account for the helpdesk workspace.' ?></p>
    </div>
    <a class="btn btn-outline-secondary" href="/users"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i> Back to users</a>
</section>

<?php if (!empty($errorMessage)): ?>
    <div class="alert alert-danger dashboard-alert" role="alert"><?= e($errorMessage) ?></div>
<?php endif; ?>

<form class="ticket-form" action="<?= e($action) ?>" method="post" novalidate>
    <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
    
    <section class="form-panel">
        <div class="form-panel__header">
            <h3>User Profile</h3>
            <p>Fields marked required are needed.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-6">
                <label class="form-label" for="name">Full Name <span class="required-mark">*</span></label>
                <input class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" id="name" name="name" value="<?= e($value('name')) ?>" maxlength="100" required>
                <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= e($errors['name']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="email">Email Address <span class="required-mark">*</span></label>
                <input class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="email" name="email" type="email" value="<?= e($value('email')) ?>" maxlength="190" required>
                <?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?= e($errors['email']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="role">Role <span class="required-mark">*</span></label>
                <select class="form-select <?= isset($errors['role']) ? 'is-invalid' : '' ?>" id="role" name="role" required>
                    <option value="employee" <?= $selectedRole('employee') ? 'selected' : '' ?>>Employee</option>
                    <option value="technician" <?= $selectedRole('technician') ? 'selected' : '' ?>>Technician</option>
                    <option value="administrator" <?= $selectedRole('administrator') ? 'selected' : '' ?>>Administrator</option>
                </select>
                <?php if (isset($errors['role'])): ?><div class="invalid-feedback"><?= e($errors['role']) ?></div><?php endif; ?>
            </div>

            <?php if (!$editing): ?>
                <div class="col-md-6">
                    <label class="form-label" for="password">Password <span class="required-mark">*</span></label>
                    <input class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" id="password" name="password" type="password" required>
                    <div class="form-text">Minimum 8 characters.</div>
                    <?php if (isset($errors['password'])): ?><div class="invalid-feedback"><?= e($errors['password']) ?></div><?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="password_confirmation">Confirm Password <span class="required-mark">*</span></label>
                    <input class="form-control <?= isset($errors['password_confirmation']) ? 'is-invalid' : '' ?>" id="password_confirmation" name="password_confirmation" type="password" required>
                    <?php if (isset($errors['password_confirmation'])): ?><div class="invalid-feedback"><?= e($errors['password_confirmation']) ?></div><?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <div class="form-actions">
        <a class="btn btn-outline-secondary" href="<?= $editing ? '/users/' . e($values['id'] ?? '') : '/users' ?>">Cancel</a>
        <button class="btn btn-primary" type="submit"><?= $editing ? 'Save changes' : 'Create user' ?></button>
    </div>
</form>

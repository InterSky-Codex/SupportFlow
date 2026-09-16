<section class="page-heading">
    <div>
        <span class="section-label">User management</span>
        <h2>Reset Password</h2>
        <p class="mb-0">Set a new password for <?= e($userItem['name']) ?> (<?= e($userItem['email']) ?>).</p>
    </div>
    <a class="btn btn-outline-secondary" href="/users/<?= e($userItem['id']) ?>"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i> Back to user</a>
</section>

<?php if (!empty($errorMessage)): ?>
    <div class="alert alert-danger dashboard-alert" role="alert"><?= e($errorMessage) ?></div>
<?php endif; ?>

<form class="ticket-form" action="/users/<?= e($userItem['id']) ?>/password" method="post" novalidate>
    <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
    
    <section class="form-panel">
        <div class="form-panel__header">
            <h3>New Credentials</h3>
            <p>Enter the new password. Minimum 8 characters required.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-6">
                <label class="form-label" for="password">New Password <span class="required-mark">*</span></label>
                <input class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" id="password" name="password" type="password" required autofocus>
                <div class="form-text">Minimum 8 characters.</div>
                <?php if (isset($errors['password'])): ?><div class="invalid-feedback"><?= e($errors['password']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="password_confirmation">Confirm New Password <span class="required-mark">*</span></label>
                <input class="form-control <?= isset($errors['password_confirmation']) ? 'is-invalid' : '' ?>" id="password_confirmation" name="password_confirmation" type="password" required>
                <?php if (isset($errors['password_confirmation'])): ?><div class="invalid-feedback"><?= e($errors['password_confirmation']) ?></div><?php endif; ?>
            </div>
        </div>
    </section>

    <div class="form-actions">
        <a class="btn btn-outline-secondary" href="/users/<?= e($userItem['id']) ?>">Cancel</a>
        <button class="btn btn-primary" type="submit">Update Password</button>
    </div>
</form>

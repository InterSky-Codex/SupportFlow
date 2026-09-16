<?php $value = static fn (string $key): string => (string) ($values[$key] ?? ''); ?>

<section class="page-heading">
    <div>
        <span class="section-label">Workspace administration</span>
        <h2>Settings</h2>
        <p class="mb-0">Manage the workspace identity and timezone used throughout SupportFlow.</p>
    </div>
</section>

<?php if (!empty($successMessage)): ?>
    <div class="alert alert-success dashboard-alert" role="status"><?= e($successMessage) ?></div>
<?php endif; ?>

<?php if (!empty($errorMessage)): ?>
    <div class="alert alert-danger dashboard-alert" role="alert"><?= e($errorMessage) ?></div>
<?php endif; ?>

<?php if (isset($errors['form'])): ?>
    <div class="alert alert-danger dashboard-alert" role="alert"><?= e($errors['form']) ?></div>
<?php endif; ?>

<form class="ticket-form" action="/settings" method="post" novalidate>
    <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">

    <section class="form-panel">
        <div class="form-panel__header">
            <h3>Workspace Profile</h3>
            <p>These values are applied after saving and reloading the workspace.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <label class="form-label" for="workspace_name">Workspace Name <span class="required-mark">*</span></label>
                <input class="form-control <?= isset($errors['workspace_name']) ? 'is-invalid' : '' ?>" id="workspace_name" name="workspace_name" value="<?= e($value('workspace_name')) ?>" maxlength="100" required>
                <div class="form-text">Shown in application titles and the footer.</div>
                <?php if (isset($errors['workspace_name'])): ?><div class="invalid-feedback"><?= e($errors['workspace_name']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="timezone">Timezone <span class="required-mark">*</span></label>
                <select class="form-select <?= isset($errors['timezone']) ? 'is-invalid' : '' ?>" id="timezone" name="timezone" required>
                    <?php foreach ($timezones as $timezone): ?>
                        <option value="<?= e($timezone) ?>" <?= $value('timezone') === $timezone ? 'selected' : '' ?>><?= e($timezone) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">Used for dates and times displayed by the application.</div>
                <?php if (isset($errors['timezone'])): ?><div class="invalid-feedback"><?= e($errors['timezone']) ?></div><?php endif; ?>
            </div>
        </div>
    </section>

    <div class="form-actions">
        <button class="btn btn-primary" type="submit"><i class="bi bi-check2-circle me-1" aria-hidden="true"></i> Save Settings</button>
    </div>
</form>

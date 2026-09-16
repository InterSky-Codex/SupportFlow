<?php
$value = static fn (string $key): string => (string) ($values[$key] ?? '');
$selected = static fn (string $key, int $id): bool => (int) ($values[$key] ?? 0) === $id;
?>
<section class="page-heading">
    <div>
        <span class="section-label">Ticket management</span>
        <h2><?= e($pageTitle) ?></h2>
        <p class="mb-0">Provide enough detail for the support team to take action.</p>
    </div>
    <a class="btn btn-outline-secondary" href="/tickets"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i> Back to tickets</a>
</section>

<form class="ticket-form" action="<?= e($action) ?>" method="post" enctype="multipart/form-data" novalidate>
    <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
    <?php if ($editing): ?>
    <?php endif; ?>
    <section class="form-panel">
        <div class="form-panel__header">
            <h3>Request details</h3>
            <p>Fields marked required are needed to create a ticket.</p>
        </div>
        <div class="row g-4">
            <div class="col-12">
                <label class="form-label" for="title">Title <span class="required-mark">*</span></label>
                <input class="form-control <?= isset($errors['title']) ? 'is-invalid' : '' ?>" id="title" name="title" value="<?= e($value('title')) ?>" maxlength="180" required>
                <?php if (isset($errors['title'])): ?><div class="invalid-feedback"><?= e($errors['title']) ?></div><?php endif; ?>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="category_id">Category <span class="required-mark">*</span></label>
                <select class="form-select <?= isset($errors['category_id']) ? 'is-invalid' : '' ?>" id="category_id" name="category_id" required>
                    <option value="">Select a category</option>
                    <?php foreach ($options['categories'] as $category): ?>
                        <option value="<?= e($category['id']) ?>" <?= $selected('category_id', (int) $category['id']) ? 'selected' : '' ?>><?= e($category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['category_id'])): ?><div class="invalid-feedback"><?= e($errors['category_id']) ?></div><?php endif; ?>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="priority_id">Priority <span class="required-mark">*</span></label>
                <select class="form-select <?= isset($errors['priority_id']) ? 'is-invalid' : '' ?>" id="priority_id" name="priority_id" required>
                    <option value="">Select priority</option>
                    <?php foreach ($options['priorities'] as $priority): ?>
                        <option value="<?= e($priority['id']) ?>" <?= $selected('priority_id', (int) $priority['id']) ? 'selected' : '' ?>><?= e($priority['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['priority_id'])): ?><div class="invalid-feedback"><?= e($errors['priority_id']) ?></div><?php endif; ?>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="due_date">Due date <span class="text-secondary fw-normal">(optional)</span></label>
                <input class="form-control <?= isset($errors['due_date']) ? 'is-invalid' : '' ?>" id="due_date" name="due_date" type="date" value="<?= e($value('due_date')) ?>">
                <?php if (isset($errors['due_date'])): ?><div class="invalid-feedback"><?= e($errors['due_date']) ?></div><?php endif; ?>
            </div>
            <div class="col-12">
                <label class="form-label" for="description">Description <span class="required-mark">*</span></label>
                <textarea class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>" id="description" name="description" rows="7" maxlength="5000" required><?= e($value('description')) ?></textarea>
                <div class="form-text">Include the issue, affected service or device, and the impact on your work.</div>
                <?php if (isset($errors['description'])): ?><div class="invalid-feedback"><?= e($errors['description']) ?></div><?php endif; ?>
            </div>
            <?php if (!$editing): ?>
                <div class="col-12">
                    <label class="form-label" for="attachment">Attachment <span class="text-secondary fw-normal">(optional)</span></label>
                    <input class="form-control <?= isset($errors['attachment']) ? 'is-invalid' : '' ?>" id="attachment" name="attachment" type="file" accept=".jpg,.jpeg,.png,.pdf,.docx,.xlsx">
                    <div class="form-text">Allowed formats: JPG, PNG, PDF, DOCX, XLSX. Maximum size: 10 MB.</div>
                    <?php if (isset($errors['attachment'])): ?><div class="invalid-feedback"><?= e($errors['attachment']) ?></div><?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
    <div class="form-actions">
        <a class="btn btn-outline-secondary" href="/tickets">Cancel</a>
        <button class="btn btn-primary" type="submit"><?= $editing ? 'Save changes' : 'Create ticket' ?></button>
    </div>
</form>


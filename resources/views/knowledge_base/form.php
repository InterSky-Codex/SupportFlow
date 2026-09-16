<?php
/** @var array<string, mixed> $values */
/** @var array<string, string> $errors */
/** @var bool $editing */
/** @var string $action */
?>
<section class="page-heading">
    <div>
        <span class="section-label">Workspace / Knowledge Base</span>
        <h2><?= $editing ? 'Edit article' : 'Create article' ?></h2>
        <p class="mb-0">Publish internal guidance for agents and support staff.</p>
    </div>
    <a class="btn btn-outline-secondary" href="/knowledge-base">Back</a>
</section>

<section class="table-panel">
    <?php if ($errors !== []): ?>
        <div class="alert alert-danger dashboard-alert" role="alert">
            <?php foreach ($errors as $error): ?>
                <div><?= e((string) $error) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form action="<?= e($action) ?>" method="post" class="row g-3">
        <input type="hidden" name="_token" value="<?= e($csrfToken ?? '') ?>">

        <div class="col-md-8">
            <label class="form-label fw-semibold" for="kb-title">Title</label>
            <input class="form-control" id="kb-title" name="title" value="<?= e((string) ($values['title'] ?? '')) ?>">
        </div>

        <div class="col-md-4">
            <label class="form-label fw-semibold" for="kb-status">Status</label>
            <select class="form-select" id="kb-status" name="status">
                <option value="draft" <?= (($values['status'] ?? 'draft') === 'draft') ? 'selected' : '' ?>>Draft</option>
                <option value="published" <?= (($values['status'] ?? 'draft') === 'published') ? 'selected' : '' ?>>Published</option>
                <option value="archived" <?= (($values['status'] ?? 'draft') === 'archived') ? 'selected' : '' ?>>Archived</option>
            </select>
        </div>

        <div class="col-md-5">
            <label class="form-label fw-semibold" for="kb-slug">Slug</label>
            <input class="form-control" id="kb-slug" name="slug" value="<?= e((string) ($values['slug'] ?? '')) ?>" placeholder="ticket-escalation-guidelines">
        </div>

        <div class="col-md-4">
            <label class="form-label fw-semibold" for="kb-category">Category</label>
            <input class="form-control" id="kb-category" name="category" value="<?= e((string) ($values['category'] ?? 'general')) ?>" placeholder="general">
        </div>

        <div class="col-md-3 d-flex align-items-end">
            <div class="form-check mt-4">
                <input class="form-check-input" id="kb-featured" type="checkbox" name="is_featured" value="1" <?= !empty($values['is_featured']) ? 'checked' : '' ?>>
                <label class="form-check-label" for="kb-featured">Featured</label>
            </div>
        </div>

        <div class="col-12">
            <label class="form-label fw-semibold" for="kb-summary">Summary</label>
            <textarea class="form-control" id="kb-summary" name="summary" rows="3"><?= e((string) ($values['summary'] ?? '')) ?></textarea>
        </div>

        <div class="col-12">
            <label class="form-label fw-semibold" for="kb-content">Content</label>
            <textarea class="form-control" id="kb-content" name="content" rows="15"><?= e((string) ($values['content'] ?? '')) ?></textarea>
        </div>

        <div class="col-12 d-flex justify-content-end gap-2">
            <a class="btn btn-light" href="/knowledge-base">Cancel</a>
            <button class="btn btn-primary" type="submit"><?= $editing ? 'Update article' : 'Create article' ?></button>
        </div>
    </form>
</section>

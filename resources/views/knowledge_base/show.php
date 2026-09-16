<?php
/** @var array<string, mixed> $article */
/** @var array<string, mixed> $currentUser */
?>
<section class="page-heading">
    <div>
        <span class="section-label">Knowledge Base / Article</span>
        <h2><?= e((string) ($article['title'] ?? 'Article')) ?></h2>
        <p class="mb-0 text-muted">
            <span class="badge bg-light text-dark border me-2"><?= e((string) ($article['category'] ?? 'general')) ?></span>
            <?= e((string) ($article['author_name'] ?? 'SupportFlow')) ?>
            <span class="mx-2">•</span>
            <?= e(date('d M Y', strtotime((string) ($article['published_at'] ?? $article['created_at'] ?? 'now')))) ?>
            <span class="mx-2">•</span>
            <?= e((string) ($article['view_count'] ?? 0)) ?> views
        </p>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="/knowledge-base">Back to knowledge base</a>
        <?php if (($currentUser['role'] ?? '') === 'administrator'): ?>
            <a class="btn btn-primary" href="/knowledge-base/<?= e((string) ($article['id'])) ?>/edit">Edit article</a>
        <?php endif; ?>
    </div>
</section>

<section class="table-panel article-content">
    <div class="mb-4">
        <?php if (!empty($article['is_featured'])): ?>
            <span class="badge bg-warning-subtle text-warning-emphasis">Featured article</span>
        <?php endif; ?>
    </div>

    <div class="article-body">
        <?= nl2br(e((string) ($article['content'] ?? ''))); ?>
    </div>
</section>

<?php
/** @var array<string, mixed> $filters */
/** @var array<string, mixed> $listing */
/** @var array<int, string> $categories */
/** @var array<string, mixed> $currentUser */
?>
<section class="page-heading">
    <div>
        <span class="section-label">Workspace / Knowledge Base</span>
        <h2>Knowledge Base</h2>
        <p class="mb-0">Quick answers, playbooks, and operational guidance for the support team.</p>
    </div>
    <?php if (($currentUser['role'] ?? '') === 'administrator'): ?>
        <a class="btn btn-primary" href="/knowledge-base/create">Create article</a>
    <?php endif; ?>
</section>

<section class="table-panel">
    <form class="ticket-filters" action="/knowledge-base" method="get">
        <div class="flex-grow-1">
            <label class="form-label small fw-semibold text-muted mb-1" for="knowledge-search">Search</label>
            <input class="form-control" id="knowledge-search" type="text" name="search" value="<?= e((string) ($filters['search'] ?? '')) ?>" placeholder="Search title, summary, or content">
        </div>
        <div>
            <label class="form-label small fw-semibold text-muted mb-1" for="knowledge-category">Category</label>
            <select class="form-select" id="knowledge-category" name="category">
                <option value="">All categories</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= e($category) ?>" <?= ($filters['category'] ?? '') === $category ? 'selected' : '' ?>><?= e($category) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="d-flex gap-2 align-items-end">
            <button class="btn btn-outline-secondary" type="submit">Apply</button>
            <a class="btn btn-light" href="/knowledge-base">Clear</a>
        </div>
    </form>

    <?php if (($listing['items'] ?? []) === []): ?>
        <?php
        $emptyStateIcon = 'bi-book-half';
        $emptyStateTitle = 'No knowledge base articles yet';
        $emptyStateDescription = 'Try a different search or check back later for a new article.';
        require __DIR__ . '/../components/empty-state.php';
        ?>
    <?php else: ?>
        <div class="knowledge-grid">
            <?php foreach ($listing['items'] as $article): ?>
                <article class="card h-100 border-0 shadow-sm">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                            <span class="badge bg-light text-dark border"><?= e((string) ($article['category'] ?? 'general')) ?></span>
                            <?php if (!empty($article['is_featured'])): ?>
                                <span class="badge bg-warning-subtle text-warning-emphasis">Featured</span>
                            <?php endif; ?>
                        </div>
                        <h3 class="h5 mb-2"><?= e((string) ($article['title'] ?? '')) ?></h3>
                        <p class="text-muted flex-grow-1"><?= e((string) ($article['summary'] ?? '')) ?></p>
                        <div class="small text-muted mb-3">
                            <span>By <?= e((string) ($article['author_name'] ?? 'SupportFlow')) ?></span>
                            <span class="mx-2">•</span>
                            <span><?= e(date('d M Y', strtotime((string) ($article['published_at'] ?? $article['created_at'] ?? 'now')))) ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-auto">
                            <a class="btn btn-sm btn-outline-primary" href="/knowledge-base/<?= e((string) ($article['id'])) ?>">Read article</a>
                            <span class="small text-muted"><?= e((string) ($article['view_count'] ?? 0)) ?> views</span>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <?php
        $currentPage = (int) ($listing['page'] ?? 1);
        $totalPages = (int) ($listing['total_pages'] ?? 1);
        $paginationFilters = array_filter([
            'search' => $filters['search'] ?? '',
            'category' => $filters['category'] ?? '',
        ], static fn ($value): bool => $value !== '' && $value !== null);
        $paginationPath = '/knowledge-base';
        require __DIR__ . '/../components/pagination.php';
        ?>
    <?php endif; ?>
</section>

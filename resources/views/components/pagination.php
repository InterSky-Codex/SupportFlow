<?php

/** @var int $currentPage */
/** @var int $totalPages */
/** @var array<string, mixed> $paginationFilters */
/** @var string $paginationPath */
?>
<?php $paginationPath = $paginationPath ?? '/tickets'; ?>
<?php if ($totalPages > 1): ?>
    <nav class="table-pagination" aria-label="Pagination">
        <?php
        $currentPage = max(1, min($totalPages, (int) $currentPage));
        $visiblePages = $totalPages <= 7
            ? range(1, $totalPages)
            : array_unique(array_merge(
                [1, $totalPages],
                range(max(1, $currentPage - 2), min($totalPages, $currentPage + 2))
            ));
        sort($visiblePages);
        $pageUrl = static function (int $page) use ($paginationFilters, $paginationPath): string {
            $query = http_build_query(array_filter(
                array_replace($paginationFilters, ['page' => $page]),
                static fn ($value): bool => $value !== '' && $value !== null
            ));

            return e($paginationPath) . '?' . e($query);
        };
        $renderPageLink = static function (int $page, string $label, string $ariaLabel, bool $isActive = false) use ($pageUrl): void {
            ?>
            <a class="<?= $isActive ? 'active' : '' ?>" href="<?= $pageUrl($page) ?>" aria-label="<?= e($ariaLabel) ?>" <?= $isActive ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
            <?php
        };
        ?>
        <?php if ($currentPage > 1): ?>
            <?php $renderPageLink(1, '«', 'First page'); ?>
            <?php $renderPageLink($currentPage - 1, '‹', 'Previous page'); ?>
        <?php else: ?>
            <a class="disabled" style="opacity: .5; pointer-events: none;" aria-disabled="true" tabindex="-1" aria-label="First page">«</a>
            <a class="disabled" style="opacity: .5; pointer-events: none;" aria-disabled="true" tabindex="-1" aria-label="Previous page">‹</a>
        <?php endif; ?>

        <?php $previousPage = 0; ?>
        <?php foreach ($visiblePages as $page): ?>
            <?php if ($previousPage > 0 && $page > $previousPage + 1): ?>
                <span class="ellipsis" aria-hidden="true">...</span>
            <?php endif; ?>
            <?php $renderPageLink($page, (string) $page, 'Page ' . $page, $page === $currentPage); ?>
            <?php $previousPage = $page; ?>
        <?php endforeach; ?>

        <?php if ($currentPage < $totalPages): ?>
            <?php $renderPageLink($currentPage + 1, '›', 'Next page'); ?>
            <?php $renderPageLink($totalPages, '»', 'Last page'); ?>
        <?php else: ?>
            <a class="disabled" style="opacity: .5; pointer-events: none;" aria-disabled="true" tabindex="-1" aria-label="Next page">›</a>
            <a class="disabled" style="opacity: .5; pointer-events: none;" aria-disabled="true" tabindex="-1" aria-label="Last page">»</a>
        <?php endif; ?>
    </nav>
<?php endif; ?>

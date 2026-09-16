<?php

/** @var string $emptyStateIcon */
/** @var string $emptyStateTitle */
/** @var string $emptyStateDescription */
?>
<div class="empty-state">
    <span class="empty-state__icon"><i class="bi <?= e($emptyStateIcon) ?>" aria-hidden="true"></i></span>
    <h3><?= e($emptyStateTitle) ?></h3>
    <p class="mb-0"><?= e($emptyStateDescription) ?></p>
</div>

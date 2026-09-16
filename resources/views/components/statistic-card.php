<?php

/** @var array{label: string, value: int, icon: string, tone: string, description: string} $statistic */
?>
<article class="statistic-card h-100">
    <div class="statistic-card__header">
        <span class="statistic-card__icon statistic-card__icon--<?= e($statistic['tone']) ?>">
            <i class="bi <?= e($statistic['icon']) ?>" aria-hidden="true"></i>
        </span>
        <span class="statistic-card__label"><?= e($statistic['label']) ?></span>
    </div>
    <p class="statistic-card__value"><?= e($statistic['value']) ?></p>
    <p class="statistic-card__description mb-0"><?= e($statistic['description']) ?></p>
</article>

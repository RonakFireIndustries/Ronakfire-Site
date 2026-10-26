<?php
/** @var array $categories */
/** @var array $counts */
/** @var array $filters */
/** @var array|null $activeCategory */
$filterIsConfigurable = !empty($filters['configurable']);
?>
<div class="filter-group">
    <h2 class="filter-group__title">Category</h2>
    <nav class="filter-list" aria-label="Product categories">
        <a class="filter-list__item <?= empty($filters['category']) ? 'is-active' : '' ?>" href="/catalog<?= !empty($filters['q']) ? '?q=' . urlencode((string) $filters['q']) : '' ?>">
            <span>All products</span>
            <span><?= array_sum($counts) ?></span>
        </a>
        <?php foreach ($categories as $category): ?>
            <a class="filter-list__item <?= ($filters['category'] ?? '') === $category['slug'] ? 'is-active' : '' ?>" href="/catalog/<?= e($category['slug']) ?>">
                <span><?= e($category['short_name'] ?: $category['name']) ?></span>
                <span><?= (int) ($counts[$category['slug']] ?? 0) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
</div>

<div class="filter-group">
    <h2 class="filter-group__title">Product type</h2>
    <nav class="filter-list" aria-label="Product type">
        <a class="filter-list__item <?= !$filterIsConfigurable ? 'is-active' : '' ?>" href="/catalog<?= !empty($filters['category']) ? '/' . e($filters['category']) : '' ?><?= !empty($filters['q']) ? '?q=' . urlencode((string) $filters['q']) : '' ?>">
            <span>All types</span>
        </a>
        <a class="filter-list__item <?= $filterIsConfigurable ? 'is-active' : '' ?>" href="/catalog<?= !empty($filters['category']) ? '/' . e($filters['category']) : '' ?>?configurable=1<?= !empty($filters['q']) ? '&q=' . urlencode((string) $filters['q']) : '' ?>">
            <span>Configurable</span>
            <?= icon('spark') ?>
        </a>
    </nav>
</div>

<div class="filter-help">
    <span class="filter-help__icon"><?= icon('clipboard') ?></span>
    <strong>Custom specification?</strong>
    <p>Send your BOQ, drawing or size schedule and we will quote it against the factory.</p>
    <a class="text-link" href="/quote">Request a quote <?= icon('arrow-right') ?></a>
</div>

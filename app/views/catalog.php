<?php
/** @var array $products */
/** @var array $categories */
/** @var array $counts */
/** @var array|null $activeCategory */
/** @var array $filters */
/** @var int $total */
/** @var int $page */
/** @var int $pages */
$pageTitle = $pageTitle ?? 'Catalog';
$catalogTitle = $activeCategory['name'] ?? ($filters['q'] ? 'Search results' : 'All fire safety products');
$catalogLede = $activeCategory['tagline'] ?? 'The complete Ronak Fire range — engineered equipment for hydrant systems, extinguishing, hose reels and project-specific fabrication.';
$sortOptions = [
    '' => 'Recommended',
    'price_asc' => 'Price: low to high',
    'price_desc' => 'Price: high to low',
    'newest' => 'Newest first',
];
?>
<section class="shop-hero">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="/">Home</a><?= icon('chevron-right') ?>
            <a href="/catalog">Shop</a>
            <?php if ($activeCategory): ?><?= icon('chevron-right') ?><span><?= e($activeCategory['name']) ?></span><?php endif; ?>
        </nav>
        <div class="shop-hero__layout">
            <div class="shop-hero__copy">
                <p class="eyebrow"><span></span> <?= $activeCategory ? 'Category range' : 'Complete product range' ?></p>
                <h1><?= $activeCategory ? e($activeCategory['name']) : 'Fire Safety Equipment' ?></h1>
                <p class="shop-hero__lede"><?= $activeCategory && !empty($activeCategory['tagline']) ? e($activeCategory['tagline']) : 'Engineered, tested and manufactured in-house for hydrant systems, extinguishers, hose reels, cabinets and project-specific fabrication.' ?></p>
                <div class="shop-hero__stats">
                    <div><strong><?= (int) $total ?></strong><span><?= $total === 1 ? 'Product' : 'Products' ?></span></div>
                    <div><strong>IS/BIS</strong><span>Tested standards</span></div>
                    <div><strong>In-house</strong><span>Palghar factory</span></div>
                </div>
                <div class="shop-hero__actions">
                    <a class="btn btn--primary" href="/quote">Get project quote <?= icon('arrow-right') ?></a>
                    <a class="btn btn--ghost" href="/contact">Speak to an engineer</a>
                </div>
            </div>
            <?php if ($activeCategory && !empty($activeCategory['image'])): ?>
                <div class="shop-hero__media">
                    <img src="<?= e($activeCategory['image']) ?>" alt="<?= e($activeCategory['name']) ?>" loading="lazy">
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="section section--tight">
    <div class="container">
        <div class="catalog-toolbar">
            <div class="catalog-toolbar__left">
                <button class="btn btn--outline btn--sm catalog-filter-toggle" type="button" data-filter-toggle>
                    <?= icon('filter') ?> Filters
                    <?php if (!empty($filters['category']) || !empty($filters['configurable'])): ?><span class="catalog-filter-toggle__dot"></span><?php endif; ?>
                </button>
                <p class="catalog-count">Showing <strong><?= count($products) ?></strong> of <?= (int) $total ?></p>
            </div>
        </div>

        <div class="catalog-layout" data-catalog-layout>
            <aside class="catalog-sidebar" aria-label="Filters">
                <div class="catalog-sidebar__head">
                    <strong>Filter products</strong>
                    <button class="icon-btn" type="button" data-filter-close aria-label="Close filters"><?= icon('close') ?></button>
                </div>
                <?php partial('catalog-filters', ['categories' => $categories, 'counts' => $counts, 'filters' => $filters, 'activeCategory' => $activeCategory]); ?>
            </aside>
            <div class="filter-scrim" data-filter-close hidden></div>

            <div class="catalog-results">
                <?php if ($products): ?>
                    <div class="product-grid product-grid--catalog">
                        <?php foreach ($products as $product): ?>
                            <?php partial('product-card', ['product' => $product]); ?>
                        <?php endforeach; ?>
                    </div>
                    <?php partial('pagination', ['page' => $page, 'pages' => $pages, 'baseUrl' => current_path()]); ?>
                <?php else: ?>
                    <div class="empty-state">
                        <span class="empty-state__icon"><?= icon('search') ?></span>
                        <h2>No products match this view</h2>
                        <p>Try a different category or search term. If you need something bespoke, our team can quote from a drawing.</p>
                        <div class="empty-state__actions">
                            <a class="btn btn--primary" href="/catalog">Clear filters</a>
                            <a class="btn btn--outline" href="/quote">Request a quote</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

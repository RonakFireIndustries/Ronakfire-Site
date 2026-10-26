<?php
/** @var array $products */
/** @var array $categories */
/** @var array $filters */
/** @var array $pager */
?>
<div class="admin-page-head">
    <div>
        <p class="admin-eyebrow">Catalog</p>
        <h2><?= (int) $pager['total'] ?> products</h2>
        <p>Prices are in ₹ and stored exclusive of GST. Option deltas drive the configurator.</p>
    </div>
    <div class="admin-page-head__actions">
        <a class="admin-btn admin-btn--primary" href="/admin/products/new"><?= icon('plus') ?> Add product</a>
    </div>
</div>

<form class="admin-filters" action="/admin/products" method="get">
    <label class="admin-search">
        <span class="sr-only">Search</span>
        <?= icon('search') ?>
        <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Search name, SKU or slug…">
    </label>
    <label class="admin-select">
        <span class="sr-only">Category</span>
        <select name="category">
            <option value="">All categories</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= e($category['slug']) ?>" <?= $filters['category'] === $category['slug'] ? 'selected' : '' ?>><?= e($category['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label class="admin-select">
        <span class="sr-only">Type</span>
        <select name="type">
            <option value="">All types</option>
            <?php foreach (['simple' => 'Simple', 'configurable' => 'Configurable', 'bundle' => 'Bundle / kit'] as $value => $label): ?>
                <option value="<?= $value ?>" <?= $filters['type'] === $value ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label class="admin-select">
        <span class="sr-only">Visibility</span>
        <select name="active">
            <option value="">Any visibility</option>
            <option value="1" <?= $filters['active'] === '1' ? 'selected' : '' ?>>Visible</option>
            <option value="0" <?= $filters['active'] === '0' ? 'selected' : '' ?>>Hidden</option>
        </select>
    </label>
    <button class="admin-btn" type="submit">Filter</button>
    <?php if ($filters['q'] || $filters['category'] || $filters['type'] || $filters['active'] !== ''): ?>
        <a class="admin-btn admin-btn--ghost" href="/admin/products">Clear</a>
    <?php endif; ?>
</form>

<section class="admin-panel">
    <?php if ($products): ?>
        <div class="admin-table-wrap">
            <table class="admin-table admin-table--products">
                <thead>
                <tr><th></th><th>Product</th><th>Category</th><th>Type</th><th class="is-right">Base price</th><th>Status</th><th class="is-right">Actions</th></tr>
                </thead>
                <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td class="admin-table__thumb">
                            <?php if (!empty($product['image'])): ?><img src="<?= e($product['image']) ?>" alt="" loading="lazy"><?php else: ?><span><?= icon('package') ?></span><?php endif; ?>
                        </td>
                        <td>
                            <a class="admin-table__link" href="/admin/products/<?= (int) $product['id'] ?>/edit"><?= e($product['name']) ?></a>
                            <small><?= e($product['sku'] ?: 'No SKU') ?> · <?= e($product['slug']) ?></small>
                        </td>
                        <td><?= e($product['category_name']) ?></td>
                        <td><span class="admin-tag admin-tag--<?= e($product['product_type']) ?>"><?= e(ucfirst($product['product_type'])) ?></span></td>
                        <td class="is-right"><?= money((int) $product['base_price_paise']) ?></td>
                        <td>
                            <?php if ((int) $product['is_active'] === 1): ?><span class="admin-status admin-status--visible">Visible</span>
                            <?php else: ?><span class="admin-status admin-status--hidden">Hidden</span><?php endif; ?>
                            <?php if ((int) $product['is_featured'] === 1): ?><span class="admin-tag">Featured</span><?php endif; ?>
                        </td>
                        <td class="is-right">
                            <div class="admin-row-actions">
                                <a href="/product/<?= e($product['slug']) ?>" target="_blank" rel="noopener" title="View on storefront"><?= icon('eye') ?></a>
                                <a href="/admin/products/<?= (int) $product['id'] ?>/edit" title="Edit"><?= icon('edit') ?></a>
                                <form action="/admin/products/<?= (int) $product['id'] ?>/toggle" method="post">
                                    <?= csrf_field() ?>
                                    <button type="submit" title="<?= (int) $product['is_active'] === 1 ? 'Hide' : 'Publish' ?>"><?= (int) $product['is_active'] === 1 ? icon('close') : icon('check') ?></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php partial('admin/partials/pagination', ['pager' => $pager, 'baseUrl' => '/admin/products']); ?>
    <?php else: ?>
        <div class="admin-empty"><?= icon('package') ?><p>No products match these filters.</p><a class="admin-btn" href="/admin/products">Clear filters</a></div>
    <?php endif; ?>
</section>

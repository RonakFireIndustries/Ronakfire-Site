<?php
/** @var array $categories */
/** @var array $productCounts */
?>
<div class="admin-page-head">
    <div>
        <p class="admin-eyebrow">Catalog</p>
        <h2>Categories</h2>
        <p>Reorder the storefront navigation, update imagery and control what is visible.</p>
    </div>
</div>

<form class="admin-form" action="/admin/categories/save" method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <div class="admin-stack">
        <?php foreach ($categories as $index => $category): ?>
            <section class="admin-panel admin-panel--form">
                <div class="admin-panel__head">
                    <div>
                        <h2><?= e($category['name']) ?></h2>
                        <p><?= (int) ($productCounts[$category['slug']] ?? 0) ?> active products · <?= e($category['slug']) ?></p>
                    </div>
                    <a class="admin-link" href="/admin/products?category=<?= e($category['slug']) ?>">View products <?= icon('arrow-right') ?></a>
                </div>
                <div class="admin-form-body">
                    <div class="admin-category-row">
                        <div class="admin-category-row__media">
                            <?php if (!empty($category['image'])): ?><img src="<?= e($category['image']) ?>" alt=""><?php else: ?><?= icon('package') ?><?php endif; ?>
                        </div>
                        <div class="admin-field-grid">
                            <input type="hidden" name="categories[<?= (int) $category['id'] ?>][id]" value="<?= (int) $category['id'] ?>">
                            <label class="admin-field">
                                <span>Name</span>
                                <input type="text" name="categories[<?= (int) $category['id'] ?>][name]" value="<?= e($category['name']) ?>" required>
                            </label>
                            <label class="admin-field">
                                <span>Short name</span>
                                <input type="text" name="categories[<?= (int) $category['id'] ?>][short_name]" value="<?= e($category['short_name']) ?>">
                            </label>
                            <label class="admin-field">
                                <span>Slug</span>
                                <input type="text" name="categories[<?= (int) $category['id'] ?>][slug]" value="<?= e($category['slug']) ?>">
                            </label>
                            <label class="admin-field">
                                <span>Sort order</span>
                                <input type="number" name="categories[<?= (int) $category['id'] ?>][sort_order]" value="<?= (int) $category['sort_order'] ?>">
                            </label>
                            <label class="admin-field admin-field--full">
                                <span>Tagline</span>
                                <input type="text" name="categories[<?= (int) $category['id'] ?>][tagline]" value="<?= e($category['tagline']) ?>" placeholder="One short line used on cards and the mega-menu">
                            </label>
                            <label class="admin-field admin-field--full">
                                <span>Description</span>
                                <textarea name="categories[<?= (int) $category['id'] ?>][description]" rows="2"><?= e($category['description']) ?></textarea>
                            </label>
                            <label class="admin-field">
                                <span>Image URL</span>
                                <input type="text" name="categories[<?= (int) $category['id'] ?>][image]" value="<?= e($category['image']) ?>">
                            </label>
                            <label class="admin-file admin-file--inline">
                                <span><?= icon('download') ?> Upload image</span>
                                <input type="file" name="image_file_<?= (int) $category['id'] ?>" accept="image/jpeg,image/png,image/webp">
                            </label>
                            <label class="admin-toggle admin-toggle--inline">
                                <input type="checkbox" name="categories[<?= (int) $category['id'] ?>][is_active]" value="1" <?= (int) $category['is_active'] === 1 ? 'checked' : '' ?>>
                                <span><strong>Visible</strong><small>Hidden categories are removed from navigation.</small></span>
                            </label>
                        </div>
                    </div>
                </div>
            </section>
        <?php endforeach; ?>
    </div>

    <div class="admin-form-footer admin-form-footer--sticky">
        <span>Changes to names and slugs update storefront URLs immediately.</span>
        <button class="admin-btn admin-btn--primary" type="submit"><?= icon('check') ?> Save categories</button>
    </div>
</form>

<section class="admin-panel admin-panel--form">
    <div class="admin-panel__head"><div><h2>Add a category</h2><p>Create the category first, then assign products to it.</p></div></div>
    <div class="admin-form-body">
        <form action="/admin/categories/save" method="post" class="admin-add-row">
            <?= csrf_field() ?>
            <label class="admin-field"><span>Name</span><input type="text" name="new_name" placeholder="Fire blankets" required></label>
            <label class="admin-field"><span>Slug</span><input type="text" name="new_slug" placeholder="fire-blankets"></label>
            <label class="admin-field"><span>Tagline</span><input type="text" name="new_tagline" placeholder="Quick-response suppression"></label>
            <label class="admin-field admin-field--qty"><span>Sort</span><input type="number" name="new_sort_order" value="99"></label>
            <button class="admin-btn admin-btn--primary" type="submit"><?= icon('plus') ?> Add</button>
        </form>
    </div>
</section>

<?php
/** @var array $product */
/** @var array $categories */
/** @var array $groups */
/** @var array $bundleItems */
/** @var array $allProducts */
/** @var string $specsText */
/** @var string $galleryText */
$p = $product;
$isNew = (int) $p['id'] === 0;
$gstPercent = rtrim(rtrim(number_format((int) $p['gst_rate_bp'] / 100, 2, '.', ''), '0'), '.');
?>
<div class="admin-page-head">
    <div>
        <p class="admin-eyebrow"><a href="/admin/products">Products</a> / <?= $isNew ? 'New' : 'Edit' ?></p>
        <h2><?= $isNew ? 'Create a product' : e($p['name']) ?></h2>
        <p><?= $isNew ? 'Start with the commercial basics, then add configuration options.' : 'Changes go live immediately when the product is visible.' ?></p>
    </div>
    <div class="admin-page-head__actions">
        <a class="admin-btn admin-btn--ghost" href="/admin/products">Cancel</a>
        <?php if (!$isNew): ?><a class="admin-btn" href="/product/<?= e($p['slug']) ?>" target="_blank" rel="noopener"><?= icon('eye') ?> View</a><?php endif; ?>
        <button class="admin-btn admin-btn--primary" type="submit" form="productForm"><?= icon('check') ?> Save product</button>
    </div>
</div>

<form id="productForm" class="admin-form" action="/admin/products/save" method="post" enctype="multipart/form-data" data-product-form>
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">

    <div class="admin-form-grid">
        <div class="admin-form-main">
            <section class="admin-panel admin-panel--form">
                <div class="admin-panel__head"><div><h2>Product basics</h2><p>The name and description customers see.</p></div></div>
                <div class="admin-form-body">
                    <div class="admin-field-grid">
                        <label class="admin-field admin-field--full">
                            <span>Product name <em>*</em></span>
                            <input type="text" name="name" value="<?= e($p['name']) ?>" required data-product-name>
                        </label>
                        <label class="admin-field">
                            <span>URL slug</span>
                            <input type="text" name="slug" value="<?= e($p['slug']) ?>" placeholder="generated-from-name">
                            <small>/product/<?= e($p['slug'] ?: 'your-slug') ?></small>
                        </label>
                        <label class="admin-field">
                            <span>SKU</span>
                            <input type="text" name="sku" value="<?= e($p['sku']) ?>">
                        </label>
                        <label class="admin-field">
                            <span>Category <em>*</em></span>
                            <select name="category_id" required>
                                <option value="">Select category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= (int) $category['id'] ?>" <?= (int) $p['category_id'] === (int) $category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="admin-field">
                            <span>Product type</span>
                            <select name="product_type" data-product-type>
                                <?php foreach (['simple' => 'Simple (fixed price)', 'configurable' => 'Configurable (options/dimensions)', 'bundle' => 'Bundle / kit'] as $value => $label): ?>
                                    <option value="<?= $value ?>" <?= $p['product_type'] === $value ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="admin-field admin-field--full">
                            <span>Short description</span>
                            <textarea name="short_description" rows="2" maxlength="500"><?= e($p['short_description']) ?></textarea>
                            <small>Used on cards and search results.</small>
                        </label>
                        <label class="admin-field admin-field--full">
                            <span>Full description</span>
                            <textarea name="description" rows="5"><?= e($p['description']) ?></textarea>
                        </label>
                        <label class="admin-field admin-field--full">
                            <span>Specifications</span>
                            <textarea name="specs" rows="6" placeholder="Standard: IS 15683:2018&#10;Body: Mild steel&#10;Working pressure: 15 bar"><?= e($specsText) ?></textarea>
                            <small>One <code>Label: Value</code> per line. Rendered as the specification table.</small>
                        </label>
                    </div>
                </div>
            </section>

            <section class="admin-panel admin-panel--form">
                <div class="admin-panel__head"><div><h2>Price &amp; tax</h2><p>All values in ₹, exclusive of GST. Deltas are added by the configurator.</p></div></div>
                <div class="admin-form-body">
                    <div class="admin-field-grid">
                        <label class="admin-field">
                            <span>Base price (₹)</span>
                            <input type="text" name="base_price" value="<?= admin_money_input((int) $p['base_price_paise']) ?>" inputmode="decimal">
                            <small>For configurable products this is added to option deltas.</small>
                        </label>
                        <label class="admin-field">
                            <span>Dimension rate (₹ / m²)</span>
                            <input type="text" name="dim_rate" value="<?= admin_money_input((int) $p['dim_rate_paise_sqm']) ?>" inputmode="decimal">
                            <small>Used by dimension groups: area = 2 × (wh + wd + hd).</small>
                        </label>
                        <label class="admin-field">
                            <span>GST rate (%)</span>
                            <input type="text" name="gst_percent" value="<?= e($gstPercent) ?>" inputmode="decimal">
                        </label>
                        <label class="admin-field">
                            <span>Unit</span>
                            <input type="text" name="unit" value="<?= e($p['unit']) ?>" placeholder="Nos">
                        </label>
                        <label class="admin-field">
                            <span>HSN code</span>
                            <input type="text" name="hsn_code" value="<?= e($p['hsn_code']) ?>">
                        </label>
                        <label class="admin-field">
                            <span>Minimum quantity</span>
                            <input type="number" name="min_qty" value="<?= (int) $p['min_qty'] ?>" min="1">
                        </label>
                        <label class="admin-field">
                            <span>Lead time</span>
                            <input type="text" name="lead_time" value="<?= e($p['lead_time']) ?>" placeholder="7–10 working days">
                        </label>
                        <label class="admin-field">
                            <span>Warranty</span>
                            <input type="text" name="warranty" value="<?= e($p['warranty']) ?>" placeholder="12-month warranty">
                        </label>
                        <label class="admin-field admin-field--full">
                            <span>Price note</span>
                            <input type="text" name="price_note" value="<?= e($p['price_note']) ?>" placeholder="Optional message shown below the description">
                        </label>
                    </div>
                </div>
            </section>

            <section class="admin-panel admin-panel--form">
                <div class="admin-panel__head">
                    <div><h2>Configuration options</h2><p>Radio, checkbox or made-to-measure dimension groups.</p></div>
                    <button class="admin-btn admin-btn--sm" type="button" data-add-group><?= icon('plus') ?> Add group</button>
                </div>
                <div class="admin-form-body">
                    <div class="admin-hint"><?= icon('spark') ?><p>Price delta is added once per unit. Price multiplier is applied to the configured subtotal (10% = 10). Dimension groups use the product's ₹/m² rate.</p></div>

                    <div class="groups-editor" data-groups-editor data-next-group="<?= count($groups) ?>">
                        <?php foreach ($groups as $gi => $group): ?>
                            <article class="group-editor" data-group data-index="<?= $gi ?>">
                                <input type="hidden" name="groups[<?= $gi ?>][id]" value="<?= (int) $group['id'] ?>">
                                <header class="group-editor__head">
                                    <span class="group-editor__handle"><?= icon('filter') ?></span>
                                    <strong>Option group <?= $gi + 1 ?></strong>
                                    <label class="admin-check"><input type="checkbox" name="groups[<?= $gi ?>][is_required]" value="1" <?= (int) $group['is_required'] === 1 ? 'checked' : '' ?>><span>Required</span></label>
                                    <button class="admin-icon-btn admin-icon-btn--danger" type="button" data-remove-group title="Remove group"><?= icon('trash') ?></button>
                                </header>
                                <div class="group-editor__body">
                                    <div class="admin-field-grid admin-field-grid--3">
                                        <label class="admin-field"><span>Label</span><input type="text" name="groups[<?= $gi ?>][name]" value="<?= e($group['name']) ?>" placeholder="Capacity"></label>
                                        <label class="admin-field"><span>Slug</span><input type="text" name="groups[<?= $gi ?>][slug]" value="<?= e($group['slug']) ?>" placeholder="capacity"></label>
                                        <label class="admin-field"><span>Input type</span>
                                            <select name="groups[<?= $gi ?>][input_type]" data-group-type>
                                                <option value="radio" <?= $group['input_type'] === 'radio' ? 'selected' : '' ?>>Single choice (radio)</option>
                                                <option value="checkbox" <?= $group['input_type'] === 'checkbox' ? 'selected' : '' ?>>Multiple choice (checkbox)</option>
                                                <option value="dimension" <?= $group['input_type'] === 'dimension' ? 'selected' : '' ?>>Made to measure (dimensions)</option>
                                            </select>
                                        </label>
                                        <label class="admin-field admin-field--full"><span>Help text</span><input type="text" name="groups[<?= $gi ?>][help_text]" value="<?= e($group['help_text']) ?>" placeholder="Shown under the group label"></label>
                                    </div>

                                    <div class="dimension-fields" data-dimension-fields <?= $group['input_type'] === 'dimension' ? '' : 'hidden' ?>>
                                        <div class="admin-field-grid admin-field-grid--3">
                                            <label class="admin-field"><span>Min (mm)</span><input type="number" name="groups[<?= $gi ?>][min_value]" value="<?= e((string) $group['min_value']) ?>"></label>
                                            <label class="admin-field"><span>Max (mm)</span><input type="number" name="groups[<?= $gi ?>][max_value]" value="<?= e((string) $group['max_value']) ?>"></label>
                                            <label class="admin-field"><span>Step (mm)</span><input type="number" name="groups[<?= $gi ?>][step_value]" value="<?= e((string) $group['step_value']) ?>"></label>
                                            <label class="admin-field"><span>Default width</span><input type="number" name="groups[<?= $gi ?>][default_w]" value="<?= e((string) $group['default_w']) ?>"></label>
                                            <label class="admin-field"><span>Default height</span><input type="number" name="groups[<?= $gi ?>][default_h]" value="<?= e((string) $group['default_h']) ?>"></label>
                                            <label class="admin-field"><span>Default depth</span><input type="number" name="groups[<?= $gi ?>][default_d]" value="<?= e((string) $group['default_d']) ?>"></label>
                                            <label class="admin-field"><span>Unit label</span><input type="text" name="groups[<?= $gi ?>][unit_label]" value="<?= e($group['unit_label']) ?>" placeholder="mm"></label>
                                        </div>
                                    </div>

                                    <div class="values-editor" data-values-editor <?= $group['input_type'] === 'dimension' ? 'hidden' : '' ?>>
                                        <div class="values-editor__head"><span>Values</span><span>Delta (₹)</span><span>Multiplier (%)</span><span>Default</span><span></span></div>
                                        <div data-values-body>
                                            <?php foreach ($group['values'] as $vi => $value): ?>
                                                <div class="value-row" data-value-row>
                                                    <input type="hidden" name="groups[<?= $gi ?>][values][<?= $vi ?>][id]" value="<?= (int) $value['id'] ?>">
                                                    <label class="admin-field"><span class="sr-only">Label</span><input type="text" name="groups[<?= $gi ?>][values][<?= $vi ?>][label]" value="<?= e($value['label']) ?>" placeholder="4 kg"></label>
                                                    <label class="admin-field"><span class="sr-only">SKU suffix</span><input type="text" name="groups[<?= $gi ?>][values][<?= $vi ?>][sku_suffix]" value="<?= e($value['sku_suffix']) ?>" placeholder="SKU suffix"></label>
                                                    <label class="admin-field"><span class="sr-only">Price delta</span><input type="text" name="groups[<?= $gi ?>][values][<?= $vi ?>][price_delta]" value="<?= admin_money_input((int) $value['price_delta_paise']) ?>" inputmode="decimal"></label>
                                                    <label class="admin-field"><span class="sr-only">Multiplier</span><input type="text" name="groups[<?= $gi ?>][values][<?= $vi ?>][price_multiplier]" value="<?= rtrim(rtrim(number_format((int) $value['price_multiplier_bp'] / 100, 2, '.', ''), '0'), '.') ?>" inputmode="decimal"></label>
                                                    <label class="admin-check"><input type="checkbox" name="groups[<?= $gi ?>][values][<?= $vi ?>][is_default]" value="1" <?= (int) $value['is_default'] === 1 ? 'checked' : '' ?>><span class="sr-only">Default</span></label>
                                                    <button class="admin-icon-btn admin-icon-btn--danger" type="button" data-remove-value title="Remove value"><?= icon('close') ?></button>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <button class="admin-btn admin-btn--sm admin-btn--ghost" type="button" data-add-value><?= icon('plus') ?> Add value</button>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                        <?php if (!$groups): ?>
                            <div class="admin-empty admin-empty--sm" data-empty-groups><?= icon('spark') ?><p>No options yet. Add a group to make this product configurable.</p></div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <section class="admin-panel admin-panel--form" data-bundle-section <?= $p['product_type'] === 'bundle' ? '' : 'hidden' ?>>
                <div class="admin-panel__head">
                    <div><h2>Bundle contents</h2><p>Components shown with the kit. The kit price is the product base price.</p></div>
                    <button class="admin-btn admin-btn--sm" type="button" data-add-bundle><?= icon('plus') ?> Add component</button>
                </div>
                <div class="admin-form-body">
                    <div class="bundle-editor" data-bundle-editor>
                        <?php foreach ($bundleItems as $bi => $item): ?>
                            <div class="bundle-row" data-bundle-row>
                                <label class="admin-field"><span class="sr-only">Component</span>
                                    <select name="bundle_items[<?= $bi ?>][component_product_id]">
                                        <option value="">Choose a product</option>
                                        <?php foreach ($allProducts as $option): ?>
                                            <option value="<?= (int) $option['id'] ?>" <?= (int) $item['id'] === (int) $option['id'] ? 'selected' : '' ?>><?= e($option['name']) ?><?= $option['sku'] ? ' · ' . e($option['sku']) : '' ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label class="admin-field admin-field--qty"><span class="sr-only">Quantity</span><input type="number" name="bundle_items[<?= $bi ?>][qty]" value="<?= (int) $item['qty'] ?>" min="1"></label>
                                <button class="admin-icon-btn admin-icon-btn--danger" type="button" data-remove-bundle title="Remove"><?= icon('trash') ?></button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="admin-hint-text"><?= icon('alert') ?> Bundle component pricing is not added on top of the kit price. Components are presentational.</p>
                </div>
            </section>
        </div>

        <aside class="admin-form-aside">
            <section class="admin-panel admin-panel--form">
                <div class="admin-panel__head"><div><h2>Visibility</h2></div></div>
                <div class="admin-form-body">
                    <label class="admin-toggle"><input type="checkbox" name="is_active" value="1" <?= (int) $p['is_active'] === 1 ? 'checked' : '' ?>><span><strong>Visible in the store</strong><small>Hidden products return 404 to customers.</small></span></label>
                    <label class="admin-toggle"><input type="checkbox" name="is_featured" value="1" <?= (int) $p['is_featured'] === 1 ? 'checked' : '' ?>><span><strong>Featured product</strong><small>Shown on the storefront home page.</small></span></label>
                    <label class="admin-field"><span>Sort order</span><input type="number" name="sort_order" value="<?= (int) $p['sort_order'] ?>"><small>Lower numbers appear first.</small></label>
                </div>
            </section>

            <section class="admin-panel admin-panel--form">
                <div class="admin-panel__head"><div><h2>Images &amp; documents</h2></div></div>
                <div class="admin-form-body">
                    <div class="image-field">
                        <div class="image-field__preview" data-image-preview>
                            <?php if (!empty($p['image'])): ?><img src="<?= e($p['image']) ?>" alt=""><?php else: ?><?= icon('package') ?><?php endif; ?>
                        </div>
                        <label class="admin-field"><span>Image URL</span><input type="text" name="image" value="<?= e($p['image']) ?>" placeholder="/images/products/…" data-image-url></label>
                        <label class="admin-file"><span><?= icon('download') ?> Upload replacement</span><input type="file" name="image_file" accept="image/jpeg,image/png,image/webp"></label>
                        <small>JPG, PNG or WebP, max 4 MB. Upload overrides the URL.</small>
                    </div>
                    <label class="admin-field"><span>Gallery URLs</span><textarea name="gallery" rows="4" placeholder="One image URL per line"><?= e($galleryText) ?></textarea></label>
                    <label class="admin-field"><span>Datasheet URL</span><input type="text" name="datasheet" value="<?= e($p['datasheet']) ?>" placeholder="/images/pdf/…pdf"></label>
                </div>
            </section>

            <section class="admin-panel admin-panel--form">
                <div class="admin-panel__head"><div><h2>Search listing</h2></div></div>
                <div class="admin-form-body">
                    <label class="admin-field"><span>Meta title</span><input type="text" name="meta_title" value="<?= e($p['meta_title']) ?>" maxlength="255"></label>
                    <label class="admin-field"><span>Meta description</span><textarea name="meta_description" rows="3" maxlength="255"><?= e($p['meta_description']) ?></textarea></label>
                </div>
            </section>

            <section class="admin-panel admin-panel--form">
                <div class="admin-panel__head"><div><h2>Record</h2></div></div>
                <div class="admin-form-body admin-record">
                    <div><span>ID</span><strong><?= $isNew ? 'New' : (int) $p['id'] ?></strong></div>
                    <div><span>Created</span><strong><?= e(format_date($p['created_at'])) ?></strong></div>
                    <div><span>Updated</span><strong><?= e(format_date($p['updated_at'])) ?></strong></div>
                </div>
            </section>
        </aside>
    </div>

    <div class="admin-form-footer">
        <span><?= $isNew ? 'The product will be created and can be configured further.' : 'Saving replaces the option set shown above.' ?></span>
        <div>
            <a class="admin-btn admin-btn--ghost" href="/admin/products">Cancel</a>
            <button class="admin-btn admin-btn--primary" type="submit"><?= icon('check') ?> Save product</button>
        </div>
    </div>
</form>

<template id="groupTemplate">
    <article class="group-editor" data-group data-index="__GROUP__">
        <input type="hidden" name="groups[__GROUP__][id]" value="0">
        <header class="group-editor__head">
            <span class="group-editor__handle"><?= icon('filter') ?></span>
            <strong>New option group</strong>
            <label class="admin-check"><input type="checkbox" name="groups[__GROUP__][is_required]" value="1" checked><span>Required</span></label>
            <button class="admin-icon-btn admin-icon-btn--danger" type="button" data-remove-group title="Remove group"><?= icon('trash') ?></button>
        </header>
        <div class="group-editor__body">
            <div class="admin-field-grid admin-field-grid--3">
                <label class="admin-field"><span>Label</span><input type="text" name="groups[__GROUP__][name]" placeholder="Capacity"></label>
                <label class="admin-field"><span>Slug</span><input type="text" name="groups[__GROUP__][slug]" placeholder="capacity"></label>
                <label class="admin-field"><span>Input type</span>
                    <select name="groups[__GROUP__][input_type]" data-group-type>
                        <option value="radio">Single choice (radio)</option>
                        <option value="checkbox">Multiple choice (checkbox)</option>
                        <option value="dimension">Made to measure (dimensions)</option>
                    </select>
                </label>
                <label class="admin-field admin-field--full"><span>Help text</span><input type="text" name="groups[__GROUP__][help_text]" placeholder="Shown under the group label"></label>
            </div>
            <div class="dimension-fields" data-dimension-fields hidden>
                <div class="admin-field-grid admin-field-grid--3">
                    <label class="admin-field"><span>Min (mm)</span><input type="number" name="groups[__GROUP__][min_value]"></label>
                    <label class="admin-field"><span>Max (mm)</span><input type="number" name="groups[__GROUP__][max_value]"></label>
                    <label class="admin-field"><span>Step (mm)</span><input type="number" name="groups[__GROUP__][step_value]"></label>
                    <label class="admin-field"><span>Default width</span><input type="number" name="groups[__GROUP__][default_w]"></label>
                    <label class="admin-field"><span>Default height</span><input type="number" name="groups[__GROUP__][default_h]"></label>
                    <label class="admin-field"><span>Default depth</span><input type="number" name="groups[__GROUP__][default_d]"></label>
                    <label class="admin-field"><span>Unit label</span><input type="text" name="groups[__GROUP__][unit_label]" placeholder="mm"></label>
                </div>
            </div>
            <div class="values-editor" data-values-editor>
                <div class="values-editor__head"><span>Values</span><span>Delta (₹)</span><span>Multiplier (%)</span><span>Default</span><span></span></div>
                <div data-values-body></div>
                <button class="admin-btn admin-btn--sm admin-btn--ghost" type="button" data-add-value><?= icon('plus') ?> Add value</button>
            </div>
        </div>
    </article>
</template>

<template id="valueTemplate">
    <div class="value-row" data-value-row>
        <input type="hidden" name="groups[__GROUP__][values][__VALUE__][id]" value="0">
        <label class="admin-field"><span class="sr-only">Label</span><input type="text" name="groups[__GROUP__][values][__VALUE__][label]" placeholder="4 kg"></label>
        <label class="admin-field"><span class="sr-only">SKU suffix</span><input type="text" name="groups[__GROUP__][values][__VALUE__][sku_suffix]" placeholder="SKU suffix"></label>
        <label class="admin-field"><span class="sr-only">Price delta</span><input type="text" name="groups[__GROUP__][values][__VALUE__][price_delta]" value="0.00" inputmode="decimal"></label>
        <label class="admin-field"><span class="sr-only">Multiplier</span><input type="text" name="groups[__GROUP__][values][__VALUE__][price_multiplier]" value="0" inputmode="decimal"></label>
        <label class="admin-check"><input type="checkbox" name="groups[__GROUP__][values][__VALUE__][is_default]" value="1"><span class="sr-only">Default</span></label>
        <button class="admin-icon-btn admin-icon-btn--danger" type="button" data-remove-value title="Remove value"><?= icon('close') ?></button>
    </div>
</template>

<template id="bundleTemplate">
    <div class="bundle-row" data-bundle-row>
        <label class="admin-field"><span class="sr-only">Component</span>
            <select name="bundle_items[__BUNDLE__][component_product_id]">
                <option value="">Choose a product</option>
                <?php foreach ($allProducts as $option): ?>
                    <option value="<?= (int) $option['id'] ?>"><?= e($option['name']) ?><?= $option['sku'] ? ' · ' . e($option['sku']) : '' ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="admin-field admin-field--qty"><span class="sr-only">Quantity</span><input type="number" name="bundle_items[__BUNDLE__][qty]" value="1" min="1"></label>
        <button class="admin-icon-btn admin-icon-btn--danger" type="button" data-remove-bundle title="Remove"><?= icon('trash') ?></button>
    </div>
</template>

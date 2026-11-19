<?php
/** @var array $product */
/** @var array $groups */
/** @var array $bundleItems */
/** @var array $related */
/** @var array $specs */
/** @var array $gallery */
/** @var array $initialQuote */
$authed = is_logged_in();
$customer = current_customer();
$unitPrice = (int) $initialQuote['unit_price_paise'];
$gstBp = (int) $product['gst_rate_bp'];
$isConfigurable = ($product['product_type'] ?? 'simple') === 'configurable';
$isBundle = ($product['product_type'] ?? 'simple') === 'bundle';
$gallery = array_values(array_unique(array_filter($gallery)));
if (!$gallery && !empty($product['image'])) {
    $gallery = [$product['image']];
}
$summary = $initialQuote['summary'] ?? [];
?>
<section class="product-page">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="/">Home</a><?= icon('chevron-right') ?>
            <a href="/catalog">Catalog</a><?= icon('chevron-right') ?>
            <a href="/catalog/<?= e($product['category_slug']) ?>"><?= e($product['category_name']) ?></a><?= icon('chevron-right') ?>
            <span><?= e($product['name']) ?></span>
        </nav>

        <div class="product-detail">
            <div class="product-gallery" data-gallery>
                <div class="product-gallery__main">
                    <?php if ($gallery): ?>
                        <img src="<?= e($gallery[0]) ?>" alt="<?= e($product['name']) ?>" data-gallery-main>
                    <?php else: ?>
                        <span class="product-gallery__placeholder"><?= icon('package') ?></span>
                    <?php endif; ?>
                    <span class="product-gallery__flag"><?= $isBundle ? 'Complete kit' : ($isConfigurable ? 'Made to order' : 'From stock') ?></span>
                </div>
                <?php if (count($gallery) > 1): ?>
                    <div class="product-gallery__thumbs">
                        <?php foreach ($gallery as $index => $image): ?>
                            <button type="button" class="product-gallery__thumb <?= $index === 0 ? 'is-active' : '' ?>" data-gallery-thumb data-src="<?= e($image) ?>" aria-label="View image <?= $index + 1 ?>">
                                <img src="<?= e($image) ?>" alt="" loading="lazy">
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <div class="product-gallery__notes">
                    <span><?= icon('shield') ?> Tested &amp; batch traceable</span>
                    <span><?= icon('package') ?> Factory packed</span>
                </div>
            </div>

            <div class="product-info">
                <div class="product-info__head">
                    <p class="product-info__category"><?= e($product['category_name']) ?> <span>·</span> <?= e($product['sku'] ?: 'RF-' . $product['id']) ?></p>
                    <h1><?= e($product['name']) ?></h1>
                    <p class="product-info__short"><?= e($product['short_description']) ?></p>
                    <?php $badges = App\Catalog::badges($product); ?>
                    <?php if ($badges): ?>
                        <ul class="badge-list badge-list--inline">
                            <?php foreach ($badges as $badge): ?><li><?= e($badge) ?></li><?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <form class="configurator" id="configurator" action="/cart/add" method="post"
                      data-configurator
                      data-product-id="<?= (int) $product['id'] ?>"
                      data-product-slug="<?= e($product['slug']) ?>"
                      data-base-price="<?= (int) $product['base_price_paise'] ?>"
                      data-dim-rate="<?= (int) $product['dim_rate_paise_sqm'] ?>"
                      data-discount-bp="<?= (int) ($customer['discount_bp'] ?? 0) ?>"
                      data-gst-bp="<?= $gstBp ?>"
                      data-authed="<?= $authed ? '1' : '0' ?>"
                      data-csrf="<?= e(csrf_token()) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">

                    <?php if ($groups): ?>
                        <div class="configurator__groups">
                            <?php foreach ($groups as $group): ?>
                                <?php
                                $groupSlug = $group['slug'];
                                $inputType = $group['input_type'];
                                $defaultValueId = 0;
                                foreach ($group['values'] as $value) {
                                    if ((int) $value['is_default'] === 1) {
                                        $defaultValueId = (int) $value['id'];
                                        break;
                                    }
                                }
                                if ($defaultValueId === 0 && $group['values'] && $inputType !== 'checkbox') {
                                    $defaultValueId = (int) $group['values'][0]['id'];
                                }
                                ?>
                                <fieldset class="option-group" data-option-group data-slug="<?= e($groupSlug) ?>" data-name="<?= e($group['name']) ?>">
                                    <legend class="option-group__legend">
                                        <span><?= e($group['name']) ?><?= (int) $group['is_required'] === 1 ? '<em>*</em>' : '' ?></span>
                                        <?php if ($group['help_text']): ?><small><?= e($group['help_text']) ?></small><?php endif; ?>
                                    </legend>

                                    <?php if ($inputType === 'dimension'): ?>
                                        <div class="dimension-grid">
                                            <?php
                                            $dimFields = [
                                                'w' => ['label' => 'Width', 'default' => (int) ($group['default_w'] ?: $group['min_value'] ?: 500)],
                                                'h' => ['label' => 'Height', 'default' => (int) ($group['default_h'] ?: $group['min_value'] ?: 500)],
                                                'd' => ['label' => 'Depth', 'default' => (int) ($group['default_d'] ?: $group['min_value'] ?: 200)],
                                            ];
                                            ?>
                                            <?php foreach ($dimFields as $field => $meta): ?>
                                                <label class="field">
                                                    <span><?= e($meta['label']) ?> <small>mm</small></span>
                                                    <input type="number"
                                                           name="selection[dimensions][<?= e($groupSlug) ?>][<?= $field ?>]"
                                                           value="<?= $meta['default'] ?>"
                                                           min="<?= (int) ($group['min_value'] ?: 100) ?>"
                                                           max="<?= (int) ($group['max_value'] ?: 5000) ?>"
                                                           step="<?= (int) ($group['step_value'] ?: 10) ?>"
                                                           data-dim="<?= $field ?>"
                                                           inputmode="numeric">
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                        <p class="dimension-note">
                                            <?= icon('spark') ?>
                                            <span>Made to measure. Panel area is calculated at <strong><?= money((int) $product['dim_rate_paise_sqm']) ?> / m²</strong> and added to the price.</span>
                                        </p>
                                        <?php if ($authed): ?>
                                            <p class="dimension-area" data-dim-area>Surface area: <?= number_format((float) ($initialQuote['options']['area_sqm'] ?? 0), 2) ?> m²</p>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="option-cards <?= $inputType === 'checkbox' ? 'option-cards--check' : '' ?>">
                                            <?php foreach ($group['values'] as $value): ?>
                                                <?php
                                                $valueId = (int) $value['id'];
                                                $checked = $inputType === 'checkbox'
                                                    ? (int) $value['is_default'] === 1
                                                    : $valueId === $defaultValueId;
                                                ?>
                                                <label class="option-card">
                                                    <input type="<?= $inputType === 'checkbox' ? 'checkbox' : 'radio' ?>"
                                                           name="selection[groups][<?= e($groupSlug) ?>]<?= $inputType === 'checkbox' ? '[]' : '' ?>"
                                                           value="<?= $valueId ?>"
                                                           data-delta="<?= (int) $value['price_delta_paise'] ?>"
                                                           data-mult="<?= (int) $value['price_multiplier_bp'] ?>"
                                                           data-label="<?= e($value['label']) ?>"
                                                           <?= $checked ? 'checked' : '' ?>>
                                                    <span class="option-card__tick"><?= icon('check') ?></span>
                                                    <span class="option-card__body">
                                                        <strong><?= e($value['label']) ?></strong>
                                                        <?php if ($authed): ?>
                                                            <?php if ((int) $value['price_multiplier_bp'] !== 0): ?>
                                                                <small><?= (int) $value['price_multiplier_bp'] > 0 ? '+' : '' ?><?= rtrim(rtrim(number_format($value['price_multiplier_bp'] / 100, 2, '.', ''), '0'), '.') ?>%</small>
                                                            <?php elseif ((int) $value['price_delta_paise'] !== 0): ?>
                                                                <small><?= (int) $value['price_delta_paise'] > 0 ? '+' : '' ?><?= money((int) $value['price_delta_paise']) ?></small>
                                                            <?php else: ?>
                                                                <small>Included</small>
                                                            <?php endif; ?>
                                                        <?php elseif ($value['sku_suffix']): ?>
                                                            <small>Ref. <?= e($value['sku_suffix']) ?></small>
                                                        <?php endif; ?>
                                                    </span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </fieldset>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($isBundle && $bundleItems): ?>
                        <div class="bundle-contents">
                            <h2><?= icon('package') ?> What’s in the kit</h2>
                            <ul>
                                <?php foreach ($bundleItems as $item): ?>
                                    <li>
                                        <span><?= (int) $item['qty'] ?> × <a href="/product/<?= e($item['slug']) ?>"><?= e($item['name']) ?></a></span>
                                        <small><?= e($item['sku']) ?></small>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <p>Kit price is fixed for the complete assembly. Components are not sold separately at this price.</p>
                        </div>
                    <?php endif; ?>

                    <div class="buy-panel">
                        <div class="buy-panel__actions">
                            <span>Quantity </span>
                        <div class="qty-stepper" data-qty>
                            <button type="button" data-qty-down aria-label="Decrease quantity"><?= icon('minus') ?></button>
                            <label class="sr-only" for="qty">Quantity</label>
                            <input id="qty" name="qty" type="number" value="<?= max(1, (int) $product['min_qty']) ?>" min="<?= max(1, (int) $product['min_qty']) ?>" max="999" inputmode="numeric">
                            <button type="button" data-qty-up aria-label="Increase quantity"><?= icon('plus') ?></button>
                        </div>
                        <button class="btn btn--primary btn--lg btn--block" type="submit" data-add-to-cart>
                            <?= icon('cart') ?>
                            <span>Add to order</span>
                        </button>
                    </div>

                        <ul class="buy-panel__meta">
                            <?php
                            $stockQty = isset($product['stock_qty']) && $product['stock_qty'] !== null ? (int) $product['stock_qty'] : null;
                            $stockLabel = 'Made to order';
                            if ($stockQty !== null) {
                                if ($stockQty > 10) {
                                    $stockLabel = 'In stock';
                                } elseif ($stockQty > 0) {
                                    $stockLabel = 'Low stock (' . $stockQty . ' left)';
                                } else {
                                    $stockLabel = 'Out of stock';
                                }
                            }
                            ?>
                            <li><?= icon('package') ?><span><small>Availability</small><strong><?= e($stockLabel) ?></strong></span></li>
                            <li><?= icon('clock') ?><span><small>Lead time</small><strong><?= e($product['lead_time'] ?: 'On confirmation') ?></strong></span></li>
                            <li><?= icon('shield') ?><span><small>Warranty</small><strong><?= e($product['warranty'] ?: 'As per standard terms') ?></strong></span></li>
                            <li><?= icon('truck') ?><span><small>Dispatch</small><strong>Palghar factory</strong></span></li>
                        </ul>

                        <?php if (!empty($product['datasheet'])): ?>
                            <a class="datasheet-link" href="<?= e($product['datasheet']) ?>" target="_blank" rel="noopener">
                                <?= icon('download') ?>
                                <span><strong>Download datasheet</strong><small>PDF · engineering specification</small></span>
                            </a>
                        <?php endif; ?>
                    </div>

                    <div class="config-summary" data-config-summary>
                        <?php if ($summary): ?>
                            <span class="config-summary__label">Your configuration</span>
                            <ul>
                                <?php foreach ($summary as $line): ?><li><?= e($line) ?></li><?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="product-assurance">
                    <span><?= icon('file') ?> GST invoice</span>
                    <span><?= icon('shield') ?> Batch tested</span>
                    <span><?= icon('phone') ?> Sales engineer support</span>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section section--tight product-content">
    <div class="container">
        <div class="tabs" data-tabs>
            <div class="tabs__nav" role="tablist" aria-label="Product information">
                <button class="tabs__button is-active" type="button" role="tab" aria-selected="true" data-tab="description">Description</button>
                <button class="tabs__button" type="button" role="tab" aria-selected="false" data-tab="specs">Specifications</button>
                <button class="tabs__button" type="button" role="tab" aria-selected="false" data-tab="delivery">Delivery &amp; documentation</button>
            </div>

            <div class="tabs__panel is-active" data-panel="description" role="tabpanel">
                <div class="prose">
                    <h2>Engineered for real project conditions.</h2>
                    <?php foreach (preg_split('/\r\n|\r|\n/', (string) $product['description']) ?: [] as $paragraph): ?>
                        <?php if (trim($paragraph) !== ''): ?><p><?= e(trim($paragraph)) ?></p><?php endif; ?>
                    <?php endforeach; ?>
                    <?php if (!empty($product['price_note'])): ?>
                        <p class="prose__note"><?= icon('alert') ?> <?= e($product['price_note']) ?></p>
                    <?php endif; ?>
                </div>
                <?php if ($isConfigurable): ?>
                    <div class="prose-aside">
                        <span class="prose-aside__icon"><?= icon('spark') ?></span>
                        <strong>Configured to your schedule</strong>
                        <p>Selections are locked to the order line so the factory and your site team work from the same specification.</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="tabs__panel" data-panel="specs" role="tabpanel">
                <?php if ($specs): ?>
                    <table class="spec-table">
                        <tbody>
                        <?php foreach ($specs as $spec): ?>
                            <tr><th scope="row"><?= e($spec['label'] ?? '') ?></th><td><?= e($spec['value'] ?? '') ?></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="muted">Full specifications are available on the datasheet or from our sales team.</p>
                <?php endif; ?>
            </div>

            <div class="tabs__panel" data-panel="delivery" role="tabpanel">
                <div class="info-grid">
                    <div class="info-card">
                        <h3><?= icon('truck') ?> Dispatch &amp; freight</h3>
                        <p>Goods are packed at our Palghar factory. Freight, unloading and site access are confirmed by our sales team with the order acknowledgement.</p>
                        <p class="muted">Standard lead time: <?= e($product['lead_time'] ?: 'confirmed on order') ?>.</p>
                    </div>
                    <div class="info-card">
                        <h3><?= icon('file') ?> Tax &amp; documentation</h3>
                        <p>GST at <?= gst_label($gstBp) ?> is charged extra<?= $product['hsn_code'] ? ' · HSN ' . e($product['hsn_code']) : '' ?>. A tax invoice, packing list and test certificate (where applicable) accompany the dispatch.</p>
                    </div>
                    <div class="info-card">
                        <h3><?= icon('shield') ?> Warranty &amp; support</h3>
                        <p><?= e($product['warranty'] ?: 'Standard warranty applies.') ?> Service and spares are supported from our Mira Road head office.</p>
                    </div>
                    <div class="info-card">
                        <h3><?= icon('phone') ?> Need help specifying?</h3>
                        <p>Talk to a sales engineer on <a href="tel:<?= e(setting('support_phone_href', config('store.support_phone_href'))) ?>"><?= e(setting('support_phone', config('store.support_phone'))) ?></a> or <a href="/quote?product=<?= e($product['slug']) ?>">send the requirement online</a>.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if ($related): ?>
    <section class="section section--tint">
        <div class="container">
            <div class="section-head">
                <div>
                    <p class="eyebrow"><span></span> Complete the system</p>
                    <h2>Related equipment</h2>
                </div>
                <a class="text-link" href="/catalog/<?= e($product['category_slug']) ?>">More in <?= e($product['category_name']) ?> <?= icon('arrow-right') ?></a>
            </div>
            <div class="product-grid">
                <?php foreach ($related as $relatedProduct): ?>
                    <?php partial('product-card', ['product' => $relatedProduct]); ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

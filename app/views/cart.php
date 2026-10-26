<?php
/** @var array $items */
/** @var array $totals */
/** @var int $minOrder */
$meetsMinimum = $totals['subtotal_paise'] >= $minOrder;
$remaining = max(0, $minOrder - $totals['subtotal_paise']);
$progress = $minOrder > 0 ? min(100, (int) round($totals['subtotal_paise'] / $minOrder * 100)) : 100;
?>
<section class="page-hero page-hero--compact">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="/">Home</a><?= icon('chevron-right') ?><span>Your order</span>
        </nav>
        <div class="page-hero__grid">
            <div>
                <p class="eyebrow"><span></span> Order builder</p>
                <h1>Your order</h1>
                <p class="page-hero__lede">Review the configured lines, quantities and tax before continuing to the delivery details.</p>
            </div>
            <div class="page-hero__aside page-hero__aside--steps">
                <span class="is-done">01 Cart</span>
                <span>02 Delivery</span>
                <span>03 Review</span>
            </div>
        </div>
    </div>
</section>

<section class="section section--tight">
    <div class="container">
        <?php if (!$items): ?>
            <div class="empty-state empty-state--large">
                <span class="empty-state__icon"><?= icon('cart') ?></span>
                <h2>Your order is empty</h2>
                <p>Browse the catalog and configure the equipment you need. Trade pricing appears once you are signed in.</p>
                <div class="empty-state__actions">
                    <a class="btn btn--primary btn--lg" href="/catalog">Browse the catalog <?= icon('arrow-right') ?></a>
                    <a class="btn btn--outline btn--lg" href="/quote">Request a quote</a>
                </div>
            </div>
        <?php else: ?>
            <div class="cart-layout">
                <div class="cart-main">
                    <div class="cart-list">
                        <div class="cart-list__head">
                            <span><?= $totals['item_count'] ?> <?= $totals['item_count'] === 1 ? 'unit' : 'units' ?> · <?= count($items) ?> configured <?= count($items) === 1 ? 'line' : 'lines' ?></span>
                            <a href="/catalog">Add more products <?= icon('plus') ?></a>
                        </div>
                        <?php foreach ($items as $item): ?>
                            <?php $options = $item['options'] ?? []; ?>
                            <article class="cart-item">
                                <a class="cart-item__media" href="/product/<?= e($item['slug']) ?>">
                                    <?php if (!empty($item['image'])): ?>
                                        <img src="<?= e($item['image']) ?>" alt="<?= e($item['name']) ?>" loading="lazy">
                                    <?php else: ?>
                                        <span class="product-card__placeholder"><?= icon('package') ?></span>
                                    <?php endif; ?>
                                </a>
                                <div class="cart-item__body">
                                    <p class="cart-item__meta"><?= e($item['category_name']) ?> · <?= e($item['sku']) ?></p>
                                    <h2><a href="/product/<?= e($item['slug']) ?>"><?= e($item['name']) ?></a></h2>
                                    <?php if (!empty($options['summary'])): ?>
                                        <ul class="cart-item__options">
                                            <?php foreach ($options['summary'] as $summaryLine): ?>
                                                <li><?= e($summaryLine) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                    <div class="cart-item__controls">
                                        <form class="cart-item__qty" action="/cart/update" method="post" data-cart-qty-form>
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                                            <div class="qty-stepper qty-stepper--sm">
                                                <button type="button" data-cart-qty-down aria-label="Decrease quantity"><?= icon('minus') ?></button>
                                                <label class="sr-only" for="qty-<?= (int) $item['id'] ?>">Quantity</label>
                                                <input id="qty-<?= (int) $item['id'] ?>" name="qty" type="number" value="<?= (int) $item['qty'] ?>" min="<?= max(1, (int) $item['min_qty']) ?>" max="999">
                                                <button type="button" data-cart-qty-up aria-label="Increase quantity"><?= icon('plus') ?></button>
                                            </div>
                                            <button class="cart-item__update" type="submit">Update</button>
                                        </form>
                                        <form action="/cart/remove" method="post" data-confirm="Remove this configured line from your order?">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                                            <button class="cart-item__remove" type="submit"><?= icon('trash') ?> Remove</button>
                                        </form>
                                    </div>
                                </div>
                                <div class="cart-item__price">
                                    <strong><?= money((int) $item['line_subtotal_paise']) ?></strong>
                                    <span><?= money((int) $item['unit_price_paise']) ?> × <?= (int) $item['qty'] ?></span>
                                    <small>+ <?= gst_label((int) $item['gst_rate_bp']) ?> GST <?= money((int) $item['line_gst_paise']) ?></small>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <a class="back-link" href="/catalog"><?= icon('arrow-right', 'flip') ?> Continue shopping</a>
                </div>

                <aside class="cart-summary">
                    <div class="summary-card">
                        <h2>Order summary</h2>
                        <dl class="summary-lines">
                            <div><dt>Subtotal (excl. GST)</dt><dd><?= money((int) $totals['subtotal_paise']) ?></dd></div>
                            <div><dt>GST</dt><dd><?= money((int) $totals['gst_paise']) ?></dd></div>
                            <div class="summary-lines__total"><dt>Total</dt><dd><?= money((int) $totals['total_paise']) ?></dd></div>
                        </dl>
                        <p class="summary-note"><?= icon('truck') ?> Freight is confirmed by our sales team after the order is reviewed.</p>

                        <?php if ($minOrder > 0): ?>
                            <div class="minimum-order <?= $meetsMinimum ? 'is-met' : '' ?>">
                                <div class="minimum-order__bar"><span style="width: <?= $progress ?>%"></span></div>
                                <?php if ($meetsMinimum): ?>
                                    <p><?= icon('check') ?> Minimum order value met.</p>
                                <?php else: ?>
                                    <p>Add <?= money($remaining) ?> to reach the <?= money($minOrder) ?> minimum order value.</p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <a class="btn btn--primary btn--lg btn--block <?= $meetsMinimum ? '' : 'is-disabled' ?>" href="<?= $meetsMinimum ? '/checkout' : '/catalog' ?>" <?= $meetsMinimum ? '' : 'aria-disabled="true"' ?>>
                            <?= $meetsMinimum ? 'Continue to delivery' : 'Add more to continue' ?> <?= icon('arrow-right') ?>
                        </a>
                        <p class="summary-secure"><?= icon('lock') ?> No online payment. Order now and pay on invoice.</p>
                    </div>

                    <div class="summary-help">
                        <span><?= icon('phone') ?></span>
                        <div>
                            <strong>Need help with a line?</strong>
                            <p>Call <a href="tel:<?= e(setting('support_phone_href', config('store.support_phone_href'))) ?>"><?= e(setting('support_phone', config('store.support_phone'))) ?></a> or <a href="/quote">request a quote</a>.</p>
                        </div>
                    </div>
                </aside>
            </div>
        <?php endif; ?>
    </div>
</section>

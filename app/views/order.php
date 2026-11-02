<?php
/** @var array $order */
/** @var array $items */
/** @var array $billing */
/** @var array $shipping */
/** @var bool $justPlaced */
$timeline = ['placed' => 'Order placed', 'confirmed' => 'Confirmed', 'invoiced' => 'Invoiced', 'dispatched' => 'Dispatched', 'delivered' => 'Delivered'];
$status = (string) $order['status'];
$cancelled = $status === 'cancelled';
$currentIndex = array_search($status, array_keys($timeline), true);
if ($currentIndex === false) {
    $currentIndex = 0;
}
?>
<section class="page-hero page-hero--compact page-hero--order">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="/">Home</a><?= icon('chevron-right') ?><a href="/account">Account</a><?= icon('chevron-right') ?><span><?= e($order['order_no']) ?></span>
        </nav>
        <div class="order-head">
            <div>
                <p class="eyebrow"><span></span> <?= $justPlaced ? 'Order received' : 'Order detail' ?></p>
                <h1><?= e($order['order_no']) ?></h1>
                <p class="page-hero__lede">Placed <?= e(format_datetime($order['placed_at'])) ?> · <?= count($items) ?> configured <?= count($items) === 1 ? 'line' : 'lines' ?></p>
            </div>
            <div class="order-head__actions">
                <span class="status-pill <?= e(order_status_class($status)) ?>"><?= e(order_status_label($status)) ?></span>
                <a class="btn btn--outline btn--sm" href="/order/<?= e($order['order_no']) ?>/print" target="_blank" rel="noopener"><?= icon('download') ?> Print proforma</a>
                <a class="btn btn--outline btn--sm" href="/order/<?= e($order['order_no']) ?>/print?pdf=1" target="_blank" rel="noopener"><?= icon('download') ?> Download PDF</a>
                <?php if (!$cancelled): ?>
                <form action="/order/<?= e($order['order_no']) ?>/reorder" method="post" style="display:inline">
                    <?= csrf_field() ?>
                    <button class="btn btn--outline btn--sm" type="submit"><?= icon('refresh') ?> Reorder</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<section class="section section--tight">
    <div class="container">
        <?php if ($justPlaced): ?>
            <div class="order-success">
                <span class="order-success__icon"><?= icon('check') ?></span>
                <div>
                    <h2>Thank you — your order is with our sales team.</h2>
                    <p>We have emailed the details to <strong><?= e($billing['email'] ?? $order['customer_email']) ?></strong>. A confirmation with stock, freight and dispatch dates will follow. No payment is due online.</p>
                    <div class="order-success__meta">
                        <span>Order <?= e($order['order_no']) ?></span>
                        <span>Total <?= money((int) $order['grand_total_paise']) ?> incl. GST</span>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!$cancelled && !empty($order['tracking_no'])): ?>
            <div class="order-tracking" style="margin:18px 0;padding:14px 18px;border-radius:12px;background:#f8fafc;border:1px solid #e2e8f0;display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                <?= icon('truck') ?>
                <div style="flex:1;min-width:240px;">
                    <strong>Dispatched</strong>
                    <?php if (!empty($order['courier_name'])): ?> via <?= e($order['courier_name']) ?><?php endif; ?>
                    <?php if (!empty($order['tracking_no'])): ?> · Tracking: <strong><?= e($order['tracking_no']) ?></strong><?php endif; ?>
                </div>
                <?php if (!empty($order['tracking_url'])): ?>
                    <a class="btn btn--outline btn--sm" href="<?= e($order['tracking_url']) ?>" target="_blank" rel="noopener">Track shipment</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($cancelled): ?>
            <div class="flash flash--error"><?= icon('alert') ?><p>This order was cancelled. Call sales if you need it reinstated or re-quoted.</p></div>
        <?php else: ?>
            <ol class="order-timeline">
                <?php $i = 0; foreach ($timeline as $key => $label): ?>
                    <li class="<?= $i <= $currentIndex ? 'is-done' : '' ?> <?= $i === $currentIndex ? 'is-current' : '' ?>">
                        <span class="order-timeline__dot"><?= $i <= $currentIndex ? icon('check') : '' ?></span>
                        <strong><?= e($label) ?></strong>
                        <small>
                            <?php
                            $timeKey = $key . '_at';
                            echo e($order[$timeKey] ? format_datetime($order[$timeKey]) : '');
                            ?>
                        </small>
                    </li>
                <?php $i++; endforeach; ?>
            </ol>
            <?php if (!empty($order['tracking_no'])): ?>
                <div class="tracking-banner">
                    <?= icon('truck') ?>
                    <div>
                        <strong>Your order is on its way.</strong>
                        <?php if (!empty($order['courier_name'])): ?><span><?= e($order['courier_name']) ?></span><?php endif; ?>
                        <span>Docket: <?= e($order['tracking_no']) ?></span>
                        <?php if (!empty($order['tracking_url'])): ?>
                            <a href="<?= e($order['tracking_url']) ?>" target="_blank" rel="noopener">Track shipment <?= icon('arrow-right') ?></a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="order-layout">
            <div class="order-main">
                <div class="table-card">
                    <div class="table-card__head">
                        <h2>Order lines</h2>
                        <span><?= count($items) ?> <?= count($items) === 1 ? 'line' : 'lines' ?></span>
                    </div>
                    <div class="order-lines">
                        <?php foreach ($items as $item): ?>
                            <article class="order-line">
                                <div class="order-line__body">
                                    <p class="cart-item__meta"><?= e($item['sku']) ?></p>
                                    <h3><?= e($item['product_name']) ?></h3>
                                    <?php $itemOptions = decode_json($item['options_json']); ?>
                                    <?php if (!empty($itemOptions['summary'])): ?>
                                        <ul class="cart-item__options">
                                            <?php foreach ($itemOptions['summary'] as $summaryLine): ?><li><?= e($summaryLine) ?></li><?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                                <div class="order-line__qty"><?= (int) $item['qty'] ?> × <?= money((int) $item['unit_price_paise']) ?></div>
                                <div class="order-line__total">
                                    <?= money((int) $item['line_total_paise']) ?>
                                    <small>incl. <?= gst_label((int) $item['gst_rate_bp']) ?> GST</small>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="order-addresses">
                    <div class="address-card">
                        <h2><?= icon('file') ?> Billing</h2>
                        <strong><?= e($billing['company_name'] ?? $order['company_name']) ?></strong>
                        <p><?= e($billing['contact_name'] ?? '') ?><br>
                            <?= e($billing['address_line1'] ?? '') ?><br>
                            <?php if (!empty($billing['address_line2'])): ?><?= e($billing['address_line2']) ?><br><?php endif; ?>
                            <?= e($billing['city'] ?? '') ?>, <?= e($billing['state'] ?? '') ?> <?= e($billing['pincode'] ?? '') ?></p>
                        <small><?= e($billing['phone'] ?? '') ?> · <?= e($billing['email'] ?? '') ?></small>
                        <?php if (!empty($billing['gstin'])): ?><small>GSTIN <?= e($billing['gstin']) ?></small><?php endif; ?>
                    </div>
                    <div class="address-card">
                        <h2><?= icon('truck') ?> Delivery</h2>
                        <strong><?= e($shipping['company_name'] ?? $order['company_name']) ?></strong>
                        <p><?= e($shipping['contact_name'] ?? '') ?><br>
                            <?= e($shipping['address_line1'] ?? '') ?><br>
                            <?php if (!empty($shipping['address_line2'])): ?><?= e($shipping['address_line2']) ?><br><?php endif; ?>
                            <?= e($shipping['city'] ?? '') ?>, <?= e($shipping['state'] ?? '') ?> <?= e($shipping['pincode'] ?? '') ?></p>
                        <small><?= e($shipping['phone'] ?? '') ?></small>
                    </div>
                </div>

                <?php if (!empty($order['notes']) || !empty($order['po_number'])): ?>
                    <div class="order-note">
                        <h2>Order references</h2>
                        <?php if (!empty($order['po_number'])): ?><p><strong>PO number:</strong> <?= e($order['po_number']) ?></p><?php endif; ?>
                        <?php if (!empty($order['notes'])): ?><p><strong>Notes:</strong> <?= nl2br(e($order['notes'])) ?></p><?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <aside class="order-summary">
                <div class="summary-card">
                    <h2>Order total</h2>
                    <dl class="summary-lines">
                        <div><dt>Subtotal</dt><dd><?= money((int) $order['subtotal_paise']) ?></dd></div>
                        <div><dt>GST</dt><dd><?= money((int) $order['gst_paise']) ?></dd></div>
                        <div><dt>Freight</dt><dd><?= (int) $order['freight_paise'] > 0 ? money((int) $order['freight_paise']) : 'To be confirmed' ?></dd></div>
                        <div class="summary-lines__total"><dt>Grand total</dt><dd><?= money((int) $order['grand_total_paise']) ?></dd></div>
                    </dl>
                    <div class="summary-detail">
                        <span>Payment terms</span><strong>On invoice</strong>
                        <span>Status</span><strong><?= e(order_status_label($status)) ?></strong>
                        <?php if (!empty($order['po_number'])): ?><span>Your PO</span><strong><?= e($order['po_number']) ?></strong><?php endif; ?>
                    </div>
                    <a class="btn btn--outline btn--block" href="/order/<?= e($order['order_no']) ?>/print" target="_blank" rel="noopener"><?= icon('download') ?> Download proforma</a>
                </div>
                <div class="summary-help">
                    <span><?= icon('phone') ?></span>
                    <div><strong>Questions about this order?</strong><p>Quote <?= e($order['order_no']) ?> when you call <a href="tel:<?= e(setting('support_phone_href', config('store.support_phone_href'))) ?>"><?= e(setting('support_phone', config('store.support_phone'))) ?></a>.</p></div>
                </div>
            </aside>
        </div>
    </div>
</section>

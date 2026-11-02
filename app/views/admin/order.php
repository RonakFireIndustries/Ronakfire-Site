<?php
/** @var array $order */
/** @var array $items */
/** @var array $billing */
/** @var array $shipping */
$timelineKeys = ['placed', 'confirmed', 'invoiced', 'dispatched', 'delivered', 'cancelled'];
$currentIndex = array_search($order['status'], $timelineKeys, true);
$customerName = $order['company_name'] ?: ($billing['company_name'] ?? 'Customer');
?>
<div class="admin-page-head">
    <div>
        <p class="admin-eyebrow"><a href="/admin/orders">Orders</a> / <?= e($order['order_no']) ?></p>
        <h2><?= e($order['order_no']) ?></h2>
        <p>Placed <?= e(format_datetime($order['placed_at'])) ?> by <a href="/admin/customers/<?= (int) $order['customer_id'] ?>"><?= e($customerName) ?></a>.</p>
    </div>
    <div class="admin-page-head__actions">
        <a class="admin-btn admin-btn--ghost" href="/admin/orders">Back</a>
        <a class="admin-btn" href="/admin/orders/<?= (int) $order['id'] ?>/invoice" target="_blank" rel="noopener"><?= icon('download') ?> Proforma</a>
    </div>
</div>

<div class="admin-stats admin-stats--compact">
    <div class="admin-stat">
        <span class="admin-stat__label">Order value</span>
        <strong><?= money((int) $order['grand_total_paise']) ?></strong>
        <small><?= (int) $order['freight_paise'] > 0 ? 'Including freight' : 'Freight to be confirmed' ?></small>
    </div>
    <div class="admin-stat">
        <span class="admin-stat__label">Lines</span>
        <strong><?= count($items) ?></strong>
        <small><?= array_sum(array_map(static fn (array $i): int => (int) $i['qty'], $items)) ?> units total</small>
    </div>
    <div class="admin-stat">
        <span class="admin-stat__label">Status</span>
        <strong><span class="admin-status admin-status--<?= e($order['status']) ?>"><?= e(order_status_label($order['status'])) ?></span></strong>
        <small><?= e($order['po_number'] ? 'PO ' . $order['po_number'] : 'No PO supplied') ?></small>
    </div>
    <div class="admin-stat">
        <span class="admin-stat__label">Customer code</span>
        <strong><?= e($order['customer_code'] ?: '—') ?></strong>
        <small><?= e($order['price_tier'] ? ucfirst($order['price_tier']) . ' tier' : 'Standard tier') ?></small>
    </div>
</div>

<div class="admin-grid admin-grid--main">
    <div class="admin-stack">
        <section class="admin-panel">
            <div class="admin-panel__head">
                <div><h2>Order lines</h2><p>Configured specifications are locked to this order.</p></div>
            </div>
            <div class="admin-table-wrap">
                <table class="admin-table admin-table--order-lines">
                    <thead><tr><th>Item</th><th class="is-right">Qty</th><th class="is-right">Unit</th><th class="is-right">GST</th><th class="is-right">Line total</th></tr></thead>
                    <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <strong><?= e($item['product_name']) ?></strong>
                                <small><?= e($item['sku'] ?: '—') ?></small>
                                <?php if (!empty($item['options']['summary'])): ?>
                                    <ul class="admin-option-list">
                                        <?php foreach ($item['options']['summary'] as $line): ?><li><?= e($line) ?></li><?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </td>
                            <td class="is-right"><?= (int) $item['qty'] ?></td>
                            <td class="is-right"><?= money((int) $item['unit_price_paise']) ?></td>
                            <td class="is-right"><?= gst_label((int) $item['gst_rate_bp']) ?><small><?= money((int) $item['line_gst_paise']) ?></small></td>
                            <td class="is-right"><strong><?= money((int) $item['line_total_paise']) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="admin-total-list">
                <div><span>Subtotal</span><strong><?= money((int) $order['subtotal_paise']) ?></strong></div>
                <div><span>GST</span><strong><?= money((int) $order['gst_paise']) ?></strong></div>
                <div><span>Freight</span><strong><?= (int) $order['freight_paise'] > 0 ? money((int) $order['freight_paise']) : 'To be confirmed' ?></strong></div>
                <div class="is-total"><span>Grand total</span><strong><?= money((int) $order['grand_total_paise']) ?></strong></div>
            </div>
        </section>

        <section class="admin-panel">
            <div class="admin-panel__head">
                <div><h2>Fulfilment</h2><p>Move the order through confirmation, invoice and dispatch.</p></div>
            </div>
            <div class="admin-panel__body">
                <ol class="admin-timeline">
                    <?php foreach ($timelineKeys as $index => $key): ?>
                        <?php if ($key === 'cancelled') { continue; } ?>
                        <?php $time = $order[$key . '_at'] ?? null; ?>
                        <li class="<?= $currentIndex !== false && $index <= $currentIndex ? 'is-done' : '' ?> <?= $order['status'] === $key ? 'is-current' : '' ?>">
                            <span class="admin-timeline__dot"><?= $currentIndex !== false && $index <= $currentIndex ? icon('check') : '' ?></span>
                            <div><strong><?= e(order_status_label($key)) ?></strong><small><?= e($time ? format_datetime($time) : 'Pending') ?></small></div>
                        </li>
                    <?php endforeach; ?>
                    <?php if ($order['status'] === 'cancelled'): ?>
                        <li class="is-cancelled is-current">
                            <span class="admin-timeline__dot"><?= icon('close') ?></span>
                            <div><strong>Cancelled</strong><small><?= e(format_datetime($order['cancelled_at'])) ?></small></div>
                        </li>
                    <?php endif; ?>
                </ol>

                <div class="admin-form-grid admin-form-grid--split">
                    <form action="/admin/orders/<?= (int) $order['id'] ?>/status" method="post" class="admin-inline-form">
                        <?= csrf_field() ?>
                        <label class="admin-field">
                            <span>Update status</span>
                            <select name="status">
                                <?php foreach (order_statuses() as $status): ?>
                                    <option value="<?= e($status) ?>" <?= $order['status'] === $status ? 'selected' : '' ?>><?= e(order_status_label($status)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="admin-field">
                            <span>Courier</span>
                            <input type="text" name="courier_name" value="<?= e($order['courier_name'] ?? '') ?>">
                        </label>
                        <label class="admin-field">
                            <span>Tracking No.</span>
                            <input type="text" name="tracking_no" value="<?= e($order['tracking_no'] ?? '') ?>">
                        </label>
                        <label class="admin-field">
                            <span>Tracking URL</span>
                            <input type="url" name="tracking_url" value="<?= e($order['tracking_url'] ?? '') ?>">
                        </label>
                        <button class="admin-btn admin-btn--primary" type="submit">Save status</button>
                    </form>

                    <form action="/admin/orders/<?= (int) $order['id'] ?>/freight" method="post" class="admin-inline-form">
                        <?= csrf_field() ?>
                        <label class="admin-field">
                            <span>Freight (₹)</span>
                            <input type="text" name="freight" value="<?= e(admin_money_input((int) $order['freight_paise'])) ?>" inputmode="decimal">
                            <small>Added to the grand total.</small>
                        </label>
                        <button class="admin-btn" type="submit">Update total</button>
                    </form>
                </div>
            </div>
        </section>

        <section class="admin-panel">
            <div class="admin-panel__head"><div><h2>Notes &amp; references</h2><p>What the customer submitted with the order.</p></div></div>
            <div class="admin-panel__body admin-detail-grid">
                <div><span>Purchase order</span><strong><?= e($order['po_number'] ?: 'Not supplied') ?></strong></div>
                <div><span>Payment terms</span><strong>On invoice</strong></div>
                <div class="admin-detail-grid__full"><span>Customer notes</span><p><?= $order['notes'] ? nl2br(e($order['notes'])) : 'No notes supplied.' ?></p></div>
            </div>
        </section>
    </div>

    <aside class="admin-stack">
        <section class="admin-panel">
            <div class="admin-panel__head">
                <div><h2>Customer</h2></div>
                <a class="admin-link" href="/admin/customers/<?= (int) $order['customer_id'] ?>">Open</a>
            </div>
            <div class="admin-panel__body">
                <div class="admin-address">
                    <strong><?= e($customerName) ?></strong>
                    <p><?= e($order['customer_contact'] ?: '') ?></p>
                    <p><?= e($order['customer_phone'] ?: '') ?><br><?= e($order['customer_email'] ?: '') ?></p>
                    <?php if (!empty($order['customer_gstin'])): ?><p><span class="admin-tag">GSTIN <?= e($order['customer_gstin']) ?></span></p><?php endif; ?>
                </div>
            </div>
        </section>

        <section class="admin-panel">
            <div class="admin-panel__head"><div><h2>Billing</h2></div></div>
            <div class="admin-panel__body">
                <div class="admin-address">
                    <strong><?= e($billing['company_name'] ?? $customerName) ?></strong>
                    <p><?= e($billing['contact_name'] ?? '') ?><br>
                        <?= e($billing['address_line1'] ?? '') ?><br>
                        <?php if (!empty($billing['address_line2'])): ?><?= e($billing['address_line2']) ?><br><?php endif; ?>
                        <?= e($billing['city'] ?? '') ?>, <?= e($billing['state'] ?? '') ?> <?= e($billing['pincode'] ?? '') ?></p>
                    <p><?= e($billing['phone'] ?? '') ?><br><?= e($billing['email'] ?? '') ?></p>
                    <?php if (!empty($billing['gstin'])): ?><p><span class="admin-tag">GSTIN <?= e($billing['gstin']) ?></span></p><?php endif; ?>
                </div>
            </div>
        </section>

        <section class="admin-panel">
            <div class="admin-panel__head"><div><h2>Delivery</h2></div></div>
            <div class="admin-panel__body">
                <div class="admin-address">
                    <strong><?= e($shipping['company_name'] ?? $customerName) ?></strong>
                    <p><?= e($shipping['contact_name'] ?? '') ?><br>
                        <?= e($shipping['address_line1'] ?? '') ?><br>
                        <?php if (!empty($shipping['address_line2'])): ?><?= e($shipping['address_line2']) ?><br><?php endif; ?>
                        <?= e($shipping['city'] ?? '') ?>, <?= e($shipping['state'] ?? '') ?> <?= e($shipping['pincode'] ?? '') ?></p>
                    <p><?= e($shipping['phone'] ?? '') ?></p>
                    <?php if (!empty($order['tracking_no'])): ?>
                        <p style="margin-top:8px;"><strong>Tracking:</strong> <?= e($order['courier_name'] ?: '—') ?> / <?= e($order['tracking_no']) ?><?php if (!empty($order['tracking_url'])): ?> · <a href="<?= e($order['tracking_url']) ?>" target="_blank">Track</a><?php endif; ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="admin-panel">
            <div class="admin-panel__head"><div><h2>Activity</h2></div></div>
            <div class="admin-panel__body">
                <dl class="admin-meta-list">
                    <?php foreach ($timelineKeys as $key): ?>
                        <?php if (!empty($order[$key . '_at'])): ?>
                            <div><dt><?= e(order_status_label($key)) ?></dt><dd><?= e(format_datetime($order[$key . '_at'])) ?></dd></div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <div><dt>Last updated</dt><dd><?= e(format_datetime($order['updated_at'])) ?></dd></div>
                </dl>
            </div>
        </section>
    </aside>
</div>

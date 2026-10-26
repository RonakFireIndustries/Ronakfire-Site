<?php
/** @var array $stats */
/** @var array $recentOrders */
/** @var array $recentEnquiries */
/** @var array $topProducts */
/** @var array $statusCounts */
$statusTotal = array_sum($statusCounts);
?>
<div class="admin-page-head">
    <div>
        <p class="admin-eyebrow">Store overview</p>
        <h2>Today’s operating picture</h2>
        <p>Orders, quotes and catalog health at a glance.</p>
    </div>
    <div class="admin-page-head__actions">
        <a class="admin-btn admin-btn--primary" href="/admin/products/new"><?= icon('plus') ?> New product</a>
        <a class="admin-btn" href="/admin/orders?status=placed"><?= icon('cart') ?> Review new orders</a>
    </div>
</div>

<div class="admin-stats">
    <a class="admin-stat" href="/admin/orders?status=placed">
        <span class="admin-stat__icon admin-stat__icon--orange"><?= icon('cart') ?></span>
        <span class="admin-stat__label">New orders</span>
        <strong><?= (int) $stats['new_orders'] ?></strong>
        <small><?= (int) $stats['open_orders'] ?> open in total</small>
    </a>
    <a class="admin-stat" href="/admin/orders">
        <span class="admin-stat__icon"><?= icon('file') ?></span>
        <span class="admin-stat__label">Order value · 30 days</span>
        <strong><?= money((int) $stats['revenue_30d']) ?></strong>
        <small><?= (int) $stats['orders_30d'] ?> orders placed</small>
    </a>
    <a class="admin-stat" href="/admin/customers">
        <span class="admin-stat__icon"><?= icon('users') ?></span>
        <span class="admin-stat__label">Active customers</span>
        <strong><?= (int) $stats['customers'] ?></strong>
        <small>Trade accounts synced from the portal</small>
    </a>
    <a class="admin-stat" href="/admin/enquiries">
        <span class="admin-stat__icon admin-stat__icon--blue"><?= icon('clipboard') ?></span>
        <span class="admin-stat__label">New quote requests</span>
        <strong><?= (int) $stats['new_enquiries'] ?></strong>
        <small>Awaiting first response</small>
    </a>
</div>

<div class="admin-grid admin-grid--main">
    <section class="admin-panel">
        <div class="admin-panel__head">
            <div><h2>Recent orders</h2><p>Latest activity from the storefront.</p></div>
            <a class="admin-link" href="/admin/orders">All orders <?= icon('arrow-right') ?></a>
        </div>
        <?php if ($recentOrders): ?>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead><tr><th>Order</th><th>Customer</th><th>Status</th><th class="is-right">Total</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($recentOrders as $order): ?>
                        <tr>
                            <td><a class="admin-table__link" href="/admin/orders/<?= (int) $order['id'] ?>"><?= e($order['order_no']) ?></a><small><?= e(format_datetime($order['placed_at'])) ?></small></td>
                            <td><?= e($order['company_name']) ?></td>
                            <td><span class="admin-status admin-status--<?= e($order['status']) ?>"><?= e(order_status_label($order['status'])) ?></span></td>
                            <td class="is-right"><?= money((int) $order['grand_total_paise']) ?></td>
                            <td class="is-right"><a class="admin-table__action" href="/admin/orders/<?= (int) $order['id'] ?>"><?= icon('chevron-right') ?></a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="admin-empty"><?= icon('cart') ?><p>No orders yet. Share the storefront with your trade customers.</p></div>
        <?php endif; ?>
    </section>

    <section class="admin-panel">
        <div class="admin-panel__head">
            <div><h2>Order pipeline</h2><p>Distribution by current status.</p></div>
        </div>
        <div class="pipeline">
            <?php foreach (order_statuses() as $status): ?>
                <?php $count = (int) ($statusCounts[$status] ?? 0); $width = $statusTotal > 0 ? round($count / $statusTotal * 100) : 0; ?>
                <a href="/admin/orders?status=<?= e($status) ?>" class="pipeline__row">
                    <span class="pipeline__label"><span class="admin-status admin-status--<?= e($status) ?>"><?= e(order_status_label($status)) ?></span><strong><?= $count ?></strong></span>
                    <span class="pipeline__bar"><span style="width: <?= $width ?>%"></span></span>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="admin-panel__foot">
            <a class="admin-btn admin-btn--block" href="/admin/orders">Open order desk <?= icon('arrow-right') ?></a>
        </div>
    </section>
</div>

<div class="admin-grid admin-grid--two">
    <section class="admin-panel">
        <div class="admin-panel__head">
            <div><h2>Quote requests</h2><p>Newest specifications from the storefront.</p></div>
            <a class="admin-link" href="/admin/enquiries">View all</a>
        </div>
        <?php if ($recentEnquiries): ?>
            <ul class="admin-activity">
                <?php foreach ($recentEnquiries as $enquiry): ?>
                    <li>
                        <span class="admin-activity__icon"><?= icon('clipboard') ?></span>
                        <div>
                            <a href="/admin/enquiries/<?= (int) $enquiry['id'] ?>"><?= e($enquiry['product_name'] ?: 'General requirement') ?></a>
                            <small><?= e($enquiry['company'] ?: $enquiry['name']) ?> · <?= e(format_date($enquiry['created_at'])) ?> · Qty <?= (int) $enquiry['qty'] ?></small>
                        </div>
                        <span class="admin-status admin-status--<?= e($enquiry['status']) ?>"><?= e(ucfirst($enquiry['status'])) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="admin-empty"><?= icon('clipboard') ?><p>No quote requests yet.</p></div>
        <?php endif; ?>
    </section>

    <section class="admin-panel">
        <div class="admin-panel__head">
            <div><h2>Best-selling lines</h2><p>By quantity across non-cancelled orders.</p></div>
        </div>
        <?php if ($topProducts): ?>
            <ol class="top-products">
                <?php foreach ($topProducts as $index => $product): ?>
                    <li>
                        <span class="top-products__rank"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
                        <div><strong><?= e($product['product_name']) ?></strong><small><?= e($product['sku']) ?></small></div>
                        <span class="top-products__qty"><?= (int) $product['qty'] ?> <small>units</small></span>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php else: ?>
            <div class="admin-empty"><?= icon('package') ?><p>Sales data will appear after the first order.</p></div>
        <?php endif; ?>
    </section>
</div>

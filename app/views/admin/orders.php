<?php
/** @var array $orders */
/** @var string $status */
/** @var string $q */
/** @var array $pager */
/** @var array $statusCounts */
$totalAll = array_sum($statusCounts);
$statusTabs = ['' => 'All'] + array_combine(order_statuses(), array_map('order_status_label', order_statuses()));
$statusUrl = static function (string $value) use ($q): string {
    $query = [];
    if ($value !== '') {
        $query['status'] = $value;
    }
    if ($q !== '') {
        $query['q'] = $q;
    }
    return '/admin/orders' . ($query ? '?' . http_build_query($query) : '');
};
?>
<div class="admin-page-head">
    <div>
        <p class="admin-eyebrow">Sales</p>
        <h2>Order desk</h2>
        <p>Confirm specifications, set freight and issue proforma invoices. No payment is collected online.</p>
    </div>
    <div class="admin-page-head__actions">
        <a class="admin-btn" href="/admin/enquiries"><?= icon('clipboard') ?> Quote requests</a>
    </div>
</div>

<nav class="admin-tabs" aria-label="Order status">
    <?php foreach ($statusTabs as $value => $label): ?>
        <?php $count = $value === '' ? $totalAll : (int) ($statusCounts[$value] ?? 0); ?>
        <a class="admin-tabs__item <?= $status === $value ? 'is-active' : '' ?>" href="<?= e($statusUrl($value)) ?>">
            <?= e($label) ?><span><?= $count ?></span>
        </a>
    <?php endforeach; ?>
</nav>

<form class="admin-filters" action="/admin/orders" method="get">
    <?php if ($status !== ''): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
    <label class="admin-search">
        <span class="sr-only">Search orders</span>
        <?= icon('search') ?>
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Order no., customer, email or PO…">
    </label>
    <button class="admin-btn" type="submit">Search</button>
    <?php if ($q !== '' || $status !== ''): ?><a class="admin-btn admin-btn--ghost" href="/admin/orders">Clear</a><?php endif; ?>
</form>

<section class="admin-panel">
    <?php if ($orders): ?>
        <div class="admin-table-wrap">
            <table class="admin-table admin-table--orders">
                <thead>
                <tr><th>Order</th><th>Customer</th><th>Sales owner</th><th>Placed</th><th>Lines</th><th>Status</th><th class="is-right">Total</th><th></th></tr>
                </thead>
                <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>
                            <a class="admin-table__link" href="/admin/orders/<?= (int) $order['id'] ?>"><?= e($order['order_no']) ?></a>
                            <small><?= e($order['po_number'] ? 'PO ' . $order['po_number'] : 'No PO') ?></small>
                        </td>
                        <td>
                            <a class="admin-table__link admin-table__link--plain" href="/admin/customers/<?= (int) $order['customer_id'] ?>"><?= e($order['company_name']) ?></a>
                            <small><?= e($order['customer_code'] ?: '—') ?></small>
                        </td>
                        <td><?= e($order['sales_owner_name'] ?: '—') ?></td>
                        <td><?= e(format_date($order['placed_at'])) ?><small><?= e(format_date($order['placed_at'], 'H:i')) ?></small></td>
                        <td><?= (int) db()->value('SELECT COUNT(*) FROM order_items WHERE order_id = ?', [$order['id']]) ?></td>
                        <td><span class="admin-status admin-status--<?= e($order['status']) ?>"><?= e(order_status_label($order['status'])) ?></span></td>
                        <td class="is-right"><?= money((int) $order['grand_total_paise']) ?></td>
                        <td class="is-right"><a class="admin-table__action" href="/admin/orders/<?= (int) $order['id'] ?>"><?= icon('chevron-right') ?></a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php partial('admin/partials/pagination', ['pager' => $pager, 'baseUrl' => '/admin/orders']); ?>
    <?php else: ?>
        <div class="admin-empty">
            <?= icon('cart') ?>
            <p><?= $q !== '' || $status !== '' ? 'No orders match this filter.' : 'No orders have been placed yet.' ?></p>
            <?php if ($q !== '' || $status !== ''): ?><a class="admin-btn" href="/admin/orders">Clear filters</a><?php endif; ?>
        </div>
    <?php endif; ?>
</section>

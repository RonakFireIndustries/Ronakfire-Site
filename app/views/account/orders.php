<?php /** @var array $orders */ ?>
<section class="page-hero page-hero--compact">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="/">Home</a><?= icon('chevron-right') ?><a href="/account">Account</a><?= icon('chevron-right') ?><span>Orders</span>
        </nav>
        <div class="page-hero__grid">
            <div>
                <p class="eyebrow"><span></span> Order history</p>
                <h1>Your orders</h1>
                <p class="page-hero__lede">Track production, dispatch and invoice status for every order placed through the store.</p>
            </div>
        </div>
    </div>
</section>

<section class="section section--tight">
    <div class="container account-layout">
        <?php partial('account-nav'); ?>
        <div class="account-main">
            <div class="panel">
                <div class="panel__head">
                    <div><h2>All orders</h2><p><?= count($orders) ?> <?= count($orders) === 1 ? 'order' : 'orders' ?> on this account</p></div>
                    <a class="btn btn--outline btn--sm" href="/catalog">Start a new order</a>
                </div>
                <?php if ($orders): ?>
                    <div class="table-wrap">
                        <table class="data-table data-table--orders">
                            <thead><tr><th>Order</th><th>Placed</th><th>PO number</th><th>Status</th><th class="is-right">Total</th><th></th></tr></thead>
                            <tbody>
                            <?php foreach ($orders as $accountOrder): ?>
                                <tr>
                                    <td data-label="Order"><a class="table-link" href="/order/<?= e($accountOrder['order_no']) ?>"><?= e($accountOrder['order_no']) ?></a></td>
                                    <td data-label="Placed"><?= e(format_date($accountOrder['placed_at'])) ?></td>
                                    <td data-label="PO"><?= e($accountOrder['po_number'] ?: '—') ?></td>
                                    <td data-label="Status">
                                        <span class="status-pill <?= e(order_status_class($accountOrder['status'])) ?>"><?= e(order_status_label($accountOrder['status'])) ?></span>
                                        <?php if (($accountOrder['status'] === 'dispatched') && !empty($accountOrder['tracking_no'])): ?>
                                            <span style="display:block;font-size:11px;color:#475569;margin-top:4px;">Track: <?= e($accountOrder['tracking_no']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Total" class="is-right"><?= money((int) $accountOrder['grand_total_paise']) ?></td>
                                    <td class="is-right table-actions">
                                        <a class="table-action" href="/order/<?= e($accountOrder['order_no']) ?>">View <?= icon('arrow-right') ?></a>
                                        <a class="table-action" href="/order/<?= e($accountOrder['order_no']) ?>/print" target="_blank"><?= icon('file') ?> Invoice</a>
                                        <?php if ($accountOrder['status'] !== 'cancelled'): ?>
                                        <form action="/order/<?= e($accountOrder['order_no']) ?>/reorder" method="post" style="display:inline">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="table-action"><?= icon('refresh') ?> Reorder</button>
                                        </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state empty-state--inline">
                        <span class="empty-state__icon"><?= icon('package') ?></span>
                        <h2>No orders yet</h2>
                        <p>Once you place an order, its configuration and status will be available here.</p>
                        <a class="btn btn--primary" href="/catalog">Browse the catalog</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php
/** @var array $orders */
/** @var array $enquiries */
/** @var array $stats */
$customer = current_customer();
?>
<section class="page-hero page-hero--compact">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="/">Home</a><?= icon('chevron-right') ?><span>Account</span>
        </nav>
        <div class="page-hero__grid">
            <div>
                <p class="eyebrow"><span></span> Trade account</p>
                <h1>Good to see you, <?= e(explode(' ', (string) ($customer['contact_name'] ?: $customer['company_name']))[0]) ?>.</h1>
                <p class="page-hero__lede"><?= e($customer['company_name']) ?><?= $customer['customer_code'] ? ' · ' . e($customer['customer_code']) : '' ?>. Your pricing and order history are below.</p>
            </div>
            <div class="page-hero__aside page-hero__aside--account">
                <span class="tier-badge"><?= e(ucfirst((string) ($customer['price_tier'] ?: 'standard'))) ?> account</span>
                <?php if ((int) $customer['discount_bp'] > 0): ?>
                    <strong><?= rtrim(rtrim(number_format($customer['discount_bp'] / 100, 2, '.', ''), '0'), '.') ?>%</strong>
                    <small>account discount</small>
                <?php else: ?>
                    <strong>B2B</strong>
                    <small>trade pricing</small>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<section class="section section--tight">
    <div class="container account-layout">
        <?php partial('account-nav'); ?>

        <div class="account-main">
            <div class="stat-grid">
                <div class="stat-card">
                    <span class="stat-card__label">Total orders</span>
                    <strong><?= (int) $stats['orders'] ?></strong>
                    <small>since your account opened</small>
                </div>
                <div class="stat-card">
                    <span class="stat-card__label">Open orders</span>
                    <strong><?= (int) $stats['open'] ?></strong>
                    <small>in production or dispatch</small>
                </div>
                <div class="stat-card stat-card--accent">
                    <span class="stat-card__label">Ordered value</span>
                    <strong><?= money((int) $stats['value']) ?></strong>
                    <small>excluding cancelled orders</small>
                </div>
            </div>

            <div class="panel">
                <div class="panel__head">
                    <div>
                        <h2>Recent orders</h2>
                        <p>Every line keeps the configuration you submitted.</p>
                    </div>
                    <a class="text-link" href="/account/orders">All orders <?= icon('arrow-right') ?></a>
                </div>
                <?php if ($orders): ?>
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead><tr><th>Order</th><th>Placed</th><th>Lines</th><th>Status</th><th class="is-right">Total</th></tr></thead>
                            <tbody>
                            <?php foreach ($orders as $accountOrder): ?>
                                <tr>
                                    <td><a class="table-link" href="/order/<?= e($accountOrder['order_no']) ?>"><?= e($accountOrder['order_no']) ?></a></td>
                                    <td><?= e(format_date($accountOrder['placed_at'])) ?></td>
                                    <td><?= (int) db()->value('SELECT COUNT(*) FROM order_items WHERE order_id = ?', [$accountOrder['id']]) ?></td>
                                    <td><span class="status-pill <?= e(order_status_class($accountOrder['status'])) ?>"><?= e(order_status_label($accountOrder['status'])) ?></span></td>
                                    <td class="is-right"><?= money((int) $accountOrder['grand_total_paise']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-inline">
                        <span><?= icon('package') ?></span>
                        <div><strong>No orders yet</strong><p>Your first configured order will appear here.</p></div>
                        <a class="btn btn--primary btn--sm" href="/catalog">Browse catalog</a>
                    </div>
                <?php endif; ?>
            </div>

            <div class="account-columns">
                <div class="panel">
                    <div class="panel__head">
                        <div><h2>Quote requests</h2><p>Specifications sent to our sales engineers.</p></div>
                        <a class="text-link" href="/account/enquiries">View all</a>
                    </div>
                    <?php if ($enquiries): ?>
                        <ul class="enquiry-list">
                            <?php foreach ($enquiries as $enquiry): ?>
                                <li>
                                    <span class="enquiry-list__icon"><?= icon('clipboard') ?></span>
                                    <div>
                                        <strong><?= e($enquiry['product_name'] ?: 'General requirement') ?></strong>
                                        <small><?= e(format_date($enquiry['created_at'])) ?> · Qty <?= (int) $enquiry['qty'] ?></small>
                                    </div>
                                    <span class="status-pill status-<?= e($enquiry['status']) ?>"><?= e(ucfirst($enquiry['status'])) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="empty-inline"><p>No quote requests yet.</p><a class="btn btn--outline btn--sm" href="/quote">Send a requirement</a></div>
                    <?php endif; ?>
                </div>

                <div class="panel">
                    <div class="panel__head"><div><h2>Account details</h2><p>Managed by the Ronak Fire customer portal.</p></div></div>
                    <dl class="account-details">
                        <div><dt>Company</dt><dd><?= e($customer['company_name']) ?></dd></div>
                        <div><dt>Contact</dt><dd><?= e($customer['contact_name'] ?: '—') ?></dd></div>
                        <div><dt>Email</dt><dd><?= e($customer['email'] ?: '—') ?></dd></div>
                        <div><dt>Phone</dt><dd><?= e($customer['phone'] ?: '—') ?></dd></div>
                        <div><dt>GSTIN</dt><dd><?= e($customer['gstin'] ?: 'Not on file') ?></dd></div>
                    </dl>
                    <p class="panel__note"><?= icon('lock') ?> To change your company details or portal password, use <a href="https://rfipl.ronakfire.com" target="_blank" rel="noopener">rfipl.ronakfire.com</a>.</p>
                </div>
            </div>
        </div>
    </div>
</section>

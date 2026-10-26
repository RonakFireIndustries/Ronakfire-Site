<?php
/** @var array $customers */
/** @var string $q */
/** @var array $pager */
?>
<div class="admin-page-head">
    <div>
        <p class="admin-eyebrow">Sales</p>
        <h2>Customers</h2>
        <p>Trade accounts mirrored from the customer portal. Pricing tiers and discounts are controlled here.</p>
    </div>
</div>

<form class="admin-filters" action="/admin/customers" method="get">
    <label class="admin-search">
        <span class="sr-only">Search customers</span>
        <?= icon('search') ?>
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Company, contact, email, code or GSTIN…">
    </label>
    <button class="admin-btn" type="submit">Search</button>
    <?php if ($q !== ''): ?><a class="admin-btn admin-btn--ghost" href="/admin/customers">Clear</a><?php endif; ?>
</form>

<section class="admin-panel">
    <?php if ($customers): ?>
        <div class="admin-table-wrap">
            <table class="admin-table admin-table--customers">
                <thead>
                <tr><th>Company</th><th>Contact</th><th>Tier</th><th class="is-right">Discount</th><th class="is-right">Orders</th><th class="is-right">Value</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                <?php foreach ($customers as $customer): ?>
                    <tr>
                        <td>
                            <a class="admin-table__link" href="/admin/customers/<?= (int) $customer['id'] ?>"><?= e($customer['company_name']) ?></a>
                            <small><?= e($customer['customer_code'] ?: 'No code') ?><?= $customer['gstin'] ? ' · ' . e($customer['gstin']) : '' ?></small>
                        </td>
                        <td>
                            <?= e($customer['contact_name'] ?: '—') ?>
                            <small><?= e($customer['email'] ?: '') ?><?= $customer['phone'] ? ' · ' . e($customer['phone']) : '' ?></small>
                        </td>
                        <td><span class="admin-tag"><?= e(ucfirst((string) ($customer['price_tier'] ?: 'standard'))) ?></span></td>
                        <td class="is-right"><?= (int) $customer['discount_bp'] > 0 ? rtrim(rtrim(number_format($customer['discount_bp'] / 100, 2, '.', ''), '0'), '.') . '%' : '—' ?></td>
                        <td class="is-right"><?= (int) $customer['order_count'] ?></td>
                        <td class="is-right"><?= money((int) $customer['order_value']) ?></td>
                        <td><?php if ((int) $customer['is_active'] === 1): ?><span class="admin-status admin-status--visible">Active</span><?php else: ?><span class="admin-status admin-status--hidden">Blocked</span><?php endif; ?></td>
                        <td class="is-right"><a class="admin-table__action" href="/admin/customers/<?= (int) $customer['id'] ?>"><?= icon('chevron-right') ?></a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php partial('admin/partials/pagination', ['pager' => $pager, 'baseUrl' => '/admin/customers']); ?>
    <?php else: ?>
        <div class="admin-empty">
            <?= icon('users') ?>
            <p><?= $q !== '' ? 'No customers match this search.' : 'No customers yet. Accounts appear after the first portal sign-in.' ?></p>
            <?php if ($q !== ''): ?><a class="admin-btn" href="/admin/customers">Clear search</a><?php endif; ?>
        </div>
    <?php endif; ?>
</section>

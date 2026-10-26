<?php
/** @var array $customer */
/** @var array $orders */
/** @var array $enquiries */
$orderValue = 0;
foreach ($orders as $row) {
    if ($row['status'] !== 'cancelled') {
        $orderValue += (int) $row['grand_total_paise'];
    }
}
?>
<div class="admin-page-head">
    <div>
        <p class="admin-eyebrow"><a href="/admin/customers">Customers</a> / <?= e($customer['customer_code'] ?: 'Account') ?></p>
        <h2><?= e($customer['company_name']) ?></h2>
        <p>Portal customer ID <code><?= e($customer['portal_id']) ?></code><?= $customer['last_login_at'] ? ' · last seen ' . e(format_datetime($customer['last_login_at'])) : '' ?>.</p>
    </div>
    <div class="admin-page-head__actions">
        <a class="admin-btn admin-btn--ghost" href="/admin/customers">Back</a>
        <a class="admin-btn" href="/admin/orders?q=<?= urlencode((string) ($customer['company_name'])) ?>"><?= icon('cart') ?> Their orders</a>
    </div>
</div>

<div class="admin-stats admin-stats--compact">
    <div class="admin-stat">
        <span class="admin-stat__label">Orders</span>
        <strong><?= count($orders) ?></strong>
        <small><?= count(array_filter($orders, static fn (array $o): bool => in_array($o['status'], ['placed', 'confirmed', 'invoiced', 'dispatched'], true))) ?> open</small>
    </div>
    <div class="admin-stat">
        <span class="admin-stat__label">Ordered value</span>
        <strong><?= money($orderValue) ?></strong>
        <small>Excluding cancellations</small>
    </div>
    <div class="admin-stat">
        <span class="admin-stat__label">Quote requests</span>
        <strong><?= count($enquiries) ?></strong>
        <small>All time</small>
    </div>
    <div class="admin-stat">
        <span class="admin-stat__label">Account status</span>
        <strong><span class="admin-status admin-status--<?= (int) $customer['is_active'] === 1 ? 'visible' : 'hidden' ?>"><?= (int) $customer['is_active'] === 1 ? 'Active' : 'Blocked' ?></span></strong>
        <small><?= e(ucfirst((string) ($customer['price_tier'] ?: 'standard'))) ?> tier</small>
    </div>
</div>

<div class="admin-grid admin-grid--main">
    <div class="admin-stack">
        <section class="admin-panel">
            <div class="admin-panel__head"><div><h2>Order history</h2><p>Every order placed through the store.</p></div></div>
            <?php if ($orders): ?>
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead><tr><th>Order</th><th>Placed</th><th>Status</th><th class="is-right">Total</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><a class="admin-table__link" href="/admin/orders/<?= (int) $order['id'] ?>"><?= e($order['order_no']) ?></a><small><?= e($order['po_number'] ? 'PO ' . $order['po_number'] : 'No PO') ?></small></td>
                                <td><?= e(format_date($order['placed_at'])) ?></td>
                                <td><span class="admin-status admin-status--<?= e($order['status']) ?>"><?= e(order_status_label($order['status'])) ?></span></td>
                                <td class="is-right"><?= money((int) $order['grand_total_paise']) ?></td>
                                <td class="is-right"><a class="admin-table__action" href="/admin/orders/<?= (int) $order['id'] ?>"><?= icon('chevron-right') ?></a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="admin-empty admin-empty--sm"><?= icon('cart') ?><p>No orders on this account yet.</p></div>
            <?php endif; ?>
        </section>

        <section class="admin-panel">
            <div class="admin-panel__head"><div><h2>Quote requests</h2><p>Specifications sent from the storefront.</p></div></div>
            <?php if ($enquiries): ?>
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead><tr><th>Requirement</th><th>Received</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($enquiries as $enquiry): ?>
                            <tr>
                                <td><a class="admin-table__link" href="/admin/enquiries/<?= (int) $enquiry['id'] ?>"><?= e($enquiry['product_name'] ?: 'General requirement') ?></a><small>Qty <?= (int) $enquiry['qty'] ?></small></td>
                                <td><?= e(format_date($enquiry['created_at'])) ?></td>
                                <td><span class="admin-status admin-status--<?= e($enquiry['status']) ?>"><?= e(ucfirst($enquiry['status'])) ?></span></td>
                                <td class="is-right"><a class="admin-table__action" href="/admin/enquiries/<?= (int) $enquiry['id'] ?>"><?= icon('chevron-right') ?></a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="admin-empty admin-empty--sm"><?= icon('clipboard') ?><p>No quote requests from this customer.</p></div>
            <?php endif; ?>
        </section>
    </div>

    <aside class="admin-stack">
        <section class="admin-panel admin-panel--form">
            <div class="admin-panel__head"><div><h2>Account &amp; pricing</h2><p>Commercial terms applied at checkout.</p></div></div>
            <form action="/admin/customers/<?= (int) $customer['id'] ?>/save" method="post" class="admin-form-body">
                <?= csrf_field() ?>
                <label class="admin-field"><span>Company name</span><input type="text" name="company_name" value="<?= e($customer['company_name']) ?>" required></label>
                <label class="admin-field"><span>Contact name</span><input type="text" name="contact_name" value="<?= e($customer['contact_name']) ?>"></label>
                <label class="admin-field"><span>Email</span><input type="email" name="email" value="<?= e($customer['email']) ?>"></label>
                <label class="admin-field"><span>Phone</span><input type="tel" name="phone" value="<?= e($customer['phone']) ?>"></label>
                <label class="admin-field"><span>GSTIN</span><input type="text" name="gstin" value="<?= e($customer['gstin']) ?>" maxlength="15"></label>
                <label class="admin-field"><span>Address line 1</span><input type="text" name="address_line1" value="<?= e($customer['address_line1']) ?>"></label>
                <label class="admin-field"><span>Address line 2</span><input type="text" name="address_line2" value="<?= e($customer['address_line2']) ?>"></label>
                <div class="admin-field-grid admin-field-grid--2">
                    <label class="admin-field"><span>City</span><input type="text" name="city" value="<?= e($customer['city']) ?>"></label>
                    <label class="admin-field"><span>State</span><input type="text" name="state" value="<?= e($customer['state']) ?>"></label>
                    <label class="admin-field"><span>PIN code</span><input type="text" name="pincode" value="<?= e($customer['pincode']) ?>" maxlength="6"></label>
                    <label class="admin-field">
                        <span>Price tier</span>
                        <select name="price_tier">
                            <?php foreach (['standard' => 'Standard', 'dealer' => 'Dealer', 'contractor' => 'Contractor', 'institution' => 'Institution', 'project' => 'Project'] as $value => $label): ?>
                                <option value="<?= e($value) ?>" <?= $customer['price_tier'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="admin-field"><span>Discount (%)</span><input type="text" name="discount_percent" value="<?= e(rtrim(rtrim(number_format($customer['discount_bp'] / 100, 2, '.', ''), '0'), '.')) ?>" inputmode="decimal"></label>
                    <label class="admin-field"><span>Credit days</span><input type="number" name="credit_days" value="<?= (int) $customer['credit_days'] ?>" min="0"></label>
                    <label class="admin-field"><span>Credit limit (₹)</span><input type="text" name="credit_limit" value="<?= e(admin_money_input((int) $customer['credit_limit_paise'])) ?>" inputmode="decimal"></label>
                </div>
                <label class="admin-toggle">
                    <input type="checkbox" name="is_active" value="1" <?= (int) $customer['is_active'] === 1 ? 'checked' : '' ?>>
                    <span><strong>Account active</strong><small>Blocked accounts cannot sign in or order.</small></span>
                </label>
                <button class="admin-btn admin-btn--primary admin-btn--block" type="submit"><?= icon('check') ?> Save account</button>
            </form>
        </section>

        <section class="admin-panel">
            <div class="admin-panel__head"><div><h2>Record</h2></div></div>
            <div class="admin-panel__body">
                <dl class="admin-meta-list">
                    <div><dt>Portal ID</dt><dd><code><?= e($customer['portal_id']) ?></code></dd></div>
                    <div><dt>Created</dt><dd><?= e(format_datetime($customer['created_at'])) ?></dd></div>
                    <div><dt>Last login</dt><dd><?= e(format_datetime($customer['last_login_at'])) ?></dd></div>
                    <div><dt>Updated</dt><dd><?= e(format_datetime($customer['updated_at'])) ?></dd></div>
                </dl>
            </div>
        </section>
    </aside>
</div>

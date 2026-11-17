<?php
/** @var array $enquiry */
/** @var array|null $product */
/** @var array|null $customer */
?>
<div class="admin-page-head">
    <div>
        <p class="admin-eyebrow"><a href="/admin/enquiries">Quote requests</a> / #<?= (int) $enquiry['id'] ?></p>
        <h2><?= e($enquiry['product_name'] ?: 'General requirement') ?></h2>
        <p>Received <?= e(format_datetime($enquiry['created_at'])) ?> from <?= e($enquiry['company'] ?: $enquiry['name']) ?>.</p>
    </div>
    <div class="admin-page-head__actions">
        <a class="admin-btn admin-btn--ghost" href="/admin/enquiries">Back</a>
        <a class="admin-btn" href="mailto:<?= e($enquiry['email']) ?>?subject=<?= rawurlencode('Your quote request #' . $enquiry['id'] . ' — Ronak Fire') ?>"><?= icon('mail') ?> Reply by email</a>
    </div>
</div>

<div class="admin-grid admin-grid--main">
    <div class="admin-stack">
        <section class="admin-panel">
            <div class="admin-panel__head"><div><h2>Requirement</h2><p>The specification supplied by the customer.</p></div></div>
            <div class="admin-panel__body">
                <dl class="admin-meta-list admin-meta-list--wide">
                    <div><dt>Product</dt><dd><?= e($enquiry['product_name'] ?: 'General enquiry') ?></dd></div>
                    <div><dt>Quantity</dt><dd><?= (int) $enquiry['qty'] ?></dd></div>
                    <div><dt>Status</dt><dd><span class="admin-status admin-status--<?= e($enquiry['status']) ?>"><?= e(ucfirst($enquiry['status'])) ?></span></dd></div>
                    <div><dt>Received</dt><dd><?= e(format_datetime($enquiry['created_at'])) ?></dd></div>
                    <?php if (!empty($enquiry['configuration'])): ?>
                        <div class="admin-detail-grid__full"><dt>Configuration</dt><dd><?= e($enquiry['configuration']) ?></dd></div>
                    <?php endif; ?>
                </dl>
                <div class="admin-message">
                    <h3>Message</h3>
                    <p><?= $enquiry['message'] ? nl2br(e($enquiry['message'])) : 'No additional message supplied.' ?></p>
                </div>
                <?php if ($product): ?>
                    <div class="admin-quote-product">
                        <?php if (!empty($product['image'])): ?><img src="<?= e($product['image']) ?>" alt=""><?php else: ?><?= icon('package') ?><?php endif; ?>
                        <div>
                            <strong><?= e($product['name']) ?></strong>
                            <small><?= e($product['sku']) ?> · <?= e($product['category_name']) ?></small>
                        </div>
                        <a class="admin-link" href="/product/<?= e($product['slug']) ?>" target="_blank" rel="noopener">View product <?= icon('arrow-right') ?></a>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="admin-panel admin-panel--form">
            <div class="admin-panel__head"><div><h2>Internal note</h2><p>Keep the customer-facing reply in email; use this for the store record.</p></div></div>
            <div class="admin-form-body">
                <p class="admin-hint-text"><?= icon('alert') ?> Notes are not currently stored. Add a status change to reflect progress, then reply by email.</p>
            </div>
        </section>
    </div>

    <aside class="admin-stack">
        <section class="admin-panel">
            <div class="admin-panel__head"><div><h2>Progress</h2></div></div>
            <div class="admin-panel__body">
                <form action="/admin/enquiries/<?= (int) $enquiry['id'] ?>/status" method="post" class="admin-inline-form admin-inline-form--stacked">
                    <?= csrf_field() ?>
                    <label class="admin-field">
                        <span>Status</span>
                        <select name="status">
                            <?php foreach (['new' => 'New', 'contacted' => 'Contacted', 'quoted' => 'Quoted', 'closed' => 'Closed'] as $value => $label): ?>
                                <option value="<?= e($value) ?>" <?= $enquiry['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <button class="admin-btn admin-btn--primary admin-btn--block" type="submit">Save status</button>
                </form>
            </div>
        </section>

        <section class="admin-panel">
            <div class="admin-panel__head"><div><h2>Contact</h2></div></div>
            <div class="admin-panel__body">
                <div class="admin-address">
                    <strong><?= e($enquiry['name']) ?></strong>
                    <p><?= e($enquiry['company'] ?: '') ?><br>
                        <?= e($enquiry['phone'] ?: '') ?><br>
                        <a href="mailto:<?= e($enquiry['email']) ?>"><?= e($enquiry['email']) ?></a></p>
                    <?php if (!empty($enquiry['gstin'])): ?><p><span class="admin-tag">GSTIN <?= e($enquiry['gstin']) ?></span></p><?php endif; ?>
                </div>
                <?php if ($customer): ?>
                    <p class="admin-hint-text">Sales owner: <?= e($customer['sales_owner_name'] ?: 'Unassigned') ?></p>
                    <a class="admin-btn admin-btn--block" href="/admin/customers/<?= (int) $customer['id'] ?>"><?= icon('users') ?> Open customer account</a>
                <?php else: ?>
                    <p class="admin-hint-text"><?= icon('alert') ?> This enquiry was sent by a visitor who was not signed in.</p>
                <?php endif; ?>
            </div>
        </section>
    </aside>
</div>

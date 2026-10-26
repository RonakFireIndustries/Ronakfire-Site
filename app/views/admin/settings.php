<?php
/** @var array $values */
?>
<div class="admin-page-head">
    <div>
        <p class="admin-eyebrow">System</p>
        <h2>Store settings</h2>
        <p>Commercial rules and public contact details used across the storefront and invoices.</p>
    </div>
</div>

<form class="admin-form" action="/admin/settings" method="post">
    <?= csrf_field() ?>

    <div class="admin-form-grid">
        <div class="admin-form-main">
            <section class="admin-panel admin-panel--form">
                <div class="admin-panel__head"><div><h2>Order rules</h2><p>Applied server-side at checkout and shown in the cart.</p></div></div>
                <div class="admin-form-body">
                    <div class="admin-field-grid">
                        <label class="admin-field">
                            <span>Minimum order value (₹, excl. GST)</span>
                            <input type="text" name="min_order" value="<?= e(admin_money_input((int) $values['min_order_paise'])) ?>" inputmode="decimal">
                            <small>Set to 0 to remove the minimum.</small>
                        </label>
                        <label class="admin-field admin-field--full">
                            <span>Freight note</span>
                            <textarea name="freight_note" rows="3"><?= e($values['freight_note']) ?></textarea>
                            <small>Shown in the cart and at checkout while freight is pending.</small>
                        </label>
                    </div>
                </div>
            </section>

            <section class="admin-panel admin-panel--form">
                <div class="admin-panel__head"><div><h2>Contact &amp; announcement</h2><p>Used in the header, footer and quote confirmations.</p></div></div>
                <div class="admin-form-body">
                    <div class="admin-field-grid">
                        <label class="admin-field">
                            <span>Support email</span>
                            <input type="email" name="support_email" value="<?= e($values['support_email']) ?>">
                        </label>
                        <label class="admin-field">
                            <span>Support phone</span>
                            <input type="text" name="support_phone" value="<?= e($values['support_phone']) ?>">
                        </label>
                        <label class="admin-field">
                            <span>Phone link (digits only)</span>
                            <input type="text" name="support_phone_href" value="<?= e($values['support_phone_href']) ?>" placeholder="+919323947512">
                        </label>
                        <label class="admin-field admin-field--full">
                            <span>Announcement bar</span>
                            <input type="text" name="announcement" value="<?= e($values['announcement']) ?>" maxlength="180">
                        </label>
                    </div>
                </div>
            </section>
        </div>

        <aside class="admin-form-aside">
            <section class="admin-panel admin-panel--form">
                <div class="admin-panel__head"><div><h2>Pricing status</h2></div></div>
                <div class="admin-form-body">
                    <label class="admin-toggle">
                        <input type="checkbox" name="prices_are_placeholders" value="1" <?= $values['prices_are_placeholders'] === '1' ? 'checked' : '' ?>>
                        <span><strong>Prices are placeholders</strong><small>Shows a warning in the admin header until real prices are entered.</small></span>
                    </label>
                    <div class="admin-hint">
                        <?= icon('alert') ?>
                        <p>Products were seeded from engineering PDFs that do not contain commercial pricing. Replace every base price and dimension rate before taking live orders.</p>
                    </div>
                </div>
            </section>

            <section class="admin-panel admin-panel--form">
                <div class="admin-panel__head"><div><h2>Portal integration</h2></div></div>
                <div class="admin-form-body">
                    <dl class="admin-meta-list">
                        <div><dt>Portal</dt><dd>rfipl.ronakfire.com</dd></div>
                        <div><dt>Cookie</dt><dd><code><?= e((string) config('portal.cookie')) ?></code></dd></div>
                        <div><dt>Domain</dt><dd><code><?= e((string) (config('portal.cookie_domain') ?: 'not set')) ?></code></dd></div>
                        <div><dt>JWT secret</dt><dd><?= (string) config('portal.jwt_secret') !== '' ? 'Configured' : 'Not configured' ?></dd></div>
                    </dl>
                    <p class="admin-hint-text"><?= icon('lock') ?> Portal credentials live in <code>config.local.php</code> and are never editable from the browser.</p>
                </div>
            </section>
        </aside>
    </div>

    <div class="admin-form-footer">
        <span>Settings are applied immediately across the storefront.</span>
        <button class="admin-btn admin-btn--primary" type="submit"><?= icon('check') ?> Save settings</button>
    </div>
</form>

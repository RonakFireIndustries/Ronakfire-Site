<?php
/**
 * Customer profile / account details.
 *
 * Contact person, phone and address can be edited by the customer.
 * Company, GSTIN and commercial terms stay locked to the sales team.
 */
$customer = current_customer();
$states = indian_states();
$discount = (int) ($customer['discount_bp'] ?? 0) / 100;
?>
<section class="page-hero page-hero--compact">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="/">Home</a><?= icon('chevron-right') ?><a href="/account">Account</a><?= icon('chevron-right') ?><span>My details</span>
        </nav>
        <div class="page-hero__grid">
            <div>
                <p class="eyebrow"><span></span> Trade account</p>
                <h1>My details</h1>
                <p class="page-hero__lede">Keep your contact and delivery address up to date. Company, GSTIN and commercial terms are managed by our sales team.</p>
            </div>
            <div class="page-hero__aside page-hero__aside--account">
                <span class="tier-badge"><?= e(ucfirst((string) ($customer['price_tier'] ?: 'standard'))) ?> account</span>
                <strong><?= e($customer['customer_code'] ?: 'RF') ?></strong>
                <small>customer code</small>
            </div>
        </div>
    </div>
</section>

<section class="section section--tight">
    <div class="container account-layout">
        <?php partial('account-nav'); ?>

        <div class="account-main">
            <div class="account-columns">
                <div class="panel">
                    <div class="panel__head">
                        <div>
                            <h2>Company &amp; commercial terms</h2>
                            <p>Locked to your trade agreement.</p>
                        </div>
                    </div>
                    <dl class="account-details">
                        <div><dt>Company</dt><dd><?= e($customer['company_name']) ?></dd></div>
                        <div><dt>Customer code</dt><dd><?= e($customer['customer_code'] ?: '—') ?></dd></div>
                        <div><dt>GSTIN</dt><dd><?= e($customer['gstin'] ?: 'Not on file') ?></dd></div>
                        <div><dt>Price tier</dt><dd><?= e(ucfirst((string) ($customer['price_tier'] ?: 'standard'))) ?></dd></div>
                        <div><dt>Account discount</dt><dd><?= $discount > 0 ? e(rtrim(rtrim(number_format($discount, 2, '.', ''), '0'), '.')) . '%' : '—' ?></dd></div>
                        <div><dt>Credit days</dt><dd><?= (int) ($customer['credit_days'] ?? 0) ?></dd></div>
                        <div><dt>Credit limit</dt><dd><?= money((int) ($customer['credit_limit_paise'] ?? 0)) ?></dd></div>
                    </dl>
                    <p class="panel__note"><?= icon('lock') ?> To change your company name, GSTIN or commercial terms, contact <a href="mailto:<?= e(setting('support_email', config('store.support_email'))) ?>"><?= e(setting('support_email', config('store.support_email'))) ?></a>.</p>
                </div>

                <div class="panel">
                    <div class="panel__head">
                        <div>
                            <h2>Sign-in &amp; status</h2>
                            <p>Your login identity on this store.</p>
                        </div>
                    </div>
                    <dl class="account-details">
                        <div><dt>Email</dt><dd><?= e($customer['email'] ?: '—') ?></dd></div>
                        <div><dt>Contact person</dt><dd><?= e($customer['contact_name'] ?: '—') ?></dd></div>
                        <div><dt>Phone</dt><dd><?= e($customer['phone'] ?: '—') ?></dd></div>
                        <div><dt>Account status</dt><dd><?= (int) $customer['is_active'] === 1 ? 'Active' : 'Blocked' ?></dd></div>
                        <div><dt>Last sign-in</dt><dd><?= e(format_datetime($customer['last_login_at'])) ?></dd></div>
                    </dl>
                    <p class="panel__note"><?= icon('shield') ?> Need to change your sign-in email or reset your password? Contact <a href="mailto:<?= e(setting('support_email', config('store.support_email'))) ?>"><?= e(setting('support_email', config('store.support_email'))) ?></a>.</p>
                </div>
            </div>

            <div class="panel">
                <div class="panel__head">
                    <div>
                        <h2>Contact &amp; delivery address</h2>
                        <p>Used to prefill checkout and keep you updated about orders.</p>
                    </div>
                </div>
                <form class="form-grid panel-form" action="/account/profile" method="post" novalidate>
                    <?= csrf_field() ?>

                    <label class="field">
                        <span>Contact person</span>
                        <input type="text" name="contact_name" value="<?= e($customer['contact_name']) ?>" maxlength="191" autocomplete="name">
                    </label>
                    <label class="field">
                        <span>Phone</span>
                        <input type="tel" name="phone" value="<?= e($customer['phone']) ?>" maxlength="32" autocomplete="tel" inputmode="tel">
                    </label>

                    <label class="field field--full">
                        <span>Address line 1</span>
                        <input type="text" name="address_line1" value="<?= e($customer['address_line1']) ?>" maxlength="255" autocomplete="address-line1" placeholder="Street, building, area">
                    </label>
                    <label class="field field--full">
                        <span>Address line 2 <small>optional</small></span>
                        <input type="text" name="address_line2" value="<?= e($customer['address_line2']) ?>" maxlength="255" autocomplete="address-line2">
                    </label>
                    <label class="field">
                        <span>City</span>
                        <input type="text" name="city" value="<?= e($customer['city']) ?>" maxlength="100" autocomplete="address-level2">
                    </label>
                    <label class="field">
                        <span>State</span>
                        <select name="state" autocomplete="address-level1">
                            <option value="">Select state</option>
                            <?php foreach ($states as $state): ?>
                                <option value="<?= e($state) ?>" <?= ($customer['state'] ?? '') === $state ? 'selected' : '' ?>><?= e($state) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field">
                        <span>PIN code</span>
                        <input type="text" name="pincode" value="<?= e($customer['pincode']) ?>" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" autocomplete="postal-code">
                    </label>

                    <div class="field field--full field--actions">
                        <button class="btn btn--primary" type="submit">Save changes <?= icon('check') ?></button>
                        <a class="btn btn--outline" href="/account">Cancel</a>
                        <a class="text-link" href="/account/addresses">Manage saved delivery addresses <?= icon('arrow-right') ?></a>
                    </div>
                </form>
            </div>

            <div class="panel">
                <div class="panel__head">
                    <div>
                        <h2>More from your account</h2>
                        <p>Stock, documents and saved delivery sites.</p>
                    </div>
                </div>
                <div class="account-quick-links">
                    <a class="btn btn--outline btn--sm" href="/account/inventory"><?= icon('package') ?> Inventory</a>
                    <a class="btn btn--outline btn--sm" href="/account/documents"><?= icon('file') ?> Documents</a>
                    <a class="btn btn--outline btn--sm" href="/account/addresses"><?= icon('map-pin') ?> Saved addresses</a>
                    <a class="btn btn--outline btn--sm" href="/account/orders"><?= icon('cart') ?> Orders</a>
                </div>
            </div>
        </div>
    </div>
</section>

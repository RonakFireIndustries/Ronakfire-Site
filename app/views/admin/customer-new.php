<?php
/** @var array $customer */
/** @var array $states */
$errorFor = static fn (string $field): string => ''; // placeholder; errors use flash in this controller
?>
<div class="admin-page-head">
    <div>
        <p class="admin-eyebrow"><a href="/admin/customers">Customers</a> / New</p>
        <h2>Create customer account</h2>
        <p>Once created, login details are emailed to the customer. They can then view orders, inventory and documents.</p>
    </div>
    <div class="admin-page-head__actions">
        <a class="admin-btn admin-btn--ghost" href="/admin/customers"><?= icon('arrow-left') ?> Back to customers</a>
    </div>
</div>

<section class="admin-panel">
    <form action="/admin/customers/create" method="post" class="admin-form-body">
        <?= csrf_field() ?>

        <div class="admin-form-grid">
            <div class="admin-form-col admin-form-col--span-2">
                <label class="admin-field admin-field--full">
                    <span>Company / institution <em>*</em></span>
                    <input type="text" name="company_name" value="<?= e($customer['company_name']) ?>" autocomplete="organization" required>
                </label>
            </div>

            <div class="admin-form-col admin-form-col--span-1">
                <label class="admin-field">
                    <span>Contact name <em>*</em></span>
                    <input type="text" name="contact_name" value="<?= e($customer['contact_name']) ?>" autocomplete="name" required>
                </label>
            </div>

            <div class="admin-form-col admin-form-col--span-1">
                <label class="admin-field">
                    <span>Email (User ID) <em>*</em></span>
                    <input type="email" name="email" value="<?= e($customer['email']) ?>" autocomplete="email" required>
                </label>
            </div>

            <div class="admin-form-col admin-form-col--span-1">
                <label class="admin-field">
                    <span>Phone</span>
                    <input type="tel" name="phone" value="<?= e($customer['phone']) ?>" autocomplete="tel" inputmode="tel">
                </label>
            </div>

            <div class="admin-form-col admin-form-col--span-1">
                <label class="admin-field">
                    <span>GSTIN <small>optional</small></span>
                    <input type="text" name="gstin" value="<?= e($customer['gstin']) ?>" maxlength="15" autocomplete="off" placeholder="27ABCDE1234F1Z5">
                </label>
            </div>

            <div class="admin-form-col admin-form-col--span-2">
                <label class="admin-field admin-field--full">
                    <span>Address line 1 <em>*</em></span>
                    <input type="text" name="address_line1" value="<?= e($customer['address_line1']) ?>" autocomplete="address-line1" required>
                </label>
            </div>

            <div class="admin-form-col admin-form-col--span-2">
                <label class="admin-field admin-field--full">
                    <span>Address line 2 <small>optional</small></span>
                    <input type="text" name="address_line2" value="<?= e($customer['address_line2']) ?>" autocomplete="address-line2">
                </label>
            </div>

            <div class="admin-form-col admin-form-col--span-1">
                <label class="admin-field">
                    <span>City <em>*</em></span>
                    <input type="text" name="city" value="<?= e($customer['city']) ?>" autocomplete="address-level2" required>
                </label>
            </div>

            <div class="admin-form-col admin-form-col--span-1">
                <label class="admin-field">
                    <span>State <em>*</em></span>
                    <select name="state" autocomplete="address-level1" required>
                        <option value="">Select state</option>
                        <?php foreach ($states as $state): ?>
                            <option value="<?= e($state) ?>" <?= $customer['state'] === $state ? 'selected' : '' ?>><?= e($state) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>

            <div class="admin-form-col admin-form-col--span-1">
                <label class="admin-field">
                    <span>PIN code <em>*</em></span>
                    <input type="text" name="pincode" value="<?= e($customer['pincode']) ?>" maxlength="6" pattern="\d{6}" inputmode="numeric" autocomplete="postal-code" required>
                </label>
            </div>

            <div class="admin-form-col admin-form-col--span-1">
                <label class="admin-field">
                    <span>Price tier</span>
                    <select name="price_tier">
                        <option value="standard" <?= $customer['price_tier'] === 'standard' ? 'selected' : '' ?>>Standard</option>
                        <option value="dealer" <?= $customer['price_tier'] === 'dealer' ? 'selected' : '' ?>>Dealer</option>
                        <option value="contractor" <?= $customer['price_tier'] === 'contractor' ? 'selected' : '' ?>>Contractor</option>
                        <option value="institutional" <?= $customer['price_tier'] === 'institutional' ? 'selected' : '' ?>>Institutional</option>
                    </select>
                </label>
            </div>

            <div class="admin-form-col admin-form-col--span-1">
                <label class="admin-field">
                    <span>Discount (%)</span>
                    <input type="number" name="discount_percent" value="<?= e($customer['discount_percent']) ?>" min="0" max="100" step="0.01">
                </label>
            </div>

            <div class="admin-form-col admin-form-col--span-1">
                <label class="admin-field">
                    <span>Credit days</span>
                    <input type="number" name="credit_days" value="<?= e($customer['credit_days']) ?>" min="0" step="1">
                </label>
            </div>

            <div class="admin-form-col admin-form-col--span-1">
                <label class="admin-field">
                    <span>Credit limit</span>
                    <input type="text" name="credit_limit" value="<?= e($customer['credit_limit']) ?>" placeholder="0">
                </label>
            </div>

            <div class="admin-form-col admin-form-col--span-1">
                <label class="admin-field admin-field--check">
                    <input type="checkbox" name="is_active" value="1" <?= $customer['is_active'] ? 'checked' : '' ?>>
                    <span>Active account</span>
                </label>
            </div>
        </div>

        <div class="admin-form-actions">
            <a class="admin-btn admin-btn--ghost" href="/admin/customers"><?= icon('arrow-left') ?> Cancel</a>
            <button class="admin-btn admin-btn--primary" type="submit"><?= icon('user-plus') ?> Create account &amp; send credentials</button>
        </div>
    </form>
</section>

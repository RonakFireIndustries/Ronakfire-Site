<?php
/**
 * @var array $addresses  Rows from customer_addresses
 */
$stateOptions = ['Andhra Pradesh','Arunachal Pradesh','Assam','Bihar','Chhattisgarh','Goa','Gujarat','Haryana','Himachal Pradesh','Jharkhand','Karnataka','Kerala','Madhya Pradesh','Maharashtra','Manipur','Meghalaya','Mizoram','Nagaland','Odisha','Punjab','Rajasthan','Sikkim','Tamil Nadu','Telangana','Tripura','Uttar Pradesh','Uttarakhand','West Bengal','Andaman and Nicobar Islands','Chandigarh','Dadra and Nagar Haveli and Daman and Diu','Delhi','Jammu and Kashmir','Ladakh','Lakshadweep','Puducherry'];
$editId = (int) input('edit', 0);
$editAddr = null;
foreach ($addresses as $a) {
    if ((int) $a['id'] === $editId) {
        $editAddr = $a;
        break;
    }
}
?>
<section class="page-hero page-hero--compact">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="/">Home</a><?= icon('chevron-right') ?><a href="/account">Account</a><?= icon('chevron-right') ?><span>Addresses</span>
        </nav>
        <div class="page-hero__grid">
            <div>
                <p class="eyebrow"><span></span> Delivery book</p>
                <h1>Saved addresses</h1>
                <p class="page-hero__lede">Store site and billing addresses to prefill checkout quickly.</p>
            </div>
        </div>
    </div>
</section>

<section class="section section--tight">
    <div class="container account-layout">
        <?php partial('account-nav'); ?>

        <div class="account-main">

            <!-- Address cards -->
            <?php if ($addresses): ?>
                <div class="address-grid">
                    <?php foreach ($addresses as $addr): ?>
                        <div class="address-book-card <?= (int) $addr['is_default'] ? 'address-book-card--default' : '' ?>">
                            <?php if ((int) $addr['is_default']): ?>
                                <span class="address-book-card__badge"><?= icon('check') ?> Default</span>
                            <?php endif; ?>
                            <strong><?= e($addr['label']) ?></strong>
                            <?php if ($addr['company_name']): ?><p><?= e($addr['company_name']) ?></p><?php endif; ?>
                            <?php if ($addr['contact_name']): ?><p><?= e($addr['contact_name']) ?></p><?php endif; ?>
                            <p>
                                <?= e($addr['address_line1']) ?>
                                <?php if ($addr['address_line2']): ?><br><?= e($addr['address_line2']) ?><?php endif; ?><br>
                                <?= e($addr['city']) ?>, <?= e($addr['state']) ?> <?= e($addr['pincode']) ?>
                            </p>
                            <?php if ($addr['phone']): ?><small><?= e($addr['phone']) ?></small><?php endif; ?>
                            <div class="address-book-card__actions">
                                <a class="text-link" href="/account/addresses?edit=<?= (int) $addr['id'] ?>">Edit</a>
                                <?php if (!(int) $addr['is_default']): ?>
                                    <form action="/account/addresses/<?= (int) $addr['id'] ?>/default" method="post" style="display:inline">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="link-btn">Make default</button>
                                    </form>
                                    <form action="/account/addresses/<?= (int) $addr['id'] ?>/delete" method="post" style="display:inline"
                                          onsubmit="return confirm('Remove this address?')">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="link-btn link-btn--danger">Remove</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-inline">
                    <span><?= icon('map-pin') ?></span>
                    <div><strong>No saved addresses yet</strong><p>Add your first address below and it will be available to prefill at checkout.</p></div>
                </div>
            <?php endif; ?>

            <!-- Add / edit form -->
            <div class="panel" id="addressForm">
                <div class="panel__head">
                    <div>
                        <h2><?= $editAddr ? 'Edit address' : 'Add address' ?></h2>
                        <p><?= $editAddr ? 'Update the details below.' : 'Save a delivery or billing site address.' ?></p>
                    </div>
                </div>
                <form action="/account/addresses/save" method="post" class="form-grid" novalidate>
                    <?= csrf_field() ?>
                    <?php if ($editAddr): ?>
                        <input type="hidden" name="id" value="<?= (int) $editAddr['id'] ?>">
                    <?php endif; ?>

                    <label class="field field--full">
                        <span>Label <small>e.g. Head office, Site A</small></span>
                        <input type="text" name="label" maxlength="64"
                               value="<?= e($editAddr['label'] ?? '') ?>" placeholder="Default">
                    </label>

                    <label class="field">
                        <span>Company / site name <small>optional</small></span>
                        <input type="text" name="company_name" value="<?= e($editAddr['company_name'] ?? '') ?>">
                    </label>
                    <label class="field">
                        <span>Contact name <small>optional</small></span>
                        <input type="text" name="contact_name" value="<?= e($editAddr['contact_name'] ?? '') ?>">
                    </label>
                    <label class="field">
                        <span>Phone <small>optional</small></span>
                        <input type="tel" name="phone" value="<?= e($editAddr['phone'] ?? '') ?>" inputmode="tel">
                    </label>

                    <label class="field field--full">
                        <span>Address line 1 <em>*</em></span>
                        <input type="text" name="address_line1"
                               value="<?= e($editAddr['address_line1'] ?? '') ?>" required>
                    </label>
                    <label class="field field--full">
                        <span>Address line 2 <small>optional</small></span>
                        <input type="text" name="address_line2" value="<?= e($editAddr['address_line2'] ?? '') ?>">
                    </label>
                    <label class="field">
                        <span>City <em>*</em></span>
                        <input type="text" name="city" value="<?= e($editAddr['city'] ?? '') ?>" required>
                    </label>
                    <label class="field">
                        <span>State <em>*</em></span>
                        <select name="state" required>
                            <option value="">Select state</option>
                            <?php foreach ($stateOptions as $st): ?>
                                <option value="<?= e($st) ?>" <?= ($editAddr['state'] ?? '') === $st ? 'selected' : '' ?>><?= e($st) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field">
                        <span>PIN code <em>*</em></span>
                        <input type="text" name="pincode" maxlength="6" pattern="\d{6}" inputmode="numeric"
                               value="<?= e($editAddr['pincode'] ?? '') ?>" required>
                    </label>

                    <div class="field field--full field--actions">
                        <button class="btn btn--primary" type="submit">
                            <?= $editAddr ? 'Update address' : 'Save address' ?> <?= icon('arrow-right') ?>
                        </button>
                        <?php if ($editAddr): ?>
                            <a class="btn btn--outline" href="/account/addresses">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

        </div><!-- /.account-main -->
    </div>
</section>

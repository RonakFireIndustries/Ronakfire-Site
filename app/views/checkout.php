<?php
/** @var array $items */
/** @var array $totals */
/** @var int $minOrder */
/** @var array $customer */
/** @var array $form */
/** @var array $errors */
/** @var bool $shipSame */
/** @var array $shipForm */
$stateOptions = ['Andhra Pradesh','Arunachal Pradesh','Assam','Bihar','Chhattisgarh','Goa','Gujarat','Haryana','Himachal Pradesh','Jharkhand','Karnataka','Kerala','Madhya Pradesh','Maharashtra','Manipur','Meghalaya','Mizoram','Nagaland','Odisha','Punjab','Rajasthan','Sikkim','Tamil Nadu','Telangana','Tripura','Uttar Pradesh','Uttarakhand','West Bengal','Andaman and Nicobar Islands','Chandigarh','Dadra and Nagar Haveli and Daman and Diu','Delhi','Jammu and Kashmir','Ladakh','Lakshadweep','Puducherry'];
$errorFor = static fn (string $field): string => isset($errors[$field]) ? '<span class="field-error">' . e($errors[$field]) . '</span>' : '';
?>
<section class="page-hero page-hero--compact">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="/">Home</a><?= icon('chevron-right') ?><a href="/cart">Your order</a><?= icon('chevron-right') ?><span>Delivery</span>
        </nav>
        <div class="page-hero__grid">
            <div>
                <p class="eyebrow"><span></span> Checkout</p>
                <h1>Delivery &amp; invoice details</h1>
                <p class="page-hero__lede">We will confirm stock and freight before dispatch. No payment is collected online.</p>
            </div>
            <div class="page-hero__aside page-hero__aside--steps">
                <span class="is-done">01 Cart</span>
                <span class="is-current">02 Delivery</span>
                <span>03 Review</span>
            </div>
        </div>
    </div>
</section>

<section class="section section--tight">
    <div class="container">
        <?php if (!empty($errors['general'])): ?>
            <div class="flash flash--error"><strong><?= icon('alert') ?> We could not place this order.</strong><p><?= e($errors['general']) ?></p></div>
        <?php endif; ?>
        <?php if (!empty($errors['minimum'])): ?>
            <div class="flash flash--error"><?= icon('alert') ?><p><?= e($errors['minimum']) ?></p></div>
        <?php endif; ?>

        <form class="checkout-layout" id="checkoutForm" action="/checkout" method="post" data-checkout>
            <?= csrf_field() ?>
            <div class="checkout-main">
                <section class="form-card" aria-labelledby="billingHeading">
                    <div class="form-card__head">
                        <span class="form-card__step">01</span>
                        <div>
                            <h2 id="billingHeading">Billing &amp; GST</h2>
                            <p>The invoice will be raised to this entity.</p>
                        </div>
                    </div>
                    <div class="form-grid">
                        <label class="field field--full <?= isset($errors['company_name']) ? 'has-error' : '' ?>">
                            <span>Company / institution <em>*</em></span>
                            <input type="text" name="company_name" value="<?= e($form['company_name']) ?>" autocomplete="organization" required>
                            <?= $errorFor('company_name') ?>
                        </label>
                        <label class="field <?= isset($errors['contact_name']) ? 'has-error' : '' ?>">
                            <span>Contact name <em>*</em></span>
                            <input type="text" name="contact_name" value="<?= e($form['contact_name']) ?>" autocomplete="name" required>
                            <?= $errorFor('contact_name') ?>
                        </label>
                        <label class="field <?= isset($errors['email']) ? 'has-error' : '' ?>">
                            <span>Email <em>*</em></span>
                            <input type="email" name="email" value="<?= e($form['email']) ?>" autocomplete="email" required>
                            <?= $errorFor('email') ?>
                        </label>
                        <label class="field <?= isset($errors['phone']) ? 'has-error' : '' ?>">
                            <span>Phone <em>*</em></span>
                            <input type="tel" name="phone" value="<?= e($form['phone']) ?>" autocomplete="tel" inputmode="tel" required>
                            <?= $errorFor('phone') ?>
                        </label>
                        <label class="field <?= isset($errors['gstin']) ? 'has-error' : '' ?>">
                            <span>GSTIN <small>optional</small></span>
                            <input type="text" name="gstin" value="<?= e($form['gstin']) ?>" maxlength="15" autocomplete="off" placeholder="27ABCDE1234F1Z5">
                            <?= $errorFor('gstin') ?>
                        </label>
                    </div>
                </section>

                <section class="form-card" aria-labelledby="addressHeading">
                    <div class="form-card__head">
                        <span class="form-card__step">02</span>
                        <div>
                            <h2 id="addressHeading">Billing address</h2>
                            <p>Registered address used on the tax invoice.</p>
                        </div>
                    </div>
                    <div class="form-grid">
                        <label class="field field--full <?= isset($errors['address_line1']) ? 'has-error' : '' ?>">
                            <span>Address line 1 <em>*</em></span>
                            <input type="text" name="address_line1" value="<?= e($form['address_line1']) ?>" autocomplete="address-line1" required>
                            <?= $errorFor('address_line1') ?>
                        </label>
                        <label class="field field--full">
                            <span>Address line 2 <small>optional</small></span>
                            <input type="text" name="address_line2" value="<?= e($form['address_line2']) ?>" autocomplete="address-line2">
                        </label>
                        <label class="field <?= isset($errors['city']) ? 'has-error' : '' ?>">
                            <span>City <em>*</em></span>
                            <input type="text" name="city" value="<?= e($form['city']) ?>" autocomplete="address-level2" required>
                            <?= $errorFor('city') ?>
                        </label>
                        <label class="field <?= isset($errors['state']) ? 'has-error' : '' ?>">
                            <span>State <em>*</em></span>
                            <select name="state" autocomplete="address-level1" required>
                                <option value="">Select state</option>
                                <?php foreach ($stateOptions as $state): ?>
                                    <option value="<?= e($state) ?>" <?= $form['state'] === $state ? 'selected' : '' ?>><?= e($state) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?= $errorFor('state') ?>
                        </label>
                        <label class="field <?= isset($errors['pincode']) ? 'has-error' : '' ?>">
                            <span>PIN code <em>*</em></span>
                            <input type="text" name="pincode" value="<?= e($form['pincode']) ?>" maxlength="6" pattern="\d{6}" inputmode="numeric" autocomplete="postal-code" required>
                            <?= $errorFor('pincode') ?>
                        </label>
                    </div>
                </section>

                <section class="form-card" aria-labelledby="deliveryHeading">
                    <div class="form-card__head">
                        <span class="form-card__step">03</span>
                        <div>
                            <h2 id="deliveryHeading">Delivery</h2>
                            <p>Where should the consignment be sent?</p>
                        </div>
                    </div>
                    <label class="check-row">
                        <input type="checkbox" name="ship_same" value="1" data-ship-same <?= $shipSame ? 'checked' : '' ?>>
                        <span><strong>Deliver to the billing address</strong><small>Uncheck if the site address is different.</small></span>
                    </label>

                    <div class="ship-fields" data-ship-fields <?= $shipSame ? 'hidden' : '' ?>>
                        <div class="form-grid">
                            <label class="field field--full">
                                <span>Site / company name</span>
                                <input type="text" name="ship_company_name" value="<?= e($shipForm['company_name'] ?? '') ?>">
                            </label>
                            <label class="field">
                                <span>Site contact</span>
                                <input type="text" name="ship_contact_name" value="<?= e($shipForm['contact_name'] ?? '') ?>">
                            </label>
                            <label class="field">
                                <span>Site phone</span>
                                <input type="tel" name="ship_phone" value="<?= e($shipForm['phone'] ?? '') ?>">
                            </label>
                            <label class="field field--full">
                                <span>Address line 1</span>
                                <input type="text" name="ship_address_line1" value="<?= e($shipForm['address_line1'] ?? '') ?>">
                            </label>
                            <label class="field field--full">
                                <span>Address line 2</span>
                                <input type="text" name="ship_address_line2" value="<?= e($shipForm['address_line2'] ?? '') ?>">
                            </label>
                            <label class="field">
                                <span>City</span>
                                <input type="text" name="ship_city" value="<?= e($shipForm['city'] ?? '') ?>">
                            </label>
                            <label class="field">
                                <span>State</span>
                                <input type="text" name="ship_state" value="<?= e($shipForm['state'] ?? '') ?>">
                            </label>
                            <label class="field">
                                <span>PIN code</span>
                                <input type="text" name="ship_pincode" value="<?= e($shipForm['pincode'] ?? '') ?>" maxlength="6" inputmode="numeric">
                            </label>
                        </div>
                        <?php if (!empty($errors['shipping'])): ?><p class="field-error"><?= e($errors['shipping']) ?></p><?php endif; ?>
                    </div>
                </section>

                <section class="form-card" aria-labelledby="orderHeading">
                    <div class="form-card__head">
                        <span class="form-card__step">04</span>
                        <div>
                            <h2 id="orderHeading">Order references</h2>
                            <p>Optional details for our dispatch team.</p>
                        </div>
                    </div>
                    <div class="form-grid">
                        <label class="field">
                            <span>Purchase order number <small>optional</small></span>
                            <input type="text" name="po_number" value="<?= e($form['po_number']) ?>" placeholder="e.g. PO/2026/0142">
                        </label>
                        <label class="field field--full">
                            <span>Notes for sales <small>optional</small></span>
                            <textarea name="notes" rows="3" placeholder="Site access, delivery instructions, required documentation…"><?= e($form['notes']) ?></textarea>
                        </label>
                    </div>

                    <div class="payment-note">
                        <span class="payment-note__icon"><?= icon('file') ?></span>
                        <div>
                            <strong>Payment on invoice</strong>
                            <p>No card or UPI payment is taken here. Our team will confirm the order, freight and payment terms, then issue the GST invoice.</p>
                        </div>
                    </div>

                    <label class="check-row check-row--terms <?= isset($errors['terms']) ? 'has-error' : '' ?>">
                        <input type="checkbox" name="terms" value="1" required>
                        <span>I confirm the specification and accept the <a href="/refund-policy" target="_blank" rel="noopener">order terms</a>.<em>*</em></span>
                    </label>
                    <?= $errorFor('terms') ?>
                </section>
            </div>

            <aside class="checkout-summary">
                <div class="summary-card">
                    <div class="summary-card__head">
                        <h2>Review order</h2>
                        <a href="/cart">Edit</a>
                    </div>
                    <ul class="checkout-items">
                        <?php foreach ($items as $item): ?>
                            <li>
                                <span class="checkout-items__media">
                                    <?php if (!empty($item['image'])): ?><img src="<?= e($item['image']) ?>" alt=""><?php else: ?><?= icon('package') ?><?php endif; ?>
                                </span>
                                <span class="checkout-items__body">
                                    <strong><?= e($item['name']) ?></strong>
                                    <small><?= (int) $item['qty'] ?> × <?= money((int) $item['unit_price_paise']) ?><?= !empty($item['options']['summary']) ? ' · ' . e(implode(' · ', array_slice($item['options']['summary'], 0, 2))) : '' ?></small>
                                </span>
                                <span class="checkout-items__price"><?= money((int) $item['line_subtotal_paise']) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <dl class="summary-lines">
                        <div><dt>Subtotal (excl. GST)</dt><dd><?= money((int) $totals['subtotal_paise']) ?></dd></div>
                        <div><dt>GST</dt><dd><?= money((int) $totals['gst_paise']) ?></dd></div>
                        <div><dt>Freight</dt><dd>To be confirmed</dd></div>
                        <div class="summary-lines__total"><dt>Total</dt><dd><?= money((int) $totals['total_paise']) ?></dd></div>
                    </dl>
                    <p class="summary-note"><?= icon('truck') ?> <?= e(setting('freight_note', config('store.freight_note'))) ?></p>
                    <button class="btn btn--primary btn--lg btn--block" type="submit">Place order <?= icon('arrow-right') ?></button>
                    <p class="summary-secure"><?= icon('lock') ?> Your price is locked to the signed-in account.</p>
                </div>

                <div class="summary-help">
                    <span><?= icon('shield') ?></span>
                    <div><strong>Ordering for a project?</strong><p>Add the PO number above and we will match the invoice to your procurement records.</p></div>
                </div>
            </aside>
        </form>
    </div>
</section>

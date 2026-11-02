<?php
/** @var array $items */
/** @var array $totals */
/** @var array $customer */
/** @var array $form */
/** @var array $errors */
$errorFor = static fn (string $field): string => isset($errors[$field]) ? '<span class="field-error">' . e($errors[$field]) . '</span>' : '';
?>
<section class="page-hero page-hero--compact">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="/">Home</a><?= icon('chevron-right') ?><a href="/cart">Your order</a><?= icon('chevron-right') ?><span>Request quote</span>
        </nav>
        <div class="page-hero__grid">
            <div>
                <p class="eyebrow"><span></span> Send requirement</p>
                <h1>Request a quotation</h1>
                <p class="page-hero__lede">We will review your configured lines and reply with pricing, stock availability and lead time. No payment is due online.</p>
            </div>
            <div class="page-hero__aside page-hero__aside--steps">
                <span class="is-done">01 Cart</span>
                <span class="is-current">02 Request quote</span>
            </div>
        </div>
    </div>
</section>

<section class="section section--tight">
    <div class="container">
        <?php if (!empty($errors['general'])): ?>
            <div class="flash flash--error"><?= icon('alert') ?><p><?= e($errors['general']) ?></p></div>
        <?php endif; ?>

        <div class="cart-quote-layout">
            <div class="cart-quote-main">
                <form class="quote-form" id="cartQuoteForm" action="/cart/quote" method="post">
                    <?= csrf_field() ?>

                    <section class="form-card">
                        <div class="form-card__head">
                            <span class="form-card__step">01</span>
                            <div>
                                <h2>Your configured lines</h2>
                                <p>Review the items below before sending.</p>
                            </div>
                        </div>
                        <div class="cart-list">
                            <div class="cart-list__head">
                                <span><?= $totals['item_count'] ?> <?= $totals['item_count'] === 1 ? 'line' : 'lines' ?></span>
                                <a href="/cart">Edit order <?= icon('arrow-right', 'flip') ?></a>
                            </div>
                            <?php foreach ($items as $item): ?>
                                <?php $options = $item['options'] ?? []; ?>
                                <article class="cart-item">
                                    <a class="cart-item__media" href="/product/<?= e($item['slug']) ?>">
                                        <?php if (!empty($item['image'])): ?>
                                            <img src="<?= e($item['image']) ?>" alt="<?= e($item['name']) ?>" loading="lazy">
                                        <?php else: ?>
                                            <span class="product-card__placeholder"><?= icon('package') ?></span>
                                        <?php endif; ?>
                                    </a>
                                    <div class="cart-item__body">
                                        <p class="cart-item__meta"><?= e($item['category_name'] ?? '') ?> · <?= e($item['sku']) ?></p>
                                        <h2><a href="/product/<?= e($item['slug']) ?>"><?= e($item['name']) ?></a></h2>
                                        <?php if (!empty($options['summary'])): ?>
                                            <ul class="cart-item__options">
                                                <?php foreach ($options['summary'] as $summaryLine): ?>
                                                    <li><?= e($summaryLine) ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </div>
                                    <div class="cart-item__price">
                                        <strong><?= money((int) $item['line_subtotal_paise']) ?></strong>
                                        <span><?= money((int) $item['unit_price_paise']) ?> · <?= (int) $item['qty'] ?></span>
                                        <small>+ GST <?= money((int) $item['line_gst_paise']) ?></small>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                        <div class="summary-card">
                            <dl class="summary-lines">
                                <div><dt>Subtotal (excl. GST)</dt><dd><?= money((int) $totals['subtotal_paise']) ?></dd></div>
                                <div><dt>GST</dt><dd><?= money((int) $totals['gst_paise']) ?></dd></div>
                                <div class="summary-lines__total"><dt>Total</dt><dd><?= money((int) $totals['total_paise']) ?></dd></div>
                            </dl>
                            <p class="summary-note"><?= icon('truck') ?> Freight is confirmed by our sales team after the quote is reviewed.</p>
                        </div>
                    </section>

                    <section class="form-card">
                        <div class="form-card__head">
                            <span class="form-card__step">02</span>
                            <div>
                                <h2>Billing &amp; contact details</h2>
                                <p>The quotation will be raised to this entity.</p>
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
                            <label class="field field--full">
                                <span>Purchase order number <small>optional</small></span>
                                <input type="text" name="po_number" value="<?= e($form['po_number']) ?>" placeholder="e.g. PO-2025-001">
                            </label>
                            <label class="field field--full">
                                <span>Additional notes <small>optional</small></span>
                                <textarea name="notes" rows="3" placeholder="Delivery site, required-by date, special instructions…"><?= e($form['notes']) ?></textarea>
                            </label>
                        </div>
                    </section>

                    <div class="form-actions">
                        <a class="btn btn--outline" href="/cart"><?= icon('arrow-left') ?> Back to cart</a>
                        <button class="btn btn--primary btn--lg btn--block" type="submit">Send requirement to sales <?= icon('arrow-right') ?></button>
                    </div>
                </form>
            </div>

            <aside class="cart-quote-aside">
                <div class="summary-help">
                    <span><?= icon('phone') ?></span>
                    <div>
                        <strong>Need help before sending?</strong>
                        <p>Call <a href="tel:<?= e(setting('support_phone_href', config('store.support_phone_href'))) ?>"><?= e(setting('support_phone', config('store.support_phone'))) ?></a> or <a href="/quote">request a quote</a>.</p>
                    </div>
                </div>
                <div class="info-card">
                    <span><?= icon('info') ?></span>
                    <div>
                        <strong>What happens next?</strong>
                        <p>Our sales team will review your configuration, confirm stock and freight, and email you a quotation within one working day. Once you confirm, we will set up your customer account with login details to view orders, inventory and documents.</p>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</section>

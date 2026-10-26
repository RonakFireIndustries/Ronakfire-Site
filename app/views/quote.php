<?php
/** @var array|null $product */
/** @var array $form */
/** @var array $errors */
/** @var bool $sent */
$errorFor = static fn (string $field): string => isset($errors[$field]) ? '<span class="field-error">' . e($errors[$field]) . '</span>' : '';
$quoteConfiguration = (string) input('configuration', '');
?>
<section class="quote-page">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="/">Home</a><?= icon('chevron-right') ?><span>Request a quote</span>
        </nav>

        <?php if ($sent): ?>
            <div class="quote-success">
                <span class="quote-success__icon"><?= icon('check') ?></span>
                <div>
                    <p class="eyebrow"><span></span> Received</p>
                    <h1>Your requirement is with our sales team.</h1>
                    <p>We will review the specification and respond within one working day with pricing, availability and lead time. For urgent projects, call <a href="tel:<?= e(setting('support_phone_href', config('store.support_phone_href'))) ?>"><?= e(setting('support_phone', config('store.support_phone'))) ?></a>.</p>
                    <div class="quote-success__actions">
                        <a class="btn btn--primary" href="/catalog">Continue browsing</a>
                        <a class="btn btn--outline" href="/account/enquiries">View your requests</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="quote-layout">
                <div class="quote-intro">
                    <p class="eyebrow"><span></span> Talk to the factory</p>
                    <h1>Send the specification.<br>We’ll price it properly.</h1>
                    <p class="quote-intro__lede">For BOQs, tender schedules, drawings and bespoke fabrication, our sales engineers quote against the actual bill of materials — not a catalogue guess.</p>

                    <ul class="quote-benefits">
                        <li><span><?= icon('file') ?></span><div><strong>Drawings &amp; BOQs welcome</strong><p>Upload not available? Send the details here and we will reply with an email address for files.</p></div></li>
                        <li><span><?= icon('shield') ?></span><div><strong>Compliant specifications</strong><p>We confirm the applicable IS standard, test certificate and approval path.</p></div></li>
                        <li><span><?= icon('truck') ?></span><div><strong>Project lead times</strong><p>Factory capacity, batch dates and dispatch are confirmed in the quotation.</p></div></li>
                    </ul>

                    <div class="quote-contact">
                        <div><small>Sales helpline</small><a href="tel:<?= e(setting('support_phone_href', config('store.support_phone_href'))) ?>"><?= e(setting('support_phone', config('store.support_phone'))) ?></a></div>
                        <div><small>Email</small><a href="mailto:<?= e(setting('support_email', config('store.support_email'))) ?>"><?= e(setting('support_email', config('store.support_email'))) ?></a></div>
                    </div>
                </div>

                <div class="quote-form-card">
                    <div class="quote-form-card__head">
                        <h2>Request a quotation</h2>
                        <p>Fields marked * are required.</p>
                    </div>

                    <?php if (!empty($errors['general'])): ?>
                        <div class="flash flash--error"><?= icon('alert') ?><p><?= e($errors['general']) ?></p></div>
                    <?php endif; ?>

                    <?php if ($product): ?>
                        <div class="quote-product">
                            <?php if (!empty($product['image'])): ?><img src="<?= e($product['image']) ?>" alt=""><?php endif; ?>
                            <div>
                                <small>Quoting for</small>
                                <strong><?= e($product['name']) ?></strong>
                                <span><?= e($product['sku']) ?></span>
                            </div>
                            <a href="/product/<?= e($product['slug']) ?>">Change</a>
                        </div>
                    <?php endif; ?>

                    <?php if ($quoteConfiguration !== ''): ?>
                        <div class="quote-configuration">
                            <span><?= icon('spark') ?> Configuration</span>
                            <p><?= e($quoteConfiguration) ?></p>
                        </div>
                    <?php endif; ?>

                    <form action="/quote" method="post">
                        <?= csrf_field() ?>
                        <?php if ($product): ?><input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>"><?php endif; ?>
                        <input type="hidden" name="configuration" value="<?= e($quoteConfiguration) ?>" data-quote-configuration>

                        <div class="form-grid">
                            <label class="field <?= isset($errors['name']) ? 'has-error' : '' ?>">
                                <span>Your name <em>*</em></span>
                                <input type="text" name="name" value="<?= e($form['name']) ?>" autocomplete="name" required>
                                <?= $errorFor('name') ?>
                            </label>
                            <label class="field">
                                <span>Company</span>
                                <input type="text" name="company" value="<?= e($form['company']) ?>" autocomplete="organization">
                            </label>
                            <label class="field <?= isset($errors['email']) ? 'has-error' : '' ?>">
                                <span>Email <em>*</em></span>
                                <input type="email" name="email" value="<?= e($form['email']) ?>" autocomplete="email" required>
                                <?= $errorFor('email') ?>
                            </label>
                            <label class="field <?= isset($errors['phone']) ? 'has-error' : '' ?>">
                                <span>Phone <em>*</em></span>
                                <input type="tel" name="phone" value="<?= e($form['phone']) ?>" autocomplete="tel" required>
                                <?= $errorFor('phone') ?>
                            </label>
                            <label class="field <?= isset($errors['gstin']) ? 'has-error' : '' ?>">
                                <span>GSTIN <small>optional</small></span>
                                <input type="text" name="gstin" value="<?= e($form['gstin']) ?>" maxlength="15">
                                <?= $errorFor('gstin') ?>
                            </label>
                            <label class="field">
                                <span>Quantity</span>
                                <input type="number" name="qty" value="<?= e($form['qty']) ?>" min="1" inputmode="numeric">
                            </label>
                            <label class="field field--full <?= isset($errors['message']) ? 'has-error' : '' ?>">
                                <span>Requirement / specification <em>*</em></span>
                                <textarea name="message" rows="5" placeholder="Product, sizes, finish, standard, project location, required-by date…"><?= e($form['message']) ?></textarea>
                                <?= $errorFor('message') ?>
                            </label>
                            <label class="quote-honeypot" aria-hidden="true">
                                <span>Website</span>
                                <input type="text" name="website" tabindex="-1" autocomplete="off">
                            </label>
                        </div>

                        <button class="btn btn--primary btn--lg btn--block" type="submit">Send requirement <?= icon('arrow-right') ?></button>
                        <p class="quote-form-card__note"><?= icon('lock') ?> Your details are used only to respond to this enquiry.</p>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

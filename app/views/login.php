<?php
/** @var string $return */
/** @var bool $portalError */
/** @var bool $portalReady */
?>
<section class="login-page">
    <div class="container login-layout">
        <div class="login-story">
            <p class="eyebrow eyebrow--light"><span></span> Ronak Fire trade</p>
            <h1>One account.<br>Your price, your orders.</h1>
            <p>Sign in with your Ronak Fire customer portal account to unlock B2B pricing, configure products and track orders through dispatch.</p>

            <ul class="login-benefits">
                <li><span><?= icon('lock') ?></span><div><strong>Your negotiated price</strong><p>Account-specific tiers and discounts are applied automatically.</p></div></li>
                <li><span><?= icon('package') ?></span><div><strong>Configured order history</strong><p>Every specification, quantity and invoice in one place.</p></div></li>
                <li><span><?= icon('file') ?></span><div><strong>Quote requests</strong><p>Send BOQs and drawings directly to the sales desk.</p></div></li>
            </ul>

            <div class="login-story__foot">
                <span>Not registered?</span>
                <a href="/contact">Apply for a trade account <?= icon('arrow-right') ?></a>
            </div>
        </div>

        <div class="login-card">
            <div class="login-card__head">
                <span class="login-card__mark"><?= icon('user') ?></span>
                <h2>Customer sign in</h2>
                <p>Your account is managed by the Ronak Fire portal.</p>
            </div>

            <?php if ($portalError): ?>
                <div class="flash flash--error"><?= icon('alert') ?><p>We could not complete the portal sign-in. Please try again.</p></div>
            <?php endif; ?>

            <?php if ($portalReady): ?>
                <a class="btn btn--primary btn--lg btn--block" href="<?= e(portal_login_url($return)) ?>">
                    Continue to customer portal <?= icon('arrow-right') ?>
                </a>
                <p class="login-card__hint">You will return to the store automatically after signing in.</p>
            <?php endif; ?>

            <?php if (dev_login_enabled()): ?>
                <?php if ($portalReady): ?><div class="login-divider"><span>or use the local demo login</span></div><?php endif; ?>
                <div class="demo-banner"><?= icon('spark') ?><span><strong>Local development</strong> — this form is disabled when the store runs in production.</span></div>
                <form action="/login/dev" method="post" class="login-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="return" value="<?= e($return) ?>">
                    <label class="field">
                        <span>Email</span>
                        <input type="email" name="email" value="buyer@example.com" autocomplete="email" required>
                    </label>
                    <label class="field">
                        <span>Contact name</span>
                        <input type="text" name="name" value="Demo Buyer" autocomplete="name">
                    </label>
                    <label class="field">
                        <span>Company</span>
                        <input type="text" name="company" value="Demo Fire Systems Pvt. Ltd." autocomplete="organization">
                    </label>
                    <button class="btn btn--dark btn--block" type="submit">Sign in to demo account</button>
                </form>
            <?php elseif (!$portalReady): ?>
                <div class="login-card__empty">
                    <?= icon('phone') ?>
                    <h3>Trade accounts are activated by our sales team.</h3>
                    <p>Call <a href="tel:<?= e(setting('support_phone_href', config('store.support_phone_href'))) ?>"><?= e(setting('support_phone', config('store.support_phone'))) ?></a> or <a href="/contact">contact us</a> to register your company.</p>
                </div>
            <?php endif; ?>

            <div class="login-card__foot">
                <?= icon('shield') ?>
                <p>Passwords are never stored by the store. Authentication is handled securely on <strong>rfipl.ronakfire.com</strong>.</p>
            </div>
        </div>
    </div>
</section>

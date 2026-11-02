<?php
/** @var string $return */
/** @var bool $portalError */
/** @var bool $portalReady */
$supportEmail = setting('support_email', config('store.support_email'));
?>
<section class="login-page">
    <div class="container login-layout">
        <div class="login-story">
            <p class="eyebrow eyebrow--light"><span></span> Ronak Fire trade</p>
            <h1>One account.<br>Your price, your orders.</h1>
            <p>Sign in to unlock B2B pricing, configure products and track orders through dispatch.</p>

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
                <p>Use the email and password provided by our sales team.</p>
            </div>

            <?php if ($portalError): ?>
                <div class="flash flash--error"><?= icon('alert') ?><p>We could not complete the portal sign-in. Please try again.</p></div>
            <?php endif; ?>

            <form class="login-form" action="/login/password" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="return" value="<?= e($return) ?>">

                <label class="field">
                    <span>Email address</span>
                    <input type="email" name="email" autocomplete="username" maxlength="191" required autofocus>
                </label>

                <label class="field">
                    <span>Password</span>
                    <input type="password" name="password" autocomplete="current-password" required>
                </label>

                <button class="btn btn--primary btn--lg btn--block" type="submit">
                    Sign in <?= icon('arrow-right') ?>
                </button>
            </form>

            <?php if ($portalReady): ?>
                <div class="login-divider">or</div>
                <a class="btn btn--outline btn--lg btn--block" href="<?= e(portal_login_url($return)) ?>">
                    Continue to customer portal <?= icon('arrow-right') ?>
                </a>
                <p class="login-card__hint">You will return to the store automatically after signing in.</p>
            <?php endif; ?>

            <p class="login-card__hint">No account yet? Trade accounts are activated by our sales team — <a href="/contact">contact us</a>.</p>

            <div class="login-card__foot">
                <?= icon('shield') ?>
                <p>Your password is stored as a one-way hash. If you cannot sign in, contact <a href="mailto:<?= e($supportEmail) ?>"><?= e($supportEmail) ?></a> for a reset.</p>
            </div>
        </div>
    </div>
</section>

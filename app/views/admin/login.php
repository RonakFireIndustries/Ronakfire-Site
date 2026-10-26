<?php
/** @var string $error */
/** @var string $email */
?>
<div class="admin-login">
    <span class="admin-login__mark"><?= icon('lock') ?></span>
    <p class="admin-eyebrow">Administrator</p>
    <h2>Sign in to the store</h2>
    <p class="admin-login__lede">Use your Ronak Fire administrator credentials.</p>

    <?php if ($error): ?>
        <div class="admin-alert admin-alert--error"><?= icon('alert') ?><span><?= e($error) ?></span></div>
    <?php endif; ?>

    <form action="/admin/login" method="post" class="admin-login__form">
        <?= csrf_field() ?>
        <label class="admin-field">
            <span>Email</span>
            <input type="email" name="email" value="<?= e($email) ?>" autocomplete="username" required autofocus>
        </label>
        <label class="admin-field">
            <span>Password</span>
            <input type="password" name="password" autocomplete="current-password" required>
        </label>
        <button class="admin-btn admin-btn--primary admin-btn--block" type="submit">Sign in <?= icon('arrow-right') ?></button>
    </form>

    <?php if (is_local()): ?>
        <div class="admin-login__hint">
            <strong>Local demo credentials</strong>
            <span>admin@ronakfire.com · Ronak@2026!</span>
            <small>Change this before deploying to production.</small>
        </div>
    <?php endif; ?>
</div>

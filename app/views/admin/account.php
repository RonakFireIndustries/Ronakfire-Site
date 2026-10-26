<?php
/** @var array|null $admin */
?>
<div class="admin-page-head">
    <div>
        <p class="admin-eyebrow">System</p>
        <h2>Your admin account</h2>
        <p>Credentials for this store administration panel.</p>
    </div>
</div>

<div class="admin-grid admin-grid--two">
    <section class="admin-panel">
        <div class="admin-panel__head"><div><h2>Profile</h2><p>Read-only details for the signed-in administrator.</p></div></div>
        <div class="admin-panel__body">
            <dl class="admin-meta-list">
                <div><dt>Name</dt><dd><?= e($admin['name'] ?? '') ?></dd></div>
                <div><dt>Email</dt><dd><?= e($admin['email'] ?? '') ?></dd></div>
                <div><dt>Role</dt><dd><span class="admin-tag"><?= e(ucfirst((string) ($admin['role'] ?? 'admin'))) ?></span></dd></div>
                <div><dt>Last login</dt><dd><?= e(format_datetime($admin['last_login_at'] ?? null)) ?></dd></div>
                <div><dt>Created</dt><dd><?= e(format_datetime($admin['created_at'] ?? null)) ?></dd></div>
            </dl>
            <div class="admin-hint">
                <?= icon('shield') ?>
                <p>Admin sessions are separate from customer sessions. Signing out of the admin panel does not sign you out of the storefront.</p>
            </div>
        </div>
    </section>

    <section class="admin-panel admin-panel--form">
        <div class="admin-panel__head"><div><h2>Change password</h2><p>Use at least 10 characters and avoid reusing the seeded password.</p></div></div>
        <form action="/admin/account/password" method="post" class="admin-form-body">
            <?= csrf_field() ?>
            <label class="admin-field">
                <span>Current password</span>
                <input type="password" name="current_password" autocomplete="current-password" required>
            </label>
            <label class="admin-field">
                <span>New password</span>
                <input type="password" name="new_password" minlength="10" autocomplete="new-password" required>
            </label>
            <label class="admin-field">
                <span>Confirm new password</span>
                <input type="password" name="confirm_password" minlength="10" autocomplete="new-password" required>
            </label>
            <button class="admin-btn admin-btn--primary admin-btn--block" type="submit"><?= icon('lock') ?> Update password</button>
        </form>
    </section>
</div>

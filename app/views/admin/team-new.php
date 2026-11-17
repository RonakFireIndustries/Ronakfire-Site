<?php ?>
<div class="admin-page-head">
    <div>
        <p class="admin-eyebrow"><a href="/admin/team">Sales team</a> / New</p>
        <h2>Add sales user</h2>
        <p>Create a login for a salesperson. They will be able to sign in and manage the customers assigned to them.</p>
    </div>
    <div class="admin-page-head__actions">
        <a class="admin-btn admin-btn--ghost" href="/admin/team"><?= icon('arrow-left') ?> Back</a>
    </div>
</div>

<section class="admin-panel">
    <form action="/admin/team/create" method="post" class="admin-form-body">
        <?= csrf_field() ?>
        <label class="admin-field"><span>Name</span><input type="text" name="name" required></label>
        <label class="admin-field"><span>Email</span><input type="email" name="email" required></label>
        <label class="admin-field">
            <span>Role</span>
            <select name="role">
                <option value="sales">Sales</option>
                <option value="admin">Admin</option>
            </select>
        </label>
        <label class="admin-field"><span>Password</span><input type="password" name="password" minlength="10" required></label>
        <label class="admin-field"><span>Confirm password</span><input type="password" name="confirm_password" minlength="10" required></label>
        <div class="admin-form-actions">
            <a class="admin-btn admin-btn--ghost" href="/admin/team">Cancel</a>
            <button class="admin-btn admin-btn--primary" type="submit">Create user</button>
        </div>
    </form>
</section>
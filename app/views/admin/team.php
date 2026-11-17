<?php
/** @var array $users */
?>
<div class="admin-page-head">
    <div>
        <p class="admin-eyebrow">Sales</p>
        <h2>Sales team</h2>
        <p>Sales users who log into this panel and own customer accounts. New customer accounts are assigned to whoever creates them.</p>
    </div>
    <div class="admin-page-head__actions">
        <a class="admin-btn" href="/admin/team/new"><?= icon('user-plus') ?> Add sales user</a>
        <a class="admin-btn admin-btn--ghost" href="/admin/sales-report"><?= icon('chart') ?> Ownership report</a>
    </div>
</div>

<section class="admin-panel">
    <?php if ($users): ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Name</th><th>Email</th><th>Role</th><th class="is-right">Customers</th><th class="is-right">Active</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= e($user['name']) ?></td>
                        <td><?= e($user['email']) ?></td>
                        <td><span class="admin-tag"><?= e(ucfirst((string) $user['role'])) ?></span></td>
                        <td class="is-right"><?= (int) $user['customer_count'] ?></td>
                        <td class="is-right"><?= (int) $user['active_customer_count'] ?></td>
                        <td><?php if ((int) $user['is_active'] === 1): ?><span class="admin-status admin-status--visible">Active</span><?php else: ?><span class="admin-status admin-status--hidden">Blocked</span><?php endif; ?></td>
                        <td class="is-right">
                            <form action="/admin/team/<?= (int) $user['id'] ?>/role" method="post" class="admin-inline-form" style="display:inline-flex;gap:.5rem;align-items:center;">
                                <?= csrf_field() ?>
                                <select name="role" class="admin-select-sm">
                                    <option value="sales" <?= $user['role'] === 'sales' ? 'selected' : '' ?>>Sales</option>
                                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                </select>
                                <button class="admin-btn admin-btn--ghost" type="submit">Save</button>
                            </form>
                            <form action="/admin/team/<?= (int) $user['id'] ?>/toggle" method="post" style="display:inline;">
                                <?= csrf_field() ?>
                                <button class="admin-btn admin-btn--ghost" type="submit"><?= (int) $user['is_active'] === 1 ? 'Deactivate' : 'Activate' ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="admin-empty"><?= icon('users') ?><p>No admin users yet.</p></div>
    <?php endif; ?>
</section>
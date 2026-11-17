<?php
/** @var array $summary */
/** @var int $unassigned */
?>
<div class="admin-page-head">
    <div>
        <p class="admin-eyebrow">Sales</p>
        <h2>Sales ownership report</h2>
        <p>Customers, orders and enquiries grouped by the sales user who owns them.</p>
    </div>
    <div class="admin-page-head__actions">
        <a class="admin-btn" href="/admin/team"><?= icon('users') ?> Sales team</a>
    </div>
</div>

<section class="admin-panel">
    <?php if ($summary): ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Sales user</th><th>Role</th><th class="is-right">Customers</th><th class="is-right">Active</th><th class="is-right">Orders</th><th class="is-right">Order value</th><th class="is-right">Enquiries</th></tr></thead>
                <tbody>
                <?php foreach ($summary as $row): ?>
                    <tr>
                        <td>
                            <?= e($row['name']) ?>
                            <small><?= e($row['email']) ?></small>
                        </td>
                        <td><span class="admin-tag"><?= e(ucfirst((string) $row['role'])) ?></span></td>
                        <td class="is-right"><?= (int) $row['customer_count'] ?></td>
                        <td class="is-right"><?= (int) $row['active_customer_count'] ?></td>
                        <td class="is-right"><?= (int) $row['order_count'] ?></td>
                        <td class="is-right"><?= money((int) $row['order_value']) ?></td>
                        <td class="is-right"><?= (int) $row['enquiry_count'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2"><strong>Unassigned customers</strong></td>
                        <td class="is-right"><strong><?= (int) $unassigned ?></strong></td>
                        <td colspan="4"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    <?php else: ?>
        <div class="admin-empty"><?= icon('chart') ?><p>No sales users yet.</p></div>
    <?php endif; ?>
</section>
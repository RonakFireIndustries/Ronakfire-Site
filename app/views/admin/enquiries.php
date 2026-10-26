<?php
/** @var array $enquiries */
/** @var string $status */
/** @var string $q */
/** @var array $pager */
/** @var array $statusCounts */
$statuses = ['new' => 'New', 'contacted' => 'Contacted', 'quoted' => 'Quoted', 'closed' => 'Closed'];
$totalAll = array_sum($statusCounts);
$statusUrl = static function (string $value) use ($q): string {
    $query = [];
    if ($value !== '') {
        $query['status'] = $value;
    }
    if ($q !== '') {
        $query['q'] = $q;
    }
    return '/admin/enquiries' . ($query ? '?' . http_build_query($query) : '');
};
?>
<div class="admin-page-head">
    <div>
        <p class="admin-eyebrow">Sales</p>
        <h2>Quote requests</h2>
        <p>Requirements, BOQs and drawings submitted from the storefront.</p>
    </div>
</div>

<nav class="admin-tabs" aria-label="Quote status">
    <a class="admin-tabs__item <?= $status === '' ? 'is-active' : '' ?>" href="<?= e($statusUrl('')) ?>">All <span><?= $totalAll ?></span></a>
    <?php foreach ($statuses as $value => $label): ?>
        <a class="admin-tabs__item <?= $status === $value ? 'is-active' : '' ?>" href="<?= e($statusUrl($value)) ?>"><?= e($label) ?> <span><?= (int) ($statusCounts[$value] ?? 0) ?></span></a>
    <?php endforeach; ?>
</nav>

<form class="admin-filters" action="/admin/enquiries" method="get">
    <?php if ($status !== ''): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
    <label class="admin-search">
        <span class="sr-only">Search quote requests</span>
        <?= icon('search') ?>
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Name, company, email or product…">
    </label>
    <button class="admin-btn" type="submit">Search</button>
    <?php if ($q !== '' || $status !== ''): ?><a class="admin-btn admin-btn--ghost" href="/admin/enquiries">Clear</a><?php endif; ?>
</form>

<section class="admin-panel">
    <?php if ($enquiries): ?>
        <div class="admin-table-wrap">
            <table class="admin-table admin-table--enquiries">
                <thead><tr><th>Requirement</th><th>Contact</th><th>Received</th><th class="is-right">Qty</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($enquiries as $enquiry): ?>
                    <tr>
                        <td>
                            <a class="admin-table__link" href="/admin/enquiries/<?= (int) $enquiry['id'] ?>"><?= e($enquiry['product_name'] ?: 'General requirement') ?></a>
                            <small><?= e($enquiry['company'] ?: 'No company') ?></small>
                        </td>
                        <td>
                            <?= e($enquiry['name']) ?>
                            <small><?= e($enquiry['email']) ?><?= $enquiry['phone'] ? ' · ' . e($enquiry['phone']) : '' ?></small>
                        </td>
                        <td><?= e(format_date($enquiry['created_at'])) ?><small><?= e(format_date($enquiry['created_at'], 'H:i')) ?></small></td>
                        <td class="is-right"><?= (int) $enquiry['qty'] ?></td>
                        <td><span class="admin-status admin-status--<?= e($enquiry['status']) ?>"><?= e(ucfirst($enquiry['status'])) ?></span></td>
                        <td class="is-right"><a class="admin-table__action" href="/admin/enquiries/<?= (int) $enquiry['id'] ?>"><?= icon('chevron-right') ?></a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php partial('admin/partials/pagination', ['pager' => $pager, 'baseUrl' => '/admin/enquiries']); ?>
    <?php else: ?>
        <div class="admin-empty">
            <?= icon('clipboard') ?>
            <p><?= $q !== '' || $status !== '' ? 'No quote requests match this filter.' : 'No quote requests yet.' ?></p>
            <?php if ($q !== '' || $status !== ''): ?><a class="admin-btn" href="/admin/enquiries">Clear filters</a><?php endif; ?>
        </div>
    <?php endif; ?>
</section>

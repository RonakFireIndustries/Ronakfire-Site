<?php
if (($pager['pages'] ?? 1) <= 1) {
    return;
}
$query = $_GET;
unset($query['page'], $query['__route']);
$pageUrl = static function (int $number) use ($baseUrl, $query): string {
    $query['page'] = $number;
    return $baseUrl . '?' . http_build_query($query);
};
$windowStart = max(1, $pager['page'] - 2);
$windowEnd = min($pager['pages'], $pager['page'] + 2);
?>
<nav class="admin-pagination" aria-label="Pagination">
    <span><?= (int) $pager['total'] ?> records · page <?= (int) $pager['page'] ?> of <?= (int) $pager['pages'] ?></span>
    <div>
        <a class="<?= $pager['page'] <= 1 ? 'is-disabled' : '' ?>" href="<?= $pager['page'] <= 1 ? '#' : e($pageUrl($pager['page'] - 1)) ?>">Prev</a>
        <?php for ($i = $windowStart; $i <= $windowEnd; $i++): ?>
            <?php if ($i === $pager['page']): ?><span class="is-current"><?= $i ?></span><?php else: ?><a href="<?= e($pageUrl($i)) ?>"><?= $i ?></a><?php endif; ?>
        <?php endfor; ?>
        <a class="<?= $pager['page'] >= $pager['pages'] ? 'is-disabled' : '' ?>" href="<?= $pager['page'] >= $pager['pages'] ? '#' : e($pageUrl($pager['page'] + 1)) ?>">Next</a>
    </div>
</nav>

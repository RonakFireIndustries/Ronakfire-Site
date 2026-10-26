<?php
if (($pages ?? 1) <= 1) {
    return;
}
$baseUrl = $baseUrl ?? current_path();
$query = $_GET;
unset($query['page'], $query['__route']);
$pageUrl = static function (int $number) use ($baseUrl, $query): string {
    $query['page'] = $number;
    return $baseUrl . '?' . http_build_query($query);
};
$windowStart = max(1, $page - 2);
$windowEnd = min($pages, $page + 2);
?>
<nav class="pagination" aria-label="Pagination">
    <a class="pagination__arrow <?= $page <= 1 ? 'is-disabled' : '' ?>" href="<?= $page <= 1 ? '#' : e($pageUrl($page - 1)) ?>" <?= $page <= 1 ? 'aria-disabled="true" tabindex="-1"' : '' ?>>
        <?= icon('chevron-right', 'pagination__prev') ?><span>Previous</span>
    </a>
    <div class="pagination__pages">
        <?php if ($windowStart > 1): ?>
            <a href="<?= e($pageUrl(1)) ?>">1</a>
            <?php if ($windowStart > 2): ?><span>…</span><?php endif; ?>
        <?php endif; ?>
        <?php for ($i = $windowStart; $i <= $windowEnd; $i++): ?>
            <?php if ($i === $page): ?>
                <span class="is-current" aria-current="page"><?= $i ?></span>
            <?php else: ?>
                <a href="<?= e($pageUrl($i)) ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        <?php if ($windowEnd < $pages): ?>
            <?php if ($windowEnd < $pages - 1): ?><span>…</span><?php endif; ?>
            <a href="<?= e($pageUrl($pages)) ?>"><?= $pages ?></a>
        <?php endif; ?>
    </div>
    <a class="pagination__arrow <?= $page >= $pages ? 'is-disabled' : '' ?>" href="<?= $page >= $pages ? '#' : e($pageUrl($page + 1)) ?>" <?= $page >= $pages ? 'aria-disabled="true" tabindex="-1"' : '' ?>>
        <span>Next</span><?= icon('chevron-right') ?>
    </a>
</nav>

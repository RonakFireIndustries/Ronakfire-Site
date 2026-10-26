<?php
/** @var array $product */
$cardCustomer = current_customer();
$cardPrice = App\Catalog::fromPrice($product, (int) ($cardCustomer['discount_bp'] ?? 0));
$cardBadges = App\Catalog::badges($product);
$cardUrl = '/product/' . $product['slug'];
?>
<article class="product-card">
    <a class="product-card__media" href="<?= e($cardUrl) ?>" aria-label="View <?= e($product['name']) ?>">
        <?php if (!empty($product['image'])): ?>
            <img src="<?= e($product['image']) ?>" alt="<?= e($product['name']) ?>" loading="lazy">
        <?php else: ?>
            <span class="product-card__placeholder"><?= icon('package') ?></span>
        <?php endif; ?>
        <span class="product-card__type">
            <?php if (($product['product_type'] ?? '') === 'bundle'): ?>Kit
            <?php elseif (($product['product_type'] ?? '') === 'configurable'): ?>Configurable
            <?php else: ?>In stock
            <?php endif; ?>
        </span>
    </a>
    <div class="product-card__body">
        <p class="product-card__category"><?= e($product['category_name'] ?? '') ?></p>
        <h3><a href="<?= e($cardUrl) ?>"><?= e($product['name']) ?></a></h3>
        <p class="product-card__desc"><?= e($product['short_description'] ?? '') ?></p>
        <?php if ($cardBadges): ?>
            <ul class="badge-list">
                <?php foreach (array_slice($cardBadges, 0, 2) as $badge): ?>
                    <li><?= e($badge) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <div class="product-card__footer">
            <div class="product-card__price">
                <?php if (is_logged_in()): ?>
                    <small>From</small>
                    <strong><?= money($cardPrice) ?></strong>
                    <span>+ <?= gst_label((int) $product['gst_rate_bp']) ?> GST</span>
                <?php else: ?>
                    <small><?= icon('lock') ?> Trade price</small>
                    <a href="/login?return=<?= urlencode($cardUrl) ?>">Sign in to view</a>
                <?php endif; ?>
            </div>
            <a class="product-card__action" href="<?= e($cardUrl) ?>" aria-label="View <?= e($product['name']) ?>"><?= icon('arrow-right') ?></a>
        </div>
    </div>
</article>

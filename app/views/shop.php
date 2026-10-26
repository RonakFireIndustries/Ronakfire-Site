<?php
/** @var array $categories */
/** @var array $featured */
/** @var array $counts */
$heroProduct = $featured[0] ?? null;
$totalProducts = array_sum($counts);
?>
<section class="hero">
    <div class="hero__grid-lines" aria-hidden="true"></div>
    <div class="container hero__grid">
        <div class="hero__copy">
            <p class="eyebrow eyebrow--light"><span></span> Fire protection, engineered in India</p>
            <h1>Specified for the project.<br><span>Built for the worst day.</span></h1>
            <p class="hero__lede">Configure extinguishers, hose reels, cabinets and hydrant equipment to your exact schedule. Manufacturer-direct B2B pricing, GST invoices and dispatch across India.</p>
            <div class="hero__actions">
                <a class="btn btn--primary btn--lg" href="/catalog">Browse the catalog <?= icon('arrow-right') ?></a>
                <a class="btn btn--ghost btn--lg" href="/quote">Send a specification</a>
            </div>
            <dl class="hero__stats">
                <div><dt>25+</dt><dd>Years manufacturing</dd></div>
                <div><dt><?= (int) $totalProducts ?></dt><dd>Engineered lines</dd></div>
                <div><dt>IS / BIS</dt><dd>Tested products</dd></div>
                <div><dt>Pan-India</dt><dd>Project dispatch</dd></div>
            </dl>
        </div>

        <div class="hero__visual" aria-hidden="true">
            <div class="hero__panel">
                <?php if ($heroProduct && !empty($heroProduct['image'])): ?>
                    <img class="hero__product" src="<?= e($heroProduct['image']) ?>" alt="">
                <?php endif; ?>
                <span class="hero__panel-tag">IS 15683:2018</span>
                <span class="hero__panel-caption"><?= e($heroProduct['name'] ?? 'Fire extinguishers') ?></span>
            </div>
            <div class="hero__float hero__float--spec">
                <span class="hero__float-icon"><?= icon('shield') ?></span>
                <div><strong>BIS approved</strong><small>Batch tested &amp; traceable</small></div>
            </div>
            <div class="hero__float hero__float--price">
                <small>Trade pricing</small>
                <strong><?= is_logged_in() ? 'Your account rates' : 'Unlocked on login' ?></strong>
                <span>Dealer · Contractor · Institution</span>
            </div>
            <div class="hero__rule">
                <span>01</span><span>Specify</span><span>02</span><span>Configure</span><span>03</span><span>Order</span>
            </div>
        </div>
    </div>
</section>

<section class="trust-strip">
    <div class="container trust-strip__grid">
        <div class="trust-item"><?= icon('package') ?><div><strong>Manufacturer direct</strong><span>No distributor markup</span></div></div>
        <div class="trust-item"><?= icon('shield') ?><div><strong>Tested to Indian standards</strong><span>IS / BIS where applicable</span></div></div>
        <div class="trust-item"><?= icon('file') ?><div><strong>GST invoice on every order</strong><span>HSN codes and tax breakup</span></div></div>
        <div class="trust-item"><?= icon('truck') ?><div><strong>Project dispatch</strong><span>Palghar factory to site</span></div></div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <div>
                <p class="eyebrow"><span></span> The range</p>
                <h2>Nine categories. One accountable manufacturer.</h2>
            </div>
            <a class="text-link" href="/catalog">View all products <?= icon('arrow-right') ?></a>
        </div>
        <div class="category-grid">
            <?php foreach ($categories as $index => $category): ?>
                <a class="category-card <?= $index === 0 ? 'category-card--feature' : '' ?>" href="/catalog/<?= e($category['slug']) ?>">
                    <span class="category-card__media">
                        <?php if (!empty($category['image'])): ?>
                            <img src="<?= e($category['image']) ?>" alt="" loading="lazy">
                        <?php endif; ?>
                    </span>
                    <span class="category-card__body">
                        <span class="category-card__meta"><span><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span><span><?= (int) ($counts[$category['slug']] ?? 0) ?> lines</span></span>
                        <strong><?= e($category['name']) ?></strong>
                        <small><?= e($category['tagline'] ?? '') ?></small>
                        <span class="category-card__cta">Shop category <?= icon('arrow-right') ?></span>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section--tint">
    <div class="container">
        <div class="section-head">
            <div>
                <p class="eyebrow"><span></span> Most specified</p>
                <h2>Ready to configure, ready to ship.</h2>
                <p class="section-head__lede">Select capacity, finish or dimensions. The price updates as you build, and your configuration travels with the order.</p>
            </div>
            <a class="text-link" href="/catalog?configurable=1">All configurable lines <?= icon('arrow-right') ?></a>
        </div>
        <div class="product-grid">
            <?php foreach ($featured as $product): ?>
                <?php partial('product-card', ['product' => $product]); ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="configure-band">
    <div class="container configure-band__grid">
        <div class="configure-band__copy">
            <p class="eyebrow eyebrow--light"><span></span> Made to measure</p>
            <h2>Your drawing. Our factory. One predictable price.</h2>
            <p>Hose boxes and fire shaft doors are built to your opening size, finish and access requirements. Enter the dimensions and the configurator calculates the panel area and rate instantly.</p>
            <a class="btn btn--primary" href="/catalog/hose-boxes-and-fire-shaft-door">Configure a cabinet <?= icon('arrow-right') ?></a>
        </div>
        <ol class="configure-steps">
            <li><span>01</span><div><strong>Choose the product family</strong><p>Start from a tested platform, not a blank sheet.</p></div></li>
            <li><span>02</span><div><strong>Set the specification</strong><p>Capacity, material, finish, dimensions and quantity.</p></div></li>
            <li><span>03</span><div><strong>Review and place the order</strong><p>Your account price, GST and delivery details in one place.</p></div></li>
        </ol>
    </div>
</section>

<section class="section">
    <div class="container quote-cta">
        <div>
            <p class="eyebrow"><span></span> For consultants &amp; contractors</p>
            <h2>Have a BOQ, drawing or a deadline?</h2>
            <p>Send the specification and our sales engineers will come back with availability, lead time and a project quotation.</p>
        </div>
        <div class="quote-cta__actions">
            <a class="btn btn--dark btn--lg" href="/quote">Request a quote <?= icon('arrow-right') ?></a>
            <a class="quote-cta__phone" href="tel:<?= e(setting('support_phone_href', config('store.support_phone_href'))) ?>">
                <?= icon('phone') ?>
                <span><small>Or call sales</small><strong><?= e(setting('support_phone', config('store.support_phone'))) ?></strong></span>
            </a>
        </div>
    </div>
</section>

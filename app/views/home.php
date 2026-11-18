<?php
/** @var array $categories */
/** @var array $counts */
/** @var array $featured */
/** @var int $totalProducts */
$phone = setting('support_phone', config('store.support_phone'));
$phoneHref = setting('support_phone_href', config('store.support_phone_href'));
?>
<section class="hero hero--video">
    <div class="hero__backdrop" aria-hidden="true">
        <video class="hero__video" autoplay loop muted playsinline preload="metadata"
            poster="/images/banner/main-banner.jpg">
            <source src="/images/banner/banner-video.mp4" type="video/mp4">
        </video>
    </div>
    <div class="container hero__grid hero__grid--single">
        <div class="hero__copy">
            <p class="eyebrow eyebrow--light"><span></span> Manufacturer-direct fire protection</p>
            <h1>Specified for the project.<br><span>Built for the worst day.</span></h1>
            <p class="hero__lede">Fire extinguishers, hose reel drums, hydrants, sprinklers and made-to-measure
                cabinets. Engineered at our Palghar factory, configured to your schedule and dispatched across India.
            </p>
            <div class="hero__actions">
                <a class="btn btn--primary btn--lg" href="/catalog">Browse the catalog <?= icon('arrow-right') ?></a>
                <a class="btn btn--ghost btn--lg" href="/quote">Send a specification</a>
            </div>
            <dl class="hero__stats">
                <div>
                    <dt>20+</dt>
                    <dd>Years of industry leadership</dd>
                </div>
                <div>
                    <dt><?= (int) $totalProducts ?></dt>
                    <dd>Engineered lines</dd>
                </div>
                <div>
                    <dt>ISO 9001</dt>
                    <dd>JAS-ANZ certified QMS</dd>
                </div>
                <div>
                    <dt>Pan-India</dt>
                    <dd>Project dispatch</dd>
                </div>
            </dl>
        </div>
    </div>
</section>

<section class="trust-strip">
    <div class="container trust-strip__grid">
        <div class="trust-item"><?= icon('package') ?>
            <div><strong>Manufacturer direct</strong><span>No distributor markup</span></div>
        </div>
        <div class="trust-item"><?= icon('shield') ?>
            <div><strong>Tested to Indian standards</strong><span>IS / BIS where applicable</span></div>
        </div>
        <div class="trust-item"><?= icon('file') ?>
            <div><strong>GST invoice on every order</strong><span>HSN codes and tax breakup</span></div>
        </div>
        <div class="trust-item"><?= icon('truck') ?>
            <div><strong>Project dispatch</strong><span>Palghar factory to site</span></div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <div>
                <p class="eyebrow"><span></span> The range</p>
                <h2>Nine categories. One accountable manufacturer.</h2>
                <p class="section-head__lede">From the extinguisher on the wall to the hydrant at the boundary, every
                    line is made, tested and dispatched by the same team.</p>
            </div>
            <a class="text-link" href="/catalog">View all products <?= icon('arrow-right') ?></a>
        </div>
        <div class="category-grid">
            <?php foreach ($categories as $index => $category): ?>
                <a class="category-card <?= $index === 0 ? 'category-card--feature' : '' ?>"
                    href="/catalog/<?= e($category['slug']) ?>">
                    <span class="category-card__media">
                        <?php if (!empty($category['image'])): ?>
                            <img src="<?= e($category['image']) ?>" alt="" loading="lazy">
                        <?php endif; ?>
                    </span>
                    <span class="category-card__body">
                        <span
                            class="category-card__meta"><span><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span><span><?= (int) ($counts[$category['slug']] ?? 0) ?>
                                Product</span></span>
                        <strong><?= e($category['name']) ?></strong>
                        <small><?= e($category['tagline'] ?? '') ?></small>
                        <span class="category-card__cta">Shop category <?= icon('arrow-right') ?></span>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- <section class="section section--tint">
    <div class="container">
        <div class="section-head">
            <div>
                <p class="eyebrow"><span></span> Most specified</p>
                <h2>Ready to configure, ready to order.</h2>
                <p class="section-head__lede">Select capacity, finish or dimensions. The price updates as you build, and the configuration travels with the order.</p>
            </div>
            <a class="text-link" href="/catalog?configurable=1">All configurable lines <?= icon('arrow-right') ?></a>
        </div>
        <div class="product-grid">
            <?php foreach ($featured as $product): ?>
                <?php partial('product-card', ['product' => $product]); ?>
            <?php endforeach; ?>
        </div>
    </div>
</section> -->

<section class="configure-band">
    <div class="container configure-band__grid">
        <div class="configure-band__copy">
            <p class="eyebrow eyebrow--light"><span></span> Made to measure</p>
            <h2>Your drawing. Our factory. One predictable price.</h2>
            <p>Hose boxes and fire shaft doors are built to your opening size, finish and access requirements. Enter the
                dimensions and the configurator calculates the panel area and rate instantly.</p>
            <a class="btn btn--primary" href="/catalog/hose-boxes-and-fire-shaft-door">Configure a cabinet
                <?= icon('arrow-right') ?></a>
        </div>
        <ol class="configure-steps">
            <li><span>01</span>
                <div><strong>Choose the product family</strong>
                    <p>Start from a tested platform, not a blank sheet.</p>
                </div>
            </li>
            <li><span>02</span>
                <div><strong>Set the specification</strong>
                    <p>Capacity, material, finish, dimensions and quantity.</p>
                </div>
            </li>
            <li><span>03</span>
                <div><strong>Review and place the order</strong>
                    <p>Your account price, GST and delivery details in one place.</p>
                </div>
            </li>
        </ol>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <div>
                <p class="eyebrow"><span></span> Who we supply</p>
                <h2>Built for the people who specify, install and inspect.</h2>
                <p class="section-head__lede">Trade accounts for dealers, contractors and institutions. One price list,
                    one dispatch team, one point of accountability.</p>
            </div>
            <a class="text-link" href="/contact">Apply for a trade account <?= icon('arrow-right') ?></a>
        </div>
        <div class="sector-grid">
            <a class="sector-card" href="/contact">
                <span class="sector-card__icon"><?= icon('package') ?></span>
                <h3>Dealers &amp; distributors</h3>
                <p>Stock fast-moving extinguishers, hose reels and valves at manufacturer-direct rates, with GST
                    invoices and repeat-order history.</p>
                <span class="sector-card__link">Open a trade account <?= icon('arrow-right') ?></span>
            </a>
            <a class="sector-card" href="/quote">
                <span class="sector-card__icon"><?= icon('clipboard') ?></span>
                <h3>MEP contractors</h3>
                <p>Configure hydrant and sprinkler packages to the schedule, with delivery notes, test certificates and
                    phased dispatch.</p>
                <span class="sector-card__link">Send a BOQ <?= icon('arrow-right') ?></span>
            </a>
            <a class="sector-card" href="/contact">
                <span class="sector-card__icon"><?= icon('users') ?></span>
                <h3>Institutions &amp; campuses</h3>
                <p>Standardise equipment across buildings with account pricing, documented specifications and
                    predictable replenishment.</p>
                <span class="sector-card__link">Talk to sales <?= icon('arrow-right') ?></span>
            </a>
            <a class="sector-card" href="/catalog/hose-boxes-and-fire-shaft-door">
                <span class="sector-card__icon"><?= icon('grid') ?></span>
                <h3>Industrial projects</h3>
                <p>Fire shaft doors, bespoke cabinets and made-to-measure fabrication, built to your opening and
                    delivered to site.</p>
                <span class="sector-card__link">Configure a cabinet <?= icon('arrow-right') ?></span>
            </a>
        </div>
    </div>
</section>

<section class="section section--tint">
    <div class="container split">
        <div class="split__media">
            <img src="/images/unit/assembly.jpg"
                alt="Assembly and inspection at the Ronak Fire manufacturing unit in Palghar" loading="lazy" width="720"
                height="540">
            <span class="split__badge"><?= icon('package') ?> Palghar, Maharashtra · ISO 9001:2015</span>
        </div>
        <div class="split__copy">
            <p class="eyebrow"><span></span> Inside the factory</p>
            <h2>A manufacturer, not a trading desk.</h2>
            <p>Ronak Fire India Pvt. Ltd. designs and builds firefighting equipment under one integrated group, led by
                an Ex-Defence professional with more than 20 years of industry experience.</p>
            <p>Press shop, fabrication, machining, coating, casting, filling, assembly and testing sit under one roof.
                That means fewer hand-offs, tighter tolerances and a quality system that is audited end to end.</p>
            <dl class="fact-list">
                <div>
                    <dt>Quality system</dt>
                    <dd>ISO 9001:2015, certified by JAS-ANZ</dd>
                </div>
                <div>
                    <dt>Manufacturing</dt>
                    <dd>Palghar, Maharashtra</dd>
                </div>
                <div>
                    <dt>Range</dt>
                    <dd><?= (int) $totalProducts ?> engineered lines across 9 categories</dd>
                </div>
                <div>
                    <dt>Supply</dt>
                    <dd>Dealers, contractors, institutions and industry</dd>
                </div>
            </dl>
            <a class="text-link" href="/about">Inside Ronak Fire <?= icon('arrow-right') ?></a>
        </div>
    </div>
</section>

<!-- <section class="section section--tight client-band">
    <div class="container">
        <div class="section-head">
            <div>
                <p class="eyebrow"><span></span> Trusted on projects</p>
                <h2>Specified by teams who cannot compromise.</h2>
                <p class="section-head__lede">Our equipment is installed with leading national and multinational
                    organisations, and with government establishments across India.</p>
            </div>
            <a class="text-link" href="/about#clients">Our credentials <?= icon('arrow-right') ?></a>
        </div>
        <div class="client-logos">
            <?php for ($logo = 1; $logo <= 6; $logo++): ?>
                <img src="/images/client/<?= $logo ?>.png" alt="" loading="lazy" width="120" height="48">
            <?php endfor; ?>
        </div>
        <div class="quality-badges">
            <span><?= icon('shield') ?> ISO 9001:2015 · JAS-ANZ</span>
            <span><?= icon('check') ?> IS / BIS tested where applicable</span>
            <span><?= icon('file') ?> GST invoices with HSN codes</span>
            <span><?= icon('truck') ?> Pan-India project dispatch</span>
        </div>
    </div>
</section> -->

<section class="section">
    <div class="container quote-cta">
        <div>
            <p class="eyebrow"><span></span> For consultants &amp; contractors</p>
            <h2>Have a BOQ, drawing or a deadline?</h2>
            <p>Send the specification and our sales engineers will come back with availability, lead time and a project
                quotation.</p>
        </div>
        <div class="quote-cta__actions">
            <a class="btn btn--dark btn--lg" href="/quote">Request a quote <?= icon('arrow-right') ?></a>
            <a class="quote-cta__phone" href="tel:<?= e($phoneHref) ?>">
                <?= icon('phone') ?>
                <span><small>Or call sales</small><strong><?= e($phone) ?></strong></span>
            </a>
        </div>
    </div>
</section>
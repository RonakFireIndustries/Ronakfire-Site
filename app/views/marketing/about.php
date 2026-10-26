<?php
/** @var int $totalProducts */
$phone = setting('support_phone', config('store.support_phone'));
$phoneHref = setting('support_phone_href', config('store.support_phone_href'));

$departments = [
    ['Press Shop', 'Raw metal sheets are cut, bent and punched into precise components for hose reels, boxes and structural assemblies.'],
    ['Trop (rod & pipe cutting)', 'Automated cutting of rods and pipes in multiple sizes, dimensioned before machining or welding.'],
    ['Welding & Fabrication', 'MIG welding and custom fabrication bring individual parts into strong, aligned structures.'],
    ['Lathe & CNC', 'Turning, threading and shaping of round components to tight tolerances and a clean mechanical finish.'],
    ['Phosphating & Powder Coating', 'Surface preparation and powder coating protect steel parts against corrosion and site conditions.'],
    ['Aluminium Furnace & Casting', 'In-house melting and casting of aluminium components used across hose reels and equipment bodies.'],
    ['Sprinkler Assembly & Testing', 'Sprinkler heads are assembled and checked before they are packed for project dispatch.'],
    ['Cylinder Filling & Testing', 'Powder filling, weight accuracy, leakage checks and pressure testing for every extinguisher cylinder.'],
    ['Assembly & Dispatch', 'Finished components become complete equipment, inspected, packed, labelled and dispatched across India.'],
    ['Box Department', 'Single-door, double-door and MS fire shaft boxes are cut, bent, welded and finished as complete cabinets.'],
];
?>
<section class="page-hero">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="/">Home</a><?= icon('chevron-right') ?><span>About</span>
        </nav>
        <div class="page-hero__grid">
            <div>
                <p class="eyebrow"><span></span> The company</p>
                <h1>Fire protection is an engineering discipline.</h1>
                <p class="page-hero__lede">Ronak Fire India Pvt. Ltd. designs, manufactures and supplies firefighting equipment, fire protection systems and fire alarm solutions under one integrated group.</p>
            </div>
            <div class="page-hero__aside">
                <strong>20+</strong>
                <span>Years of industry leadership</span>
                <small>ISO 9001:2015 · JAS-ANZ</small>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container about-story">
        <div class="about-story__body">
            <p class="eyebrow"><span></span> Who we are</p>
            <h2>Dedication to delivering the best fire safety solutions.</h2>
            <p>At Ronak Fire India Pvt. Ltd. we are dedicated to the science, engineering and advancement of modern firefighting solutions. Established with a vision to become India’s most trusted and recognised fire protection brand, we manufacture high-quality firefighting equipment, fire protection systems and fire alarm solutions under one integrated group.</p>
            <p>The company is led by an Ex-Defence professional with more than 20 years of industry experience, supported by a skilled technical team. Products are designed and manufactured to conform to stringent I.S.I. standards, with reliability and safety checked at every stage.</p>
            <blockquote class="about-quote">“Only the best products leave our plant.”</blockquote>
            <p>Our manufacturing facility is equipped with modern infrastructure, and we follow a rigorous ISO 9001:2015 Quality Management System certified by JAS-ANZ — from raw material selection to final dispatch. Customer-centric policies and disciplined inspection have earned the trust of leading national and multinational organisations, along with government establishments across India.</p>
            <p>We are committed to protecting life and property through innovation, precision engineering and uncompromising quality.</p>
        </div>
        <aside class="about-side" aria-label="Company at a glance">
            <h2>At a glance</h2>
            <dl>
                <div><dt>Leadership</dt><dd>Ex-Defence professional with 20+ years of industry experience</dd></div>
                <div><dt>Factory</dt><dd>Palghar (W), District Palghar, Maharashtra</dd></div>
                <div><dt>Quality system</dt><dd>ISO 9001:2015, certified by JAS-ANZ</dd></div>
                <div><dt>Range</dt><dd><?= (int) $totalProducts ?> engineered lines across 9 categories</dd></div>
                <div><dt>Customers</dt><dd>Dealers, contractors, institutions, industry and government</dd></div>
            </dl>
            <a class="btn btn--outline btn--sm" href="/contact">Talk to our team <?= icon('arrow-right') ?></a>
        </aside>
    </div>
</section>

<section class="section section--tint">
    <div class="container">
        <div class="section-head">
            <div>
                <p class="eyebrow"><span></span> Direction</p>
                <h2>What we are building towards.</h2>
            </div>
        </div>
        <div class="pillar-grid">
            <article class="pillar">
                <p class="eyebrow">Vision</p>
                <h3>India’s most trusted fire protection brand.</h3>
                <p>To deliver world-class firefighting products and integrated safety solutions that protect life, property and critical infrastructure — with uncompromising quality, consistency and innovation.</p>
            </article>
            <article class="pillar">
                <p class="eyebrow">Mission</p>
                <h3>Safer environments, built deliberately.</h3>
                <ul>
                    <li>Deliver top-quality fire protection systems that safeguard lives and assets.</li>
                    <li>Continuously upgrade safety systems as fire regulations and industry demands evolve.</li>
                    <li>Execute projects on time, with precision, transparency and efficiency.</li>
                    <li>Support customers with technical guidance through the life of the product.</li>
                    <li>Build safer environments for industry, infrastructure and communities nationwide.</li>
                </ul>
            </article>
        </div>
    </div>
</section>

<section class="section" id="manufacturing">
    <div class="container">
        <div class="section-head">
            <div>
                <p class="eyebrow"><span></span> Manufacturing</p>
                <h2>Ten departments under one roof.</h2>
                <p class="section-head__lede">Components are produced, assembled and tested in-house. Each department hands over to the next with a measurable check, not an assumption.</p>
            </div>
            <a class="text-link" href="/contact">Plan a factory visit <?= icon('arrow-right') ?></a>
        </div>
        <div class="capability-grid">
            <?php foreach ($departments as $index => [$name, $description]): ?>
                <article class="capability">
                    <span><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
                    <div>
                        <strong><?= e($name) ?></strong>
                        <small><?= e($description) ?></small>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section--tint" id="quality">
    <div class="container split">
        <div class="split__copy">
            <p class="eyebrow"><span></span> Quality system</p>
            <h2>Tested before it leaves the plant.</h2>
            <p>Quality is a process, not a final inspection. Raw material is verified on receipt, components are checked in process, and finished equipment is tested and recorded before dispatch.</p>
            <ul class="check-list">
                <li><?= icon('check') ?> Raw material verification and batch traceability</li>
                <li><?= icon('check') ?> In-process inspection at every department hand-off</li>
                <li><?= icon('check') ?> Pressure, leakage and weight checks on filled cylinders</li>
                <li><?= icon('check') ?> Final inspection, packing and labelling before dispatch</li>
                <li><?= icon('check') ?> ISO 9001:2015 Quality Management System, certified by JAS-ANZ</li>
            </ul>
            <a class="text-link" href="/catalog">See the tested range <?= icon('arrow-right') ?></a>
        </div>
        <div class="split__media">
            <img src="/images/unit/sprinkler.jpg" alt="Sprinkler assembly and testing at the Ronak Fire factory" loading="lazy" width="720" height="540">
            <span class="split__badge"><?= icon('shield') ?> Batch tested · ISO 9001:2015</span>
        </div>
    </div>
</section>

<section class="section" id="clients">
    <div class="container">
        <div class="section-head">
            <div>
                <p class="eyebrow"><span></span> Our clients</p>
                <h2>Trusted by project teams nationwide.</h2>
                <p class="section-head__lede">Government establishments, national organisations and multinational companies rely on Ronak Fire equipment across their sites.</p>
            </div>
        </div>
        <div class="client-logos">
            <?php for ($logo = 1; $logo <= 6; $logo++): ?>
                <img src="/images/client/<?= $logo ?>.png" alt="" loading="lazy" width="120" height="48">
            <?php endfor; ?>
        </div>
        <div class="quality-badges">
            <span><?= icon('shield') ?> ISO 9001:2015 · JAS-ANZ</span>
            <span><?= icon('check') ?> I.S.I. standards</span>
            <span><?= icon('file') ?> GST invoices with HSN codes</span>
            <span><?= icon('truck') ?> Pan-India dispatch</span>
        </div>
    </div>
</section>

<section class="section">
    <div class="container quote-cta">
        <div>
            <p class="eyebrow"><span></span> Work with us</p>
            <h2>Need a manufacturer your project team can hold accountable?</h2>
            <p>Open a trade account, request a quotation or visit the factory. We will connect you with the right specification and lead time.</p>
        </div>
        <div class="quote-cta__actions">
            <a class="btn btn--dark btn--lg" href="/contact">Contact sales <?= icon('arrow-right') ?></a>
            <a class="quote-cta__phone" href="tel:<?= e($phoneHref) ?>">
                <?= icon('phone') ?>
                <span><small>Or call sales</small><strong><?= e($phone) ?></strong></span>
            </a>
        </div>
    </div>
</section>

<?php
$salesPhone = setting('support_phone', config('store.support_phone'));
$salesPhoneHref = setting('support_phone_href', config('store.support_phone_href'));
$salesEmail = setting('support_email', config('store.support_email'));

$corporateAddress = 'E001, Kasturi Vandana Complex, Off Kasturi Vandana Industrial Estate, Near Hero Asian Motor, Goddev Phatak Road, Bhayandar East, Maharashtra 401105';
$factoryAddress = 'Plot No. 62 & 63, S. No. 984, Old Satpari Road, Behind Anand Arc, Village Shirgaon, Palghar (W), District Palghar 401404';

$corporateMap = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode('Kasturi Vandana Complex, Goddev Phatak Road, Bhayandar East, Maharashtra 401105');
$factoryMap = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode('Shirgaon, Palghar West, Maharashtra 401404');
?>
<section class="page-hero">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="/">Home</a><?= icon('chevron-right') ?><span>Contact</span>
        </nav>
        <div class="page-hero__grid">
            <div>
                <p class="eyebrow"><span></span> Contact us</p>
                <h1>Talk to the people who build it.</h1>
                <p class="page-hero__lede">For pricing, drawings, lead times or a factory visit, reach the sales and technical team directly. Trade accounts are opened for dealers, contractors and institutions.</p>
            </div>
            <div class="page-hero__aside">
                <strong>Mon–Sat</strong>
                <span>9:30 am – 6:30 pm IST</span>
                <small>Sales &amp; technical support</small>
            </div>
        </div>
    </div>
</section>

<section class="section section--tight">
    <div class="container">
        <div class="contact-grid">
            <article class="contact-card">
                <span class="contact-card__icon"><?= icon('package') ?></span>
                <h2>Corporate office</h2>
                <address><?= e($corporateAddress) ?></address>
                <div class="contact-card__links">
                    <a href="tel:<?= e($salesPhoneHref) ?>"><?= icon('phone') ?><?= e($salesPhone) ?></a>
                    <a href="mailto:<?= e($salesEmail) ?>"><?= icon('mail') ?><?= e($salesEmail) ?></a>
                    <a href="<?= e($corporateMap) ?>" target="_blank" rel="noopener"><?= icon('arrow-right') ?>Get directions</a>
                </div>
            </article>

            <article class="contact-card">
                <span class="contact-card__icon"><?= icon('grid') ?></span>
                <h2>Factory</h2>
                <address><?= e($factoryAddress) ?></address>
                <div class="contact-card__links">
                    <a href="tel:+919323947512"><?= icon('phone') ?>+91 93239 47512</a>
                    <a href="mailto:info@ronakfire.com"><?= icon('mail') ?>info@ronakfire.com</a>
                    <a href="<?= e($factoryMap) ?>" target="_blank" rel="noopener"><?= icon('arrow-right') ?>Get directions</a>
                </div>
            </article>

            <article class="contact-card">
                <span class="contact-card__icon"><?= icon('users') ?></span>
                <h2>Sales &amp; support</h2>
                <p class="contact-card__note">Orders, quotations and technical queries. Send a drawing or BOQ and we will respond with availability and lead time.</p>
                <div class="contact-card__links">
                    <a href="tel:+919323947512"><?= icon('phone') ?>+91 93239 47512</a>
                    <a href="tel:+918104039710"><?= icon('phone') ?>+91 81040 39710</a>
                    <a href="mailto:<?= e($salesEmail) ?>"><?= icon('mail') ?><?= e($salesEmail) ?></a>
                    <a href="mailto:info@ronakfire.com"><?= icon('mail') ?>info@ronakfire.com</a>
                </div>
            </article>
        </div>

        <div class="contact-actions">
            <div class="contact-panel contact-panel--dark">
                <p class="eyebrow eyebrow--light"><span></span> Quotations</p>
                <h2>Send the specification. We will price it.</h2>
                <p>Share the product, quantity, delivery pincode and required-on-site date. Drawings and BOQ references help us quote accurately the first time.</p>
                <a class="btn btn--primary btn--lg" href="/quote">Request a quote <?= icon('arrow-right') ?></a>
            </div>
            <div class="contact-panel">
                <p class="eyebrow"><span></span> Trade customers</p>
                <h2>Already have an account?</h2>
                <p>Sign in to see your account pricing, reorder configured equipment and track order status.</p>
                <div class="contact-panel__actions">
                    <a class="btn btn--dark" href="/login">Customer login <?= icon('arrow-right') ?></a>
                    <a class="text-link" href="/catalog">Browse the catalog</a>
                </div>
                <p class="contact-panel__small">Trade accounts are opened for verified businesses only. The store never stores customer passwords.</p>
            </div>
        </div>
    </div>
</section>

<section class="section section--tint">
    <div class="container split">
        <div class="split__copy">
            <p class="eyebrow"><span></span> Faster quotations</p>
            <h2>What to include in your enquiry.</h2>
            <ul class="check-list">
                <li><?= icon('check') ?> Product name, size or capacity — or the relevant IS specification</li>
                <li><?= icon('check') ?> Quantity and delivery pincode</li>
                <li><?= icon('check') ?> Drawing, opening size or BOQ reference for fabricated items</li>
                <li><?= icon('check') ?> GSTIN and billing company name</li>
                <li><?= icon('check') ?> Required-on-site date and any phased delivery plan</li>
            </ul>
            <p class="muted">Urgent site requirement? Call <a href="tel:<?= e($salesPhoneHref) ?>"><?= e($salesPhone) ?></a> and ask for the project desk.</p>
        </div>
        <div class="split__media">
            <img src="/images/unit/press-shop.jpg" alt="Press shop at the Ronak Fire manufacturing unit" loading="lazy" width="720" height="540">
            <span class="split__badge"><?= icon('clock') ?> Factory visits by appointment</span>
        </div>
    </div>
</section>

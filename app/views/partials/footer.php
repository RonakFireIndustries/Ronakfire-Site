<?php
$footerCategories = App\Catalog::categories();
$footerCategories = array_slice($footerCategories, 0, 6);
$footerPhone = (string) setting('support_phone', config('store.support_phone'));
$footerPhoneHref = (string) setting('support_phone_href', config('store.support_phone_href'));
$footerEmail = (string) setting('support_email', config('store.support_email'));
$footerAddress = (string) config('store.address');
?>
<footer class="site-footer">
    <div class="container">
        <div class="site-footer__top">
            <div class="site-footer__brand">
                <a href="/" aria-label="Ronak Fire home">
                    <img src="/images/logo.png" alt="Ronak Fire India Pvt. Ltd." width="190" height="50" loading="lazy">
                </a>
                <p>Firefighting equipment engineered, manufactured and supplied from Mumbai since 1998. Built for consultants, contractors, institutions and industry.</p>
                <div class="site-footer__proof">
                    <span><?= icon('shield') ?> IS / BIS tested</span>
                    <span><?= icon('truck') ?> Pan-India dispatch</span>
                    <span><?= icon('file') ?> GST invoices</span>
                </div>
            </div>

            <div class="site-footer__column">
                <h2>Shop</h2>
                <?php foreach ($footerCategories as $category): ?>
                    <a href="/catalog/<?= e($category['slug']) ?>"><?= e($category['short_name'] ?: $category['name']) ?></a>
                <?php endforeach; ?>
                <a class="site-footer__more" href="/catalog">All products <?= icon('arrow-right') ?></a>
            </div>

            <div class="site-footer__column">
                <h2>Company</h2>
                <a href="/about">About Ronak Fire</a>
                <a href="/catalog">Product range</a>
                <a href="/about#quality">Quality &amp; compliance</a>
                <a href="/about#clients">Our clients</a>
                <a href="/contact">Contact &amp; offices</a>
                <a href="/quote">Request a quotation</a>
                <a href="/login">Trade customer login</a>
                <a href="/admin/login">Administrator</a>
            </div>

            <div class="site-footer__column site-footer__contact">
                <h2>Talk to sales</h2>
                <a class="site-footer__phone" href="tel:<?= e($footerPhoneHref) ?>"><?= icon('phone') ?><?= e($footerPhone) ?></a>
                <a href="mailto:<?= e($footerEmail) ?>"><?= icon('mail') ?><?= e($footerEmail) ?></a>
                <p><?= icon('package') ?><span><?= e($footerAddress) ?></span></p>
                <span class="site-footer__hours"><?= icon('clock') ?> Mon–Sat, 9:30 am – 6:30 pm IST</span>
            </div>
        </div>

        <div class="site-footer__bottom">
            <p>&copy; <?= date('Y') ?> <?= e(config('store.name')) ?>. All rights reserved.</p>
            <div class="site-footer__legal">
                <a href="/privacy">Privacy</a>
                <a href="/refund-policy">Refund &amp; cancellation</a>
                <span>Prices exclusive of GST unless stated.</span>
            </div>
        </div>
    </div>
</footer>

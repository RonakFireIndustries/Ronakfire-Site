<?php
$navCategories = App\Catalog::categories();
$navCustomer = current_customer();
$navCartCount = cart_count();
$announcement = (string) setting('announcement', 'Manufacturer-direct fire protection · GST invoices · Pan-India dispatch');
$phone = (string) setting('support_phone', config('store.support_phone'));
$phoneHref = (string) setting('support_phone_href', config('store.support_phone_href'));
$activeNav = $activeNav ?? '';
?>
<div class="announcement">
    <div class="container announcement__inner">
        <p><?= icon('spark', 'announcement__icon') ?><?= e($announcement) ?></p>
        <div class="announcement__aside">
            <span class="announcement__badge">B2B trade store</span>
            <a href="tel:<?= e($phoneHref) ?>"><?= icon('phone') ?><?= e($phone) ?></a>
            <a href="/contact" class="announcement__link">Contact sales</a>
        </div>
    </div>
</div>

<header class="site-header" id="siteHeader">
    <div class="container site-header__inner">
        <a class="brand" href="/" aria-label="Ronak Fire home">
            <img src="/images/logo.png" alt="Ronak Fire India Pvt. Ltd." width="176" height="46">
            <span class="brand__tag">Fire store</span>
        </a>

        <nav class="primary-nav" aria-label="Primary">
            <a class="primary-nav__link <?= $activeNav === '/shop' ? 'is-active' : '' ?>" href="/shop">Shop</a>
            <div class="nav-dropdown" data-dropdown>
                <button class="primary-nav__link nav-dropdown__toggle <?= $activeNav === '/catalog' ? 'is-active' : '' ?>" type="button" aria-expanded="false" data-dropdown-toggle>
                    Products <?= icon('chevron-down') ?>
                </button>
                <div class="mega" data-dropdown-panel>
                    <div class="mega__grid">
                        <?php foreach ($navCategories as $category): ?>
                            <a class="mega__item" href="/catalog/<?= e($category['slug']) ?>">
                                <span class="mega__thumb">
                                    <?php if (!empty($category['image'])): ?>
                                        <img src="<?= e($category['image']) ?>" alt="" loading="lazy">
                                    <?php endif; ?>
                                </span>
                                <span>
                                    <strong><?= e($category['short_name'] ?: $category['name']) ?></strong>
                                    <small><?= e($category['tagline'] ?? '') ?></small>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <div class="mega__footer">
                        <a class="mega__all" href="/catalog">View the full catalog <?= icon('arrow-right') ?></a>
                        <span>Can’t find a specification? <a href="/quote">Send it to our engineers</a>.</span>
                    </div>
                </div>
            </div>
            <a class="primary-nav__link <?= $activeNav === '/configure' ? 'is-active' : '' ?>" href="/catalog?configurable=1">Configure</a>
            <a class="primary-nav__link <?= $activeNav === '/quote' ? 'is-active' : '' ?>" href="/quote">Request quote</a>
            <a class="primary-nav__link <?= $activeNav === 'about' ? 'is-active' : '' ?>" href="/about">Company</a>
        </nav>

        <div class="header-actions">
            <button class="icon-btn header-actions__search" type="button" data-search-open aria-label="Search products"><?= icon('search') ?></button>

            <a class="account-chip" href="<?= $navCustomer ? '/account' : '/login' ?>">
                <span class="account-chip__icon"><?= icon('user') ?></span>
                <span class="account-chip__text">
                    <small><?= $navCustomer ? e($navCustomer['customer_code'] ?: 'Trade account') : 'B2B pricing' ?></small>
                    <strong><?= $navCustomer ? e($navCustomer['company_name']) : 'Dealer login' ?></strong>
                </span>
            </a>

            <a class="cart-chip" href="/cart" aria-label="Your order, <?= $navCartCount ?> items">
                <?= icon('cart') ?>
                <span class="cart-chip__label">Order</span>
                <span class="cart-chip__count <?= $navCartCount > 0 ? 'is-visible' : '' ?>" data-cart-count><?= $navCartCount ?></span>
            </a>

            <button class="menu-toggle" type="button" data-menu-toggle aria-controls="mobileNav" aria-expanded="false">
                <span class="menu-toggle__open"><?= icon('menu') ?></span>
                <span class="menu-toggle__close"><?= icon('close') ?></span>
                <span class="sr-only">Menu</span>
            </button>
        </div>
    </div>
</header>

<div class="mobile-nav" id="mobileNav" hidden>
    <div class="mobile-nav__inner">
        <form class="mobile-search" action="/catalog" method="get" role="search">
            <label class="sr-only" for="mobileSearch">Search products</label>
            <input id="mobileSearch" type="search" name="q" placeholder="Search products…">
            <button class="icon-btn" type="submit" aria-label="Search"><?= icon('search') ?></button>
        </form>
        <nav class="mobile-nav__links" aria-label="Mobile">
            <a href="/">Home <span>Ronak Fire</span></a>
            <a href="/shop">Shop <span>Start here</span></a>
            <a href="/catalog">All products <span><?= (int) App\Catalog::count([]) ?> lines</span></a>
            <a href="/catalog?configurable=1">Configure a product <span>Made to spec</span></a>
            <a href="/quote">Request a quote <span>Send drawings</span></a>
            <a href="/about">About Ronak Fire <span></span></a>
            <a href="/contact">Contact sales <span></span></a>
        </nav>
        <div class="mobile-nav__categories">
            <p class="eyebrow">Shop by category</p>
            <?php foreach ($navCategories as $category): ?>
                <a href="/catalog/<?= e($category['slug']) ?>"><?= e($category['name']) ?><?= icon('chevron-right') ?></a>
            <?php endforeach; ?>
        </div>
        <div class="mobile-nav__account">
            <?php if ($navCustomer): ?>
                <a class="btn btn--primary btn--block" href="/account"><?= icon('user') ?> My account</a>
            <?php else: ?>
                <a class="btn btn--primary btn--block" href="/login"><?= icon('lock') ?> Dealer login</a>
                <p>Prices and ordering are available to verified trade customers.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<div class="nav-scrim" data-menu-close hidden></div>

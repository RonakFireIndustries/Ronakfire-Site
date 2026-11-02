<?php
$accountCustomer = current_customer();
$path = current_path();
?>
<aside class="account-nav">
    <div class="account-nav__identity">
        <span class="account-nav__avatar"><?= e(strtoupper(substr((string) ($accountCustomer['company_name'] ?? 'R'), 0, 1))) ?></span>
        <div>
            <strong><?= e($accountCustomer['company_name'] ?? '') ?></strong>
            <small><?= e($accountCustomer['customer_code'] ?: 'Trade customer') ?></small>
        </div>
    </div>
    <nav aria-label="Account">
        <a href="/account" class="<?= $path === '/account' ? 'is-active' : '' ?>"><?= icon('grid') ?> Overview</a>
        <a href="/account/profile" class="<?= str_starts_with($path, '/account/profile') ? 'is-active' : '' ?>"><?= icon('user') ?> My details</a>
        <a href="/account/orders" class="<?= str_starts_with($path, '/account/orders') ? 'is-active' : '' ?>"><?= icon('package') ?> Orders</a>
        <a href="/account/enquiries" class="<?= str_starts_with($path, '/account/enquiries') ? 'is-active' : '' ?>"><?= icon('clipboard') ?> Quote requests</a>
        <a href="/account/addresses" class="<?= str_starts_with($path, '/account/addresses') ? 'is-active' : '' ?>"><?= icon('map-pin') ?> Addresses</a>
        <a href="/account/inventory" class="<?= str_starts_with($path, '/account/inventory') ? 'is-active' : '' ?>"><?= icon('package') ?> Inventory</a>
        <a href="/account/documents" class="<?= str_starts_with($path, '/account/documents') ? 'is-active' : '' ?>"><?= icon('file') ?> Documents</a>
        <a href="/quote"><?= icon('plus') ?> New quote request</a>
    </nav>
    <div class="account-nav__foot">
        <a href="/catalog"><?= icon('search') ?> Browse catalog</a>
        <form action="/logout" method="post">
            <?= csrf_field() ?>
            <button class="account-nav__logout" type="submit"><?= icon('logout') ?> Sign out</button>
        </form>
    </div>
</aside>

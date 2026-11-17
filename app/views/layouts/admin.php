<?php
/** @var string $content */
/** @var array|null $admin */
/** @var string $adminNav */
/** @var string $adminTitle */
$adminNav = $adminNav ?? '';
$adminTitle = $adminTitle ?? 'Dashboard';
$newOrders = (int) db()->value("SELECT COUNT(*) FROM orders WHERE status = 'placed'");
$newEnquiries = (int) db()->value("SELECT COUNT(*) FROM enquiries WHERE status = 'new'");
$adminInitial = strtoupper(substr((string) ($admin['name'] ?? 'A'), 0, 1));
?>
<!doctype html>
<html lang="en-IN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($pageTitle ?? 'Ronak Fire admin') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('assets/css/admin.css')) ?>">
</head>
<body class="admin-body">
<a class="skip-link" href="#adminMain">Skip to content</a>
<div class="admin-shell">
    <aside class="admin-sidebar" id="adminSidebar">
        <a class="admin-sidebar__brand" href="/admin">
            <img src="/images/logo.png" alt="Ronak Fire">
            <span>Store admin</span>
        </a>

        <nav class="admin-nav" aria-label="Admin">
            <p class="admin-nav__label">Overview</p>
            <a href="/admin" class="<?= $adminNav === 'dashboard' ? 'is-active' : '' ?>"><?= icon('grid') ?><span>Dashboard</span></a>

            <p class="admin-nav__label">Catalog</p>
            <a href="/admin/products" class="<?= $adminNav === 'products' ? 'is-active' : '' ?>"><?= icon('package') ?><span>Products</span></a>
            <a href="/admin/categories" class="<?= $adminNav === 'categories' ? 'is-active' : '' ?>"><?= icon('filter') ?><span>Categories</span></a>

            <p class="admin-nav__label">Sales</p>
            <a href="/admin/orders" class="<?= $adminNav === 'orders' ? 'is-active' : '' ?>"><?= icon('cart') ?><span>Orders</span><?php if ($newOrders): ?><em class="admin-nav__badge"><?= $newOrders ?></em><?php endif; ?></a>
            <a href="/admin/enquiries" class="<?= $adminNav === 'enquiries' ? 'is-active' : '' ?>"><?= icon('clipboard') ?><span>Quote requests</span><?php if ($newEnquiries): ?><em class="admin-nav__badge"><?= $newEnquiries ?></em><?php endif; ?></a>
            <a href="/admin/customers" class="<?= $adminNav === 'customers' ? 'is-active' : '' ?>"><?= icon('users') ?><span>Customers</span></a>
            <?php if (!\App\Auth::isSales()): ?>
                <a href="/admin/team" class="<?= $adminNav === 'team' ? 'is-active' : '' ?>"><?= icon('users') ?><span>Sales team</span></a>
                <a href="/admin/sales-report" class="<?= $adminNav === 'sales-report' ? 'is-active' : '' ?>"><?= icon('chart') ?><span>Ownership report</span></a>
            <?php endif; ?>

            <p class="admin-nav__label">System</p>
            <a href="/admin/settings" class="<?= $adminNav === 'settings' ? 'is-active' : '' ?>"><?= icon('settings') ?><span>Store settings</span></a>
            <a href="/admin/account" class="<?= $adminNav === 'account' ? 'is-active' : '' ?>"><?= icon('user') ?><span>Your account</span></a>
        </nav>

        <div class="admin-sidebar__foot">
            <a href="/" target="_blank" rel="noopener"><?= icon('eye') ?><span>View storefront</span></a>
            <form action="/admin/logout" method="post">
                <?= csrf_field() ?>
                <button type="submit"><?= icon('logout') ?><span>Sign out</span></button>
            </form>
        </div>
    </aside>
    <div class="admin-scrim" data-admin-menu-close hidden></div>

    <div class="admin-main">
        <header class="admin-topbar">
            <button class="admin-menu-toggle" type="button" data-admin-menu aria-label="Open menu"><?= icon('menu') ?></button>
            <div class="admin-topbar__title">
                <span>Administration</span>
                <h1><?= e($adminTitle) ?></h1>
            </div>
            <div class="admin-topbar__actions">
                <?php if (setting('prices_are_placeholders', '0') === '1'): ?>
                    <a class="admin-pill admin-pill--warn" href="/admin/settings"><?= icon('alert') ?> Prices are placeholders</a>
                <?php endif; ?>
                <a class="admin-pill" href="/" target="_blank" rel="noopener"><?= icon('eye') ?> Store</a>
                <div class="admin-user">
                    <span class="admin-user__avatar"><?= e($adminInitial) ?></span>
                    <span class="admin-user__text"><small>Signed in</small><strong><?= e($admin['name'] ?? 'Administrator') ?></strong></span>
                </div>
            </div>
        </header>

        <main class="admin-content" id="adminMain">
            <?php partial('flash'); ?>
            <?= $content ?>
        </main>
    </div>
</div>
<script src="<?= e(asset('assets/js/admin.js')) ?>" defer></script>
</body>
</html>

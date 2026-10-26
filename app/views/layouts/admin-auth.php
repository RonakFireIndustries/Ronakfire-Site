<?php /** @var string $content */ ?><!doctype html>
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
<body class="admin-auth-body">
<div class="admin-auth">
    <aside class="admin-auth__brand">
        <a href="/" class="admin-auth__logo"><img src="/images/logo.png" alt="Ronak Fire"></a>
        <div class="admin-auth__copy">
            <p class="admin-auth__eyebrow">Ronak Fire store operations</p>
            <h1>Run the catalog.<br>Move the orders.</h1>
            <p>Prices, configurations, freight, customer tiers and quote requests — managed from one place.</p>
        </div>
        <ul class="admin-auth__points">
            <li>Server-authoritative pricing</li>
            <li>Order and invoice workflow</li>
            <li>Quote requests from the storefront</li>
        </ul>
        <p class="admin-auth__foot">Authorised personnel only. Activity is logged.</p>
    </aside>
    <main class="admin-auth__main">
        <div class="admin-auth__card">
            <?php partial('flash'); ?>
            <?= $content ?>
        </div>
        <a class="admin-auth__back" href="/">← Back to the store</a>
    </main>
</div>
<script src="<?= e(asset('assets/js/admin.js')) ?>" defer></script>
</body>
</html>

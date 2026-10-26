<?php
/**
 * Storefront layout.
 *
 * @var string $content
 * @var string $pageTitle
 * @var string $metaDescription
 * @var string $bodyClass
 * @var string|null $canonical
 * @var string $activeNav
 */
$canonical = $canonical ?? null;
$bodyClass = $bodyClass ?? '';
$activeNav = $activeNav ?? '';
$robots = $robots ?? null;
$noIndexPaths = ['/cart', '/checkout', '/account', '/order/', '/login'];
if ($robots === null) {
    foreach ($noIndexPaths as $prefix) {
        if (str_starts_with(current_path(), $prefix)) {
            $robots = 'noindex, nofollow';
            break;
        }
    }
}
$pageTitle = $pageTitle ?? config('store.name');
$metaDescription = $metaDescription ?? 'Fire extinguishers, hose reel drums, hydrants, sprinklers and fire safety equipment from Ronak Fire India. Configure products and order at B2B pricing.';
?>
<!doctype html>
<html lang="en-IN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f5831f">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($metaDescription) ?>">
    <?php if ($robots): ?><meta name="robots" content="<?= e($robots) ?>"><?php endif; ?>
    <?php if (!empty($canonical)): ?><link rel="canonical" href="<?= e($canonical) ?>"><?php endif; ?>
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= e(config('store.name')) ?>">
    <meta property="og:title" content="<?= e($pageTitle) ?>">
    <meta property="og:description" content="<?= e($metaDescription) ?>">
    <meta property="og:image" content="<?= e(abs_url('/images/banner/main-banner.jpg')) ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="/images/favicon/favicon-180.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/images/favicon/favicon-16x16.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('assets/css/store.css')) ?>">
</head>
<body class="<?= e($bodyClass) ?>">
<a class="skip-link" href="#main">Skip to content</a>
<?php partial('header', ['activeNav' => $activeNav]); ?>

<main id="main" class="site-main">
    <?php partial('flash'); ?>
    <?= $content ?>
</main>

<?php partial('footer'); ?>

<div class="toast-stack" id="toastStack" aria-live="polite" aria-atomic="true"></div>
<div class="search-overlay" id="searchOverlay" hidden>
    <form class="search-overlay__form container" action="/catalog" method="get" role="search">
        <label class="sr-only" for="globalSearch">Search products</label>
        <span class="search-overlay__icon"><?= icon('search') ?></span>
        <input id="globalSearch" type="search" name="q" placeholder="Search extinguishers, hose reels, valves…" autocomplete="off">
        <button class="btn btn--primary" type="submit">Search</button>
        <button class="search-overlay__close icon-btn" type="button" data-search-close aria-label="Close search"><?= icon('close') ?></button>
    </form>
</div>
<script src="<?= e(asset('assets/js/store.js')) ?>" defer></script>
</body>
</html>

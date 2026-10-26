<?php
/**
 * Local development router for the PHP built-in server.
 *
 *   php -S localhost:8080 dev-router.php
 *
 * Existing static files (the marketing site, images, CSS) are served as-is.
 * Everything else is handed to the store front controller.
 */
declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = rawurldecode($path);
$path = '/' . ltrim($path, '/');

/* Mirror the production security headers from .htaccess during local testing. */
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data: https://www.google-analytics.com https://www.googletagmanager.com; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com; script-src 'self' https://www.googletagmanager.com; font-src 'self' data: https://cdnjs.cloudflare.com https://fonts.gstatic.com; connect-src 'self' https://www.google-analytics.com https://region1.google-analytics.com https://analytics.google.com https://www.googletagmanager.com; form-action 'self'; base-uri 'self'; frame-ancestors 'self'");

$root = str_replace('\\', '/', __DIR__);

/* Never expose application source, storage or configuration through the dev server. */
$blocked = ['/app', '/bin', '/database', '/docs', '/legacy', '/storage', '/config.local.php', '/config.example.php', '/dev-router.php'];
foreach ($blocked as $prefix) {
    if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Not found';
        return;
    }
}

if (preg_match('#(^|/)\.#', $path)) {
    http_response_code(404);
    return;
}

/* Mirror the production 301s for legacy marketing URLs during local testing. */
$categories = ['extinguisher', 'hose-reel-drums', 'hose-boxes-and-fire-shaft-door', 'fire-hose',
    'breeching-inlet', 'landing-valve', 'sprinkler', 'fire-hose-delivery-coupling', 'branch-pipe-nozzle'];
$legacyRedirects = [
    '/index.html' => '/',
    '/about-us.html' => '/about',
    '/pages/about-us.html' => '/about',
    '/contact-us.html' => '/contact',
    '/pages/contact-us.html' => '/contact',
    '/products.html' => '/catalog',
    '/pages/products.html' => '/catalog',
    '/privacy-policy.html' => '/privacy',
    '/pages/privacy-policy.html' => '/privacy',
    '/refund-cancellation-policy.html' => '/refund-policy',
    '/pages/refund-cancellation-policy.html' => '/refund-policy',
    '/testimonial.html' => '/about#clients',
    '/pages/testimonial.html' => '/about#clients',
    '/pages/404.html' => '/',
];
foreach ($categories as $category) {
    $legacyRedirects['/' . $category . '.html'] = '/catalog/' . $category;
    $legacyRedirects['/products/' . $category . '.html'] = '/catalog/' . $category;
}
if (isset($legacyRedirects[$path])) {
    header('Location: ' . $legacyRedirects[$path], true, 301);
    return;
}

/* Serve real static files directly (Apache does this without the router). */
if ($path !== '/') {
    $candidate = realpath(__DIR__ . str_replace('/', DIRECTORY_SEPARATOR, $path));
    if ($candidate !== false
        && str_starts_with(str_replace('\\', '/', $candidate), $root . '/')
        && is_file($candidate)) {
        return false;
    }
}

/* Route to the store. A bare / opens the store home for local preview. */
$_GET['__route'] = $path === '/' ? 'home' : trim($path, '/');
require __DIR__ . '/store.php';

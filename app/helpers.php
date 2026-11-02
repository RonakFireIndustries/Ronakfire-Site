<?php

declare(strict_types=1);

use App\Auth;
use App\Cart;
use App\PortalAuth;

/* -------------------------------------------------------------------------
   Configuration & environment
   ------------------------------------------------------------------------- */

function config(string $path, mixed $default = null): mixed
{
    $value = $GLOBALS['rf_config'] ?? [];
    foreach (explode('.', $path) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }
    return $value;
}

function is_local(): bool
{
    return (config('app_env') ?? 'local') === 'local';
}

function is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    if (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') {
        return true;
    }
    return (string) ($_SERVER['SERVER_PORT'] ?? '') === '443';
}

function dev_login_enabled(): bool
{
    return is_local() && (bool) config('security.dev_login', true);
}

/* -------------------------------------------------------------------------
   Output / URLs
   ------------------------------------------------------------------------- */

function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    if (preg_match('#^(https?:)?//#i', $path)) {
        return $path;
    }
    return '/' . ltrim($path, '/');
}

function abs_url(string $path = ''): string
{
    return rtrim((string) config('app_url'), '/') . url($path);
}

function asset(string $path): string
{
    $file = BASE_PATH . '/' . ltrim($path, '/');
    $version = is_file($file) ? (string) filemtime($file) : '1';
    return url($path) . '?v=' . $version;
}

function redirect(string $path, int $status = 303): never
{
    header('Location: ' . url($path), true, $status);
    exit;
}

function current_path(): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    return '/' . trim($path, '/');
}

function is_active_nav(string ...$prefixes): bool
{
    $path = current_path();
    foreach ($prefixes as $prefix) {
        if ($prefix === '/' && $path === '/') {
            return true;
        }
        if ($prefix !== '/' && str_starts_with($path, $prefix)) {
            return true;
        }
    }
    return false;
}

function money(int $paise, bool $showDecimals = true): string
{
    $amount = $paise / 100;
    $formatted = number_format($amount, 2, '.', '');
    [$whole, $decimal] = explode('.', $formatted);

    $lastThree = substr($whole, -3);
    $rest = substr($whole, 0, -3);
    if ($rest !== '') {
        $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest) . ',';
    }

    $value = '₹' . $rest . $lastThree;
    if ($showDecimals && $decimal !== '00') {
        $value .= '.' . $decimal;
    }
    return $value;
}

function gst_label(int $basisPoints): string
{
    return rtrim(rtrim(number_format($basisPoints / 100, 2, '.', ''), '0'), '.') . '%';
}

function format_date(?string $value, string $format = 'd M Y'): string
{
    if (!$value) {
        return '—';
    }
    $time = strtotime($value);
    return $time ? date($format, $time) : '—';
}

function format_datetime(?string $value): string
{
    return format_date($value, 'd M Y, H:i');
}

/* -------------------------------------------------------------------------
   Request input
   ------------------------------------------------------------------------- */

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function input(string $key, mixed $default = null): mixed
{
    $value = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($value) ? trim($value) : $value;
}

function post(string $key, mixed $default = null): mixed
{
    $value = $_POST[$key] ?? $default;
    return is_string($value) ? trim($value) : $value;
}

function request_wants_json(): bool
{
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $requestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
    return str_contains($accept, 'application/json') || strtolower($requestedWith) === 'xmlhttprequest';
}

/* -------------------------------------------------------------------------
   CSRF
   ------------------------------------------------------------------------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function csrf_valid(?string $token): bool
{
    return is_string($token) && $token !== '' && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function require_csrf(): void
{
    $token = $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!csrf_valid(is_string($token) ? $token : null)) {
        if (request_wants_json()) {
            json_response(['ok' => false, 'message' => 'Your session expired. Refresh the page and try again.'], 419);
        }
        http_response_code(419);
        render('errors/419', ['pageTitle' => 'Session expired'], 'layout');
        exit;
    }
}

/* -------------------------------------------------------------------------
   Flash messages
   ------------------------------------------------------------------------- */

function flash(string $type, string $message): void
{
    $_SESSION['flashes'][] = ['type' => $type, 'message' => $message];
}

function flashes(): array
{
    $items = $_SESSION['flashes'] ?? [];
    unset($_SESSION['flashes']);
    return $items;
}

/* -------------------------------------------------------------------------
   Rendering
   ------------------------------------------------------------------------- */

function render(string $view, array $data = [], string $layout = 'layout'): void
{
    $viewFile = APP_PATH . '/views/' . $view . '.php';
    if (!is_file($viewFile)) {
        throw new RuntimeException('View not found: ' . $view);
    }

    extract($data, EXTR_SKIP);
    $pageTitle = $pageTitle ?? config('store.name');
    $metaDescription = $metaDescription ?? '';
    $bodyClass = $bodyClass ?? '';
    $canonical = $canonical ?? null;

    ob_start();
    require $viewFile;
    $content = (string) ob_get_clean();

    if ($layout === '') {
        echo $content;
        return;
    }

    $layoutFile = APP_PATH . '/views/layouts/' . $layout . '.php';
    if (!is_file($layoutFile)) {
        throw new RuntimeException('Layout not found: ' . $layout);
    }
    require $layoutFile;
}

function partial(string $name, array $data = []): void
{
    $file = APP_PATH . '/views/partials/' . $name . '.php';
    if (!is_file($file)) {
        return;
    }
    extract($data, EXTR_SKIP);
    require $file;
}

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

/* -------------------------------------------------------------------------
   Auth / cart shortcuts
   ------------------------------------------------------------------------- */

function current_customer(): ?array
{
    return Auth::customer();
}

function is_logged_in(): bool
{
    return Auth::customer() !== null;
}

function require_customer(): void
{
    if (!is_logged_in()) {
        $return = $_SERVER['REQUEST_URI'] ?? '/account';
        redirect('/login?return=' . urlencode($return));
    }
}

function cart_count(): int
{
    $customer = Auth::customer();
    if (!$customer) {
        return 0;
    }
    return Cart::count((int) $customer['id']);
}

function portal_login_url(string $return = '/account'): string
{
    return PortalAuth::loginUrl($return);
}

/* -------------------------------------------------------------------------
   Settings & input parsing
   ------------------------------------------------------------------------- */

function setting(string $key, mixed $default = null): mixed
{
    static $cache = [];
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }
    try {
        $value = db()->value('SELECT value FROM settings WHERE `key` = ?', [$key]);
    } catch (Throwable) {
        return $default;
    }
    if ($value === null) {
        return $default;
    }
    return $cache[$key] = $value;
}

function set_setting(string $key, string $value): void
{
    $existing = db()->one('SELECT `key` FROM settings WHERE `key` = ?', [$key]);
    if ($existing) {
        db()->update('settings', ['value' => $value], '`key` = :key', ['key' => $key]);
    } else {
        db()->insert('settings', ['key' => $key, 'value' => $value]);
    }
}

function store_min_order_paise(): int
{
    return (int) setting('min_order_paise', config('store.min_order_paise', 0));
}

function parse_money_to_paise(mixed $value): int
{
    $clean = preg_replace('/[^0-9.\-]/', '', (string) $value);
    if ($clean === '' || $clean === '-' || $clean === '.') {
        return 0;
    }
    return (int) round((float) $clean * 100);
}

function valid_gstin(string $gstin): bool
{
    return (bool) preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/', strtoupper($gstin));
}

/**
 * Indian states and union territories used by address forms.
 *
 * @return list<string>
 */
function indian_states(): array
{
    return [
        'Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar', 'Chhattisgarh',
        'Goa', 'Gujarat', 'Haryana', 'Himachal Pradesh', 'Jharkhand', 'Karnataka',
        'Kerala', 'Madhya Pradesh', 'Maharashtra', 'Manipur', 'Meghalaya',
        'Mizoram', 'Nagaland', 'Odisha', 'Punjab', 'Rajasthan', 'Sikkim',
        'Tamil Nadu', 'Telangana', 'Tripura', 'Uttar Pradesh', 'Uttarakhand',
        'West Bengal', 'Andaman and Nicobar Islands', 'Chandigarh',
        'Dadra and Nagar Haveli and Daman and Diu', 'Delhi', 'Jammu and Kashmir',
        'Ladakh', 'Lakshadweep', 'Puducherry',
    ];
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    return trim($text, '-');
}

function safe_return_path(mixed $value, string $fallback = '/account'): string
{
    if (!is_string($value) || $value === '') {
        return $fallback;
    }
    if (!str_starts_with($value, '/') || str_starts_with($value, '//') || str_contains($value, '\\')) {
        return $fallback;
    }
    return $value;
}

/* -------------------------------------------------------------------------
   Order status
   ------------------------------------------------------------------------- */

function order_status_label(string $status): string
{
    return [
        'placed' => 'Placed',
        'confirmed' => 'Confirmed',
        'invoiced' => 'Invoiced',
        'dispatched' => 'Dispatched',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
    ][$status] ?? ucfirst($status);
}

function order_status_class(string $status): string
{
    return 'status-' . preg_replace('/[^a-z0-9-]/', '', strtolower($status));
}

function order_statuses(): array
{
    return ['placed', 'confirmed', 'invoiced', 'dispatched', 'delivered', 'cancelled'];
}

/* -------------------------------------------------------------------------
   Specs helper
   ------------------------------------------------------------------------- */

function decode_json(?string $json, array $default = []): array
{
    if (!$json) {
        return $default;
    }
    $decoded = json_decode($json, true);
    return is_array($decoded) ? $decoded : $default;
}

function specs_from_text(string $text): array
{
    $specs = [];
    foreach (preg_split('/\r\n|\r|\n/', $text) as $line) {
        $line = trim($line);
        if ($line === '' || !str_contains($line, ':')) {
            continue;
        }
        [$label, $value] = explode(':', $line, 2);
        $label = trim($label);
        $value = trim($value);
        if ($label !== '' && $value !== '') {
            $specs[] = ['label' => $label, 'value' => $value];
        }
    }
    return $specs;
}

function specs_to_text(array $specs): string
{
    $lines = [];
    foreach ($specs as $spec) {
        $lines[] = ($spec['label'] ?? '') . ': ' . ($spec['value'] ?? '');
    }
    return implode("\n", $lines);
}

/* -------------------------------------------------------------------------
   Icons (inline SVG, no external icon font)
   ------------------------------------------------------------------------- */

function icon(string $name, string $class = ''): string
{
    $paths = [
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'user' => '<circle cx="12" cy="8" r="3.5"/><path d="M4.5 20a7.5 7.5 0 0 1 15 0"/>',
        'cart' => '<path d="M3 4h2l2.2 10.2a2 2 0 0 0 2 1.6h7.9a2 2 0 0 0 2-1.6L21 7H6"/><circle cx="10" cy="20" r="1.2"/><circle cx="18" cy="20" r="1.2"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'close' => '<path d="m6 6 12 12M18 6 6 18"/>',
        'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
        'chevron-right' => '<path d="m9 6 6 6-6 6"/>',
        'arrow-right' => '<path d="M4 12h15"/><path d="m13 6 6 6-6 6"/>',
        'check' => '<path d="m5 12 4.5 4.5L19 7"/>',
        'phone' => '<path d="M6.5 3.5 9 3l1.8 4.4-2 1.6a12 12 0 0 0 6.2 6.2l1.6-2L21 15l-.5 2.5A2.5 2.5 0 0 1 18 20C10 20 4 14 4 6a2.5 2.5 0 0 1 2.5-2.5Z"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/>',
        'download' => '<path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M4 20h16"/>',
        'shield' => '<path d="M12 3 5 6v6c0 4.5 3 7.5 7 9 4-1.5 7-4.5 7-9V6l-7-3Z"/><path d="m9 12 2 2 4-4"/>',
        'truck' => '<path d="M3 6h11v10H3z"/><path d="M14 9h4l3 3v4h-7z"/><circle cx="7" cy="18" r="1.6"/><circle cx="17" cy="18" r="1.6"/>',
        'package' => '<path d="m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3Z"/><path d="m4 7.5 8 4.5 8-4.5"/><path d="M12 12v9"/>',
        'lock' => '<rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
        'logout' => '<path d="M15 4h4v16h-4"/><path d="M11 8l-4 4 4 4"/><path d="M7 12h9"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'minus' => '<path d="M5 12h14"/>',
        'filter' => '<path d="M4 6h16M7 12h10M10 18h4"/>',
        'file' => '<path d="M6 3h8l4 4v14H6z"/><path d="M14 3v4h4"/><path d="M9 12h6M9 16h6"/>',
        'clock' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7v5l3 2"/>',
        'alert' => '<path d="M12 4 3 20h18L12 4Z"/><path d="M12 10v4"/><circle cx="12" cy="17" r=".8"/>',
        'edit' => '<path d="m4 16-1 5 5-1L20 8l-4-4L4 16Z"/><path d="m13 6 4 4"/>',
        'trash' => '<path d="M4 7h16"/><path d="M9 7V4h6v3"/><path d="M7 7l1 13h8l1-13"/>',
        'eye' => '<path d="M2.5 12S6 6 12 6s9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/>',
        'grid' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'users' => '<circle cx="9" cy="8" r="3"/><path d="M3 20a6 6 0 0 1 12 0"/><path d="M16 5a3 3 0 0 1 0 6"/><path d="M18 20a5 5 0 0 0-2-4"/>',
        'clipboard' => '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V3h6v1"/><path d="M9 9h6M9 13h6M9 17h4"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-2.8 2.8-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.6V22h-4v-.1a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1L4.2 17l.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.6-1H3v-4h.1a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9L4.2 7 7 4.2l.1.1a1.7 1.7 0 0 0 1.9.3 1.7 1.7 0 0 0 1-1.6V3h4v.1a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.3l.1-.1L19.8 7l-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.6 1h.1v4H21a1.7 1.7 0 0 0-1.6 1Z"/>',
        'spark' => '<path d="M12 3v4M12 17v4M3 12h4M17 12h4"/><path d="m5.6 5.6 2.8 2.8M15.6 15.6l2.8 2.8M18.4 5.6l-2.8 2.8M8.4 15.6l-2.8 2.8"/>',
    ];

    $path = $paths[$name] ?? $paths['file'];
    return '<svg class="icon ' . e($class) . '" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
}


if (!function_exists('guest_cart_id')) {
    function guest_cart_id(bool $create = true): string
    {
        if (!empty($_COOKIE['guest_cart_id'])) {
            $id = trim((string) $_COOKIE['guest_cart_id']);
            if (preg_match('/^[a-zA-Z0-9_-]{16,64}$/', $id)) {
                return $id;
            }
        }
        $id = bin2hex(random_bytes(16));
        if ($create) {
            $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
            @setcookie('guest_cart_id', $id, time() + 60 * 60 * 24 * 30, '/', '', $secure, true);
            $_COOKIE['guest_cart_id'] = $id;
        }
        return $id;
    }
}

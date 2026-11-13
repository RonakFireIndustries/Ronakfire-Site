<?php

declare(strict_types=1);

use App\Database;

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}
if (!defined('APP_PATH')) {
    define('APP_PATH', __DIR__);
}

if (is_file(BASE_PATH . '/vendor/autoload.php')) {
    require BASE_PATH . '/vendor/autoload.php';
}

$config = require __DIR__ . '/config.php';

if (is_file(BASE_PATH . '/config.local.php')) {
    $local = require BASE_PATH . '/config.local.php';
    if (is_array($local)) {
        $config = array_replace_recursive($config, $local);
    }
}

$GLOBALS['rf_config'] = $config;

require __DIR__ . '/helpers.php';
require_once __DIR__ . '/QuotePdf.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $file = APP_PATH . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

date_default_timezone_set('Asia/Kolkata');

$isLocal = ($config['app_env'] ?? 'local') === 'local';
error_reporting(E_ALL);
ini_set('display_errors', $isLocal ? '1' : '0');
ini_set('log_errors', '1');

if (PHP_SAPI !== 'cli') {
    $secure = is_https();
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name($config['security']['session_name'] ?? 'rf_store_session');
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

/**
 * Lazy database connection.
 */
function db(): Database
{
    static $db = null;
    if ($db === null) {
        $db = new Database(config('db'));
    }
    return $db;
}

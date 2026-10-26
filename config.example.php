<?php
/**
 * Copy this file to config.local.php and fill in production values.
 * config.local.php is git-ignored.
 */
return [
    'app_env' => 'production',
    'app_url' => 'https://ronakfire.com',

    'db' => [
        'driver' => 'mysql',
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'cpaneluser_ronak_store',
        'user' => 'cpaneluser_ronak',
        'password' => 'CHANGE_ME',
    ],

    'portal' => [
        // Shared secret used by rfipl.ronakfire.com to sign the customer JWT.
        'jwt_secret' => 'CHANGE_ME_TO_A_LONG_RANDOM_STRING',
        // Adjust if the portal uses different paths.
        'login_url' => 'https://rfipl.ronakfire.com/login',
        'logout_url' => 'https://rfipl.ronakfire.com/logout',
        'cookie' => 'rf_portal_token',
        'cookie_domain' => '.ronakfire.com',
    ],

    'store' => [
        'support_email' => 'sales@ronakfire.com',
        'order_email' => 'sales@ronakfire.com',
        'gstin' => '',
        'notify_orders' => true,
    ],

    'security' => [
        'dev_login' => false,
    ],
];

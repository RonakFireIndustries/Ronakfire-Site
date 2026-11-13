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
        // Use the exact names cPanel created (usually CPANELUSER_name).
        'name' => 'CPANELUSER_ronak_store',
        'user' => 'CPANELUSER_ronak',
        'password' => 'CHANGE_ME',
    ],

    'portal' => [
        // Leave empty to use the email + password login form on /login.
        // Only set this when rfipl.ronakfire.com is live and should own sign-in.
        'jwt_secret' => '',
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

    'mail' => [
        // cPanel sends through the local mail server; create the mailbox first.
        'driver' => 'mail',
        'from' => 'sales@ronakfire.com',
        'from_name' => 'Ronak Fire India',
    ],

    'security' => [
        'dev_login' => false,
    ],
];

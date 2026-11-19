<?php
/**
 * Default application configuration.
 *
 * Do not put production secrets here. Create config.local.php in the site root
 * (it is git-ignored) and return an array that overrides these values.
 */
return [
    'app_env' => 'local', // local | production
    'app_url' => 'http://localhost:8080',

    'db' => [
        'driver' => 'mysql', // sqlite | mysql
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'a17590e4_ronak_store',
        'user' => 'a17590e4_root',
        'password' => 'Predator@6565',
    ],

    'portal' => [
        'login_url' => 'https://rfipl.ronakfire.com/login',
        'logout_url' => 'https://rfipl.ronakfire.com/logout',
        'cookie' => 'rf_portal_token',
        'cookie_domain' => '',
        'jwt_secret' => '',
        'issuer' => 'rfipl.ronakfire.com',
        'audience' => 'ronakfire.com',
        'profile_url' => '', // optional fallback API, e.g. https://rfipl.ronakfire.com/api/me
    ],

    'store' => [
        'name' => 'Ronak Fire India Pvt. Ltd.',
        'short_name' => 'Ronak Fire',
        'support_email' => 'sales@ronakfire.com',
        'support_phone' => '+91 93239 47512',
        'support_phone_href' => '+919323947512',
        'address' => 'Shop No. 118, 1st Floor, Vasudev Sky High, Kanakia Rd, Om Ram Sagar Phase 1, Beverly Park, Mira Road East, Mira Bhayandar, Maharashtra 401105',
        'currency' => 'INR',
        'gstin' => '', // e.g. 27AABCR1234F1Z5
        'min_order_paise' => 500000, // ₹5,000
        'freight_note' => 'Freight and dispatch will be confirmed by our sales team.',
        'order_email' => 'sales@ronakfire.com',
        'notify_orders' => true,
    ],

    'security' => [
        'session_name' => 'rf_store_session',
        'dev_login' => true, // local only; ignored when app_env=production
        'admin_throttle_seconds' => 2,
    ],
];

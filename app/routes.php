<?php
/**
 * Store routes.
 *
 * Patterns are matched against the path with leading/trailing slashes removed.
 * The public front controller is store.php; Apache (or dev-router.php) sends
 * store URLs here with ?__route=…
 */
declare(strict_types=1);

require __DIR__ . '/controllers/store.php';
require __DIR__ . '/controllers/marketing.php';
require __DIR__ . '/controllers/admin.php';

/* -------------------------------------------------------------------------
   Marketing
   ------------------------------------------------------------------------- */
$router->get('home', 'ctrl_home');
$router->get('about', 'ctrl_about');
$router->get('contact', 'ctrl_contact');
$router->get('privacy', 'ctrl_privacy');
$router->get('refund-policy', 'ctrl_refund_policy');

/* -------------------------------------------------------------------------
   Storefront
   ------------------------------------------------------------------------- */
$router->get('shop', 'ctrl_shop');
$router->get('catalog', 'ctrl_catalog');
$router->get('catalog/{slug}', 'ctrl_catalog');
$router->get('product/{slug}', 'ctrl_product');

/* -------------------------------------------------------------------------
   Cart
   ------------------------------------------------------------------------- */
$router->get('cart', 'ctrl_cart');
$router->post('cart/add', 'ctrl_cart_add');
$router->post('cart/update', 'ctrl_cart_update');
$router->post('cart/remove', 'ctrl_cart_remove');

/* -------------------------------------------------------------------------
   Checkout & orders
   ------------------------------------------------------------------------- */
$router->get('checkout', 'ctrl_checkout');
$router->post('checkout', 'ctrl_checkout');
$router->get('order/{no}', 'ctrl_order');
$router->get('order/{no}/print', 'ctrl_order_print');

/* -------------------------------------------------------------------------
   Customer account
   ------------------------------------------------------------------------- */
$router->get('account', 'ctrl_account');
$router->get('account/orders', 'ctrl_account_orders');
$router->get('account/enquiries', 'ctrl_account_enquiries');

/* -------------------------------------------------------------------------
   Quote & auth
   ------------------------------------------------------------------------- */
$router->get('quote', 'ctrl_quote');
$router->post('quote', 'ctrl_quote');
$router->get('login', 'ctrl_login');
$router->post('login/dev', 'ctrl_login_dev');
$router->get('logout', 'ctrl_logout');
$router->post('logout', 'ctrl_logout');

/* -------------------------------------------------------------------------
   Admin
   ------------------------------------------------------------------------- */
$router->get('admin', 'ctrl_admin_dashboard');
$router->get('admin/login', 'ctrl_admin_login');
$router->post('admin/login', 'ctrl_admin_login');
$router->post('admin/logout', 'ctrl_admin_logout');

$router->get('admin/products', 'ctrl_admin_products');
$router->get('admin/products/new', 'ctrl_admin_product_form');
$router->get('admin/products/{id}/edit', 'ctrl_admin_product_form');
$router->post('admin/products/save', 'ctrl_admin_product_save');
$router->post('admin/products/{id}/toggle', 'ctrl_admin_product_toggle');

$router->get('admin/categories', 'ctrl_admin_categories');
$router->post('admin/categories/save', 'ctrl_admin_categories_save');

$router->get('admin/orders', 'ctrl_admin_orders');
$router->get('admin/orders/{id}', 'ctrl_admin_order');
$router->post('admin/orders/{id}/status', 'ctrl_admin_order_status');
$router->post('admin/orders/{id}/freight', 'ctrl_admin_order_freight');
$router->get('admin/orders/{id}/invoice', 'ctrl_admin_order_invoice');

$router->get('admin/customers', 'ctrl_admin_customers');
$router->get('admin/customers/{id}', 'ctrl_admin_customer');
$router->post('admin/customers/{id}/save', 'ctrl_admin_customer_save');

$router->get('admin/enquiries', 'ctrl_admin_enquiries');
$router->get('admin/enquiries/{id}', 'ctrl_admin_enquiry');
$router->post('admin/enquiries/{id}/status', 'ctrl_admin_enquiry_status');

$router->get('admin/settings', 'ctrl_admin_settings');
$router->post('admin/settings', 'ctrl_admin_settings_save');
$router->get('admin/account', 'ctrl_admin_account');
$router->post('admin/account/password', 'ctrl_admin_password');

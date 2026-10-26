<?php

declare(strict_types=1);

use App\Auth;
use App\Cart;
use App\Catalog;
use App\PortalAuth;
use App\Pricing;

/* -------------------------------------------------------------------------
   Storefront
   ------------------------------------------------------------------------- */

function ctrl_shop(): void
{
    $categories = Catalog::categories();
    $featured = Catalog::featured(6);
    $counts = Catalog::countsByCategory();

    render('shop', [
        'pageTitle' => 'Fire Safety Equipment Store | Ronak Fire India',
        'metaDescription' => 'Configure and order fire extinguishers, hose reel drums, hose boxes, hydrant valves and fire safety equipment directly from Ronak Fire India. B2B pricing for verified customers.',
        'categories' => $categories,
        'featured' => $featured,
        'counts' => $counts,
        'activeNav' => '/shop',
    ]);
}

function ctrl_catalog(array $params = []): void
{
    $categorySlug = $params['slug'] ?? (string) input('category', '');
    $activeCategory = $categorySlug !== '' ? Catalog::findCategory($categorySlug) : null;
    if ($categorySlug !== '' && !$activeCategory) {
        http_response_code(404);
        render('errors/404', ['pageTitle' => 'Category not found'], 'layout');
        return;
    }

    $filters = [
        'category' => $categorySlug,
        'q' => (string) input('q', ''),
        'sort' => (string) input('sort', ''),
        'configurable' => input('configurable') ? 1 : null,
    ];

    $total = Catalog::count($filters);
    $perPage = 12;
    $page = max(1, (int) input('page', 1));
    $pages = max(1, (int) ceil($total / $perPage));
    $page = min($page, $pages);

    $products = Catalog::products($filters + ['limit' => $perPage, 'offset' => ($page - 1) * $perPage]);
    $categories = Catalog::categories();
    $counts = Catalog::countsByCategory();

    $title = $activeCategory ? $activeCategory['name'] : 'All Fire Safety Products';
    render('catalog', [
        'pageTitle' => $title . ' | Ronak Fire Store',
        'metaDescription' => $activeCategory['tagline'] ?? 'Browse the Ronak Fire India range of firefighting equipment. Filter by category, compare specifications and configure products to your project.',
        'products' => $products,
        'categories' => $categories,
        'counts' => $counts,
        'activeCategory' => $activeCategory,
        'filters' => $filters,
        'total' => $total,
        'page' => $page,
        'pages' => $pages,
        'activeNav' => '/catalog',
    ]);
}

function ctrl_product(array $params): void
{
    $product = Catalog::find((string) ($params['slug'] ?? ''));
    if (!$product) {
        http_response_code(404);
        render('errors/404', ['pageTitle' => 'Product not found'], 'layout');
        return;
    }

    $groups = Catalog::optionGroups((int) $product['id']);
    $bundleItems = Catalog::bundleItems((int) $product['id']);
    $related = Catalog::related($product, 4);
    $specs = decode_json($product['specs_json']);
    $gallery = decode_json($product['gallery_json']);
    if (!$gallery) {
        $gallery = [$product['image']];
    }

    $customer = current_customer();
    $initialQuote = Pricing::quote($product, [], $customer);

    render('product', [
        'pageTitle' => ($product['meta_title'] ?: $product['name'] . ' | Ronak Fire') . '',
        'metaDescription' => $product['meta_description'] ?: $product['short_description'],
        'canonical' => abs_url('/product/' . $product['slug']),
        'bodyClass' => 'page-product',
        'product' => $product,
        'groups' => $groups,
        'bundleItems' => $bundleItems,
        'related' => $related,
        'specs' => $specs,
        'gallery' => $gallery,
        'initialQuote' => $initialQuote,
        'activeNav' => '/catalog',
    ]);
}

/* -------------------------------------------------------------------------
   Cart
   ------------------------------------------------------------------------- */

function ctrl_cart(): void
{
    require_customer();
    $customer = current_customer();
    $items = Cart::items((int) $customer['id']);
    $totals = Cart::totals($items);

    render('cart', [
        'pageTitle' => 'Your Order | Ronak Fire Store',
        'bodyClass' => 'page-cart',
        'items' => $items,
        'totals' => $totals,
        'minOrder' => store_min_order_paise(),
        'activeNav' => '/cart',
    ]);
}

function ctrl_cart_add(): void
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    $wantsJson = request_wants_json() || str_contains($contentType, 'application/json');

    if (!is_logged_in()) {
        if ($wantsJson) {
            json_response([
                'ok' => false,
                'message' => 'Please sign in with your customer account to see pricing and order.',
                'login_url' => url('/login?return=' . urlencode(current_path())),
            ], 401);
        }
        flash('info', 'Sign in to add products to your order.');
        redirect('/login?return=' . urlencode(current_path()));
    }
    require_csrf();

    $payload = $_POST;
    if (str_contains($contentType, 'application/json')) {
        $decoded = json_decode(file_get_contents('php://input') ?: '', true);
        if (is_array($decoded)) {
            $payload = $decoded;
        }
    }

    $productId = (int) ($payload['product_id'] ?? 0);
    $qty = (int) ($payload['qty'] ?? 1);
    $selection = is_array($payload['selection'] ?? null) ? $payload['selection'] : [];

    if ($productId <= 0) {
        if ($wantsJson) {
            json_response(['ok' => false, 'message' => 'Choose a product before adding it to your order.'], 422);
        }
        flash('error', 'Choose a product before adding it to your order.');
        redirect('/catalog');
    }

    $customer = current_customer();
    $result = Cart::add((int) $customer['id'], $productId, $qty, $selection);

    if ($wantsJson) {
        json_response($result, $result['ok'] ? 200 : 422);
    }

    flash($result['ok'] ? 'success' : 'error', $result['message']);
    $referer = parse_url((string) ($_SERVER['HTTP_REFERER'] ?? ''), PHP_URL_PATH);
    $back = is_string($referer) && str_starts_with($referer, '/') ? $referer : '/cart';
    redirect($back);
}

function ctrl_cart_update(): void
{
    require_customer();
    require_csrf();
    $customer = current_customer();
    $itemId = (int) post('item_id', 0);
    $qty = (int) post('qty', 1);
    if (Cart::updateQty((int) $customer['id'], $itemId, $qty)) {
        flash('success', 'Quantity updated.');
    } else {
        flash('error', 'That item could not be updated.');
    }
    redirect('/cart');
}

function ctrl_cart_remove(): void
{
    require_customer();
    require_csrf();
    $customer = current_customer();
    $itemId = (int) post('item_id', 0);
    if (Cart::remove((int) $customer['id'], $itemId)) {
        flash('success', 'Item removed from your order.');
    }
    redirect('/cart');
}

/* -------------------------------------------------------------------------
   Checkout & orders
   ------------------------------------------------------------------------- */

function checkout_form_values(?array $customer): array
{
    return [
        'company_name' => $customer['company_name'] ?? '',
        'contact_name' => $customer['contact_name'] ?? '',
        'email' => $customer['email'] ?? '',
        'phone' => $customer['phone'] ?? '',
        'gstin' => $customer['gstin'] ?? '',
        'address_line1' => $customer['address_line1'] ?? '',
        'address_line2' => $customer['address_line2'] ?? '',
        'city' => $customer['city'] ?? '',
        'state' => $customer['state'] ?? '',
        'pincode' => $customer['pincode'] ?? '',
        'po_number' => '',
        'notes' => '',
    ];
}

function ctrl_checkout(): void
{
    require_customer();
    $customer = current_customer();
    $items = Cart::items((int) $customer['id']);
    if (!$items) {
        flash('info', 'Your order is empty. Add a product to continue.');
        redirect('/catalog');
    }

    $totals = Cart::totals($items);
    $minOrder = store_min_order_paise();
    $errors = [];
    $form = checkout_form_values($customer);
    $shipSame = true;
    $shipForm = [];

    if (is_post()) {
        require_csrf();
        foreach (array_keys($form) as $key) {
            $form[$key] = trim((string) post($key, $form[$key]));
        }
        $shipSame = (bool) post('ship_same', 0);
        $shipForm = [
            'company_name' => trim((string) post('ship_company_name', '')),
            'contact_name' => trim((string) post('ship_contact_name', '')),
            'phone' => trim((string) post('ship_phone', '')),
            'address_line1' => trim((string) post('ship_address_line1', '')),
            'address_line2' => trim((string) post('ship_address_line2', '')),
            'city' => trim((string) post('ship_city', '')),
            'state' => trim((string) post('ship_state', '')),
            'pincode' => trim((string) post('ship_pincode', '')),
        ];

        if ($form['company_name'] === '') {
            $errors['company_name'] = 'Enter the billing company name.';
        }
        if ($form['contact_name'] === '') {
            $errors['contact_name'] = 'Enter a contact name.';
        }
        if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a valid email address.';
        }
        if (strlen(preg_replace('/\D/', '', $form['phone'])) < 10) {
            $errors['phone'] = 'Enter a phone number with at least 10 digits.';
        }
        if ($form['gstin'] !== '' && !valid_gstin($form['gstin'])) {
            $errors['gstin'] = 'Enter a valid 15-character GSTIN, or leave it blank.';
        }
        if ($form['address_line1'] === '') {
            $errors['address_line1'] = 'Enter the billing address.';
        }
        if ($form['city'] === '') {
            $errors['city'] = 'Enter the city.';
        }
        if ($form['state'] === '') {
            $errors['state'] = 'Enter the state.';
        }
        if (!preg_match('/^\d{6}$/', $form['pincode'])) {
            $errors['pincode'] = 'Enter a 6-digit PIN code.';
        }
        if (!$shipSame) {
            if ($shipForm['address_line1'] === '' || $shipForm['city'] === '' || $shipForm['state'] === '' || !preg_match('/^\d{6}$/', $shipForm['pincode'])) {
                $errors['shipping'] = 'Complete the delivery address.';
            }
        }
        if (!post('terms')) {
            $errors['terms'] = 'Please accept the order terms.';
        }
        if ($totals['subtotal_paise'] < $minOrder) {
            $errors['minimum'] = 'The minimum order value is ' . money($minOrder) . ' (excluding GST).';
        }

        if (!$errors) {
            $shipping = $shipSame ? [
                'company_name' => $form['company_name'],
                'contact_name' => $form['contact_name'],
                'phone' => $form['phone'],
                'address_line1' => $form['address_line1'],
                'address_line2' => $form['address_line2'],
                'city' => $form['city'],
                'state' => $form['state'],
                'pincode' => $form['pincode'],
            ] : $shipForm;

            $now = date('Y-m-d H:i:s');
            $orderNo = null;
            for ($i = 0; $i < 8; $i++) {
                $candidate = 'RF-' . date('Ymd') . '-' . random_int(100000, 999999);
                if (!db()->one('SELECT id FROM orders WHERE order_no = ?', [$candidate])) {
                    $orderNo = $candidate;
                    break;
                }
            }
            if ($orderNo === null) {
                $errors['general'] = 'Could not allocate an order number. Please try again.';
            } else {
                try {
                    $orderId = db()->transaction(function () use ($orderNo, $customer, $form, $shipping, $totals, $items, $now): int {
                        $orderId = db()->insert('orders', [
                            'order_no' => $orderNo,
                            'customer_id' => (int) $customer['id'],
                            'status' => 'placed',
                            'po_number' => $form['po_number'],
                            'notes' => $form['notes'],
                            'subtotal_paise' => $totals['subtotal_paise'],
                            'gst_paise' => $totals['gst_paise'],
                            'freight_paise' => 0,
                            'grand_total_paise' => $totals['total_paise'],
                            'billing_json' => json_encode($form, JSON_UNESCAPED_UNICODE),
                            'shipping_json' => json_encode($shipping, JSON_UNESCAPED_UNICODE),
                            'placed_at' => $now,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);

                        foreach ($items as $item) {
                            db()->insert('order_items', [
                                'order_id' => $orderId,
                                'product_id' => (int) $item['product_id'],
                                'product_name' => $item['name'],
                                'sku' => $item['sku'],
                                'options_json' => $item['options_json'],
                                'qty' => (int) $item['qty'],
                                'unit_price_paise' => (int) $item['unit_price_paise'],
                                'gst_rate_bp' => (int) $item['gst_rate_bp'],
                                'line_subtotal_paise' => (int) $item['line_subtotal_paise'],
                                'line_gst_paise' => (int) $item['line_gst_paise'],
                                'line_total_paise' => (int) $item['line_total_paise'],
                            ]);
                        }

                        Cart::clear((int) $customer['id']);
                        return $orderId;
                    });

                    notify_order_placed($orderId);
                    redirect('/order/' . $orderNo . '?placed=1');
                } catch (Throwable $e) {
                    error_log('Order creation failed: ' . $e->getMessage());
                    $errors['general'] = 'We could not place the order. Please try again or call our sales team.';
                }
            }
        }
    }

    render('checkout', [
        'pageTitle' => 'Checkout | Ronak Fire Store',
        'bodyClass' => 'page-checkout',
        'items' => $items,
        'totals' => $totals,
        'minOrder' => $minOrder,
        'customer' => $customer,
        'form' => $form,
        'errors' => $errors,
        'shipSame' => $shipSame,
        'shipForm' => $shipForm,
        'activeNav' => '/cart',
    ]);
}

function find_order_for_customer(string $orderNo, int $customerId): ?array
{
    return db()->one(
        'SELECT o.*, c.company_name, c.customer_code, c.contact_name AS customer_contact, c.email AS customer_email, c.phone AS customer_phone, c.gstin AS customer_gstin
         FROM orders o JOIN customers c ON c.id = o.customer_id
         WHERE o.order_no = ? AND o.customer_id = ?',
        [$orderNo, $customerId]
    );
}

function ctrl_order(array $params): void
{
    require_customer();
    $customer = current_customer();
    $order = find_order_for_customer((string) ($params['no'] ?? ''), (int) $customer['id']);
    if (!$order) {
        http_response_code(404);
        render('errors/404', ['pageTitle' => 'Order not found'], 'layout');
        return;
    }

    $items = db()->all('SELECT * FROM order_items WHERE order_id = ? ORDER BY id', [$order['id']]);
    foreach ($items as &$item) {
        $item['options'] = decode_json($item['options_json']);
    }
    unset($item);

    render('order', [
        'pageTitle' => 'Order ' . $order['order_no'] . ' | Ronak Fire',
        'bodyClass' => 'page-order',
        'order' => $order,
        'items' => $items,
        'billing' => decode_json($order['billing_json']),
        'shipping' => decode_json($order['shipping_json']),
        'justPlaced' => isset($_GET['placed']),
        'activeNav' => '/account',
    ]);
}

function ctrl_order_print(array $params): void
{
    require_customer();
    $customer = current_customer();
    $order = find_order_for_customer((string) ($params['no'] ?? ''), (int) $customer['id']);
    if (!$order) {
        http_response_code(404);
        exit('Order not found');
    }
    $items = db()->all('SELECT * FROM order_items WHERE order_id = ? ORDER BY id', [$order['id']]);
    render('invoice', [
        'pageTitle' => 'Proforma invoice ' . $order['order_no'],
        'order' => $order,
        'items' => $items,
        'billing' => decode_json($order['billing_json']),
        'shipping' => decode_json($order['shipping_json']),
        'proforma' => true,
    ], 'print');
}

/* -------------------------------------------------------------------------
   Account
   ------------------------------------------------------------------------- */

function ctrl_account(): void
{
    require_customer();
    $customer = current_customer();
    $orders = db()->all(
        'SELECT * FROM orders WHERE customer_id = ? ORDER BY placed_at DESC, id DESC LIMIT 5',
        [$customer['id']]
    );
    $enquiries = db()->all(
        'SELECT * FROM enquiries WHERE customer_id = ? ORDER BY created_at DESC LIMIT 4',
        [$customer['id']]
    );
    $stats = [
        'orders' => (int) db()->value('SELECT COUNT(*) FROM orders WHERE customer_id = ?', [$customer['id']]),
        'open' => (int) db()->value("SELECT COUNT(*) FROM orders WHERE customer_id = ? AND status IN ('placed','confirmed','invoiced','dispatched')", [$customer['id']]),
        'value' => (int) db()->value("SELECT COALESCE(SUM(grand_total_paise), 0) FROM orders WHERE customer_id = ? AND status <> 'cancelled'", [$customer['id']]),
    ];

    render('account/dashboard', [
        'pageTitle' => 'Customer Account | Ronak Fire Store',
        'bodyClass' => 'page-account',
        'orders' => $orders,
        'enquiries' => $enquiries,
        'stats' => $stats,
        'activeNav' => '/account',
    ]);
}

function ctrl_account_orders(): void
{
    require_customer();
    $customer = current_customer();
    $orders = db()->all(
        'SELECT * FROM orders WHERE customer_id = ? ORDER BY placed_at DESC, id DESC',
        [$customer['id']]
    );
    render('account/orders', [
        'pageTitle' => 'Your Orders | Ronak Fire Store',
        'bodyClass' => 'page-account',
        'orders' => $orders,
        'activeNav' => '/account',
    ]);
}

function ctrl_account_enquiries(): void
{
    require_customer();
    $customer = current_customer();
    $enquiries = db()->all(
        'SELECT * FROM enquiries WHERE customer_id = ? ORDER BY created_at DESC',
        [$customer['id']]
    );
    render('account/enquiries', [
        'pageTitle' => 'Your Quote Requests | Ronak Fire Store',
        'bodyClass' => 'page-account',
        'enquiries' => $enquiries,
        'activeNav' => '/account',
    ]);
}

/* -------------------------------------------------------------------------
   Quote / enquiry
   ------------------------------------------------------------------------- */

function ctrl_quote(): void
{
    $product = null;
    $slug = (string) input('product', '');
    if ($slug !== '') {
        $product = Catalog::find($slug);
    }
    $customer = current_customer();
    $errors = [];
    $sent = isset($_GET['sent']);
    $form = [
        'name' => $customer['contact_name'] ?? '',
        'company' => $customer['company_name'] ?? '',
        'email' => $customer['email'] ?? '',
        'phone' => $customer['phone'] ?? '',
        'gstin' => $customer['gstin'] ?? '',
        'qty' => '1',
        'message' => '',
    ];

    if (is_post()) {
        require_csrf();
        foreach (array_keys($form) as $key) {
            $form[$key] = trim((string) post($key, $form[$key]));
        }
        $productId = (int) post('product_id', 0);
        if ($productId > 0) {
            $product = Catalog::findById($productId);
        }
        $configuration = trim((string) post('configuration', ''));
        $honeypot = trim((string) post('website', ''));

        if ($honeypot !== '') {
            $errors['general'] = 'Your request could not be submitted.';
        }
        if ($form['name'] === '') {
            $errors['name'] = 'Enter your name.';
        }
        if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a valid email address.';
        }
        if (strlen(preg_replace('/\D/', '', $form['phone'])) < 10) {
            $errors['phone'] = 'Enter a phone number with at least 10 digits.';
        }
        if ($form['gstin'] !== '' && !valid_gstin($form['gstin'])) {
            $errors['gstin'] = 'Enter a valid GSTIN or leave it blank.';
        }
        if ($form['message'] === '' && !$product) {
            $errors['message'] = 'Tell us what you need, or choose a product.';
        }

        if (!$errors) {
            $now = date('Y-m-d H:i:s');
            db()->insert('enquiries', [
                'customer_id' => $customer['id'] ?? null,
                'name' => $form['name'],
                'company' => $form['company'],
                'email' => $form['email'],
                'phone' => $form['phone'],
                'gstin' => strtoupper($form['gstin']),
                'product_id' => $product['id'] ?? null,
                'product_name' => $product['name'] ?? '',
                'qty' => max(1, (int) $form['qty']),
                'configuration' => $configuration,
                'message' => $form['message'],
                'status' => 'new',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            notify_enquiry($form, $product, $configuration, (int) $form['qty']);
            redirect('/quote?sent=1');
        }
    }

    render('quote', [
        'pageTitle' => 'Request a Quote | Ronak Fire Store',
        'metaDescription' => 'Send your specification, drawing or product requirement to the Ronak Fire sales team for a B2B quotation.',
        'bodyClass' => 'page-quote',
        'product' => $product,
        'form' => $form,
        'errors' => $errors,
        'sent' => $sent,
        'activeNav' => '/quote',
    ]);
}

/* -------------------------------------------------------------------------
   Auth
   ------------------------------------------------------------------------- */

function ctrl_login(): void
{
    $return = safe_return_path(input('return', '/account'));
    if (is_logged_in()) {
        redirect($return);
    }

    $portalError = (string) input('portal', '') === '1';
    $portalReady = (string) config('portal.jwt_secret', '') !== '';

    if (!dev_login_enabled() && $portalReady && !$portalError) {
        redirect(PortalAuth::loginUrl($return));
    }

    render('login', [
        'pageTitle' => 'Customer Login | Ronak Fire Store',
        'bodyClass' => 'page-login',
        'return' => $return,
        'portalError' => $portalError,
        'portalReady' => $portalReady,
        'activeNav' => '/login',
    ]);
}

function ctrl_login_dev(): void
{
    if (!dev_login_enabled()) {
        http_response_code(404);
        exit('Not found');
    }
    require_csrf();

    $email = strtolower(trim((string) post('email', '')));
    $name = trim((string) post('name', 'Demo Buyer'));
    $company = trim((string) post('company', 'Demo Fire Systems Pvt. Ltd.'));
    $return = safe_return_path(post('return', '/account'));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('error', 'Enter a valid email address for the demo login.');
        redirect('/login?return=' . urlencode($return));
    }

    $claims = [
        'sub' => 'dev-' . sha1($email),
        'email' => $email,
        'name' => $name,
        'company' => $company,
        'customer_code' => 'DEMO-' . strtoupper(substr(sha1($email), 0, 5)),
        'tier' => 'dealer',
        'gstin' => '',
    ];

    $customer = Auth::syncCustomer($claims);
    if (!$customer) {
        flash('error', 'The demo account could not be created.');
        redirect('/login');
    }

    flash('success', 'Signed in as ' . $customer['company_name'] . '.');
    redirect($return);
}

function ctrl_logout(): void
{
    Auth::logout();
    PortalAuth::clearCookie();

    if (!is_local() && (string) config('portal.logout_url', '') !== '') {
        redirect((string) config('portal.logout_url'));
    }
    flash('success', 'You have been signed out.');
    redirect('/shop');
}

/* -------------------------------------------------------------------------
   Notifications (best effort; failures never block an order)
   ------------------------------------------------------------------------- */

function notify_order_placed(int $orderId): void
{
    if (!config('store.notify_orders', false)) {
        return;
    }
    try {
        $order = db()->one('SELECT * FROM orders WHERE id = ?', [$orderId]);
        if (!$order) {
            return;
        }
        $customer = db()->one('SELECT * FROM customers WHERE id = ?', [$order['customer_id']]);
        $items = db()->all('SELECT * FROM order_items WHERE order_id = ?', [$orderId]);
        $lines = [];
        foreach ($items as $item) {
            $lines[] = $item['qty'] . ' × ' . $item['product_name'] . ' — ' . money((int) $item['line_total_paise']);
        }
        $body = "New store order\n\n"
            . 'Order: ' . $order['order_no'] . "\n"
            . 'Customer: ' . ($customer['company_name'] ?? '') . "\n"
            . 'Contact: ' . ($customer['contact_name'] ?? '') . ' / ' . ($customer['phone'] ?? '') . "\n"
            . 'Email: ' . ($customer['email'] ?? '') . "\n"
            . 'GSTIN: ' . ($customer['gstin'] ?? '') . "\n\n"
            . implode("\n", $lines) . "\n\n"
            . 'Subtotal: ' . money((int) $order['subtotal_paise']) . "\n"
            . 'GST: ' . money((int) $order['gst_paise']) . "\n"
            . 'Total: ' . money((int) $order['grand_total_paise']) . "\n\n"
            . 'Open in admin: ' . abs_url('/admin/orders/' . $orderId);

        @mail(
            (string) config('store.order_email', config('store.support_email')),
            'New store order ' . $order['order_no'],
            $body,
            'From: ' . config('store.support_email') . "\r\nContent-Type: text/plain; charset=UTF-8"
        );
    } catch (Throwable $e) {
        error_log('Order notification failed: ' . $e->getMessage());
    }
}

function notify_enquiry(array $form, ?array $product, string $configuration, int $qty): void
{
    if (!config('store.notify_orders', false)) {
        return;
    }
    try {
        $body = "New quote request\n\n"
            . 'Name: ' . $form['name'] . "\n"
            . 'Company: ' . $form['company'] . "\n"
            . 'Email: ' . $form['email'] . "\n"
            . 'Phone: ' . $form['phone'] . "\n"
            . 'GSTIN: ' . $form['gstin'] . "\n"
            . 'Product: ' . ($product['name'] ?? 'General enquiry') . "\n"
            . 'Quantity: ' . $qty . "\n"
            . 'Configuration: ' . $configuration . "\n\n"
            . $form['message'];
        @mail(
            (string) config('store.order_email', config('store.support_email')),
            'New quote request from ' . $form['company'],
            $body,
            'From: ' . config('store.support_email') . "\r\nContent-Type: text/plain; charset=UTF-8"
        );
    } catch (Throwable $e) {
        error_log('Enquiry notification failed: ' . $e->getMessage());
    }
}

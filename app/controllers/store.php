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
    $categorySlug = (string) input('category', '');
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
        'in_stock' => input('in_stock') ? 1 : null,
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
        'in_stock' => input('in_stock') ? 1 : null,
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
    $customer = current_customer();
    if (!$customer) {
        $guestId = guest_cart_id(true);
        $items = Cart::guestItems($guestId);
        $totals = Cart::guestTotals($items);
        render('cart', [
            'pageTitle' => 'Your Order | Ronak Fire Store',
            'bodyClass' => 'page-cart',
            'items' => $items,
            'totals' => $totals,
            'minOrder' => 0,
            'activeNav' => '/cart',
            'isGuest' => true,
        ]);
        return;
    }
    $items = Cart::items((int) $customer['id']);
    $totals = Cart::totals($items);

    render('cart', [
        'pageTitle' => 'Your Order | Ronak Fire Store',
        'bodyClass' => 'page-cart',
        'items' => $items,
        'totals' => $totals,
        'minOrder' => store_min_order_paise(),
        'activeNav' => '/cart',
        'isGuest' => false,
    ]);
}

function ctrl_cart_add(): void
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    $wantsJson = request_wants_json() || str_contains($contentType, 'application/json');

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
    if ($customer) {
        $result = Cart::add((int) $customer['id'], $productId, $qty, $selection);
    } else {
        $guestId = guest_cart_id(true);
        $result = Cart::guestAdd($guestId, $productId, $qty, $selection);
    }

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
    require_csrf();
    $customer = current_customer();
    if ($customer) {
        $itemId = (int) post('item_id', 0);
        $qty = (int) post('qty', 1);
        if (Cart::updateQty((int) $customer['id'], $itemId, $qty)) {
            flash('success', 'Quantity updated.');
        } else {
            flash('error', 'That item could not be updated.');
        }
    } else {
        $guestId = guest_cart_id(true);
        $itemId = (int) post('item_id', 0);
        $qty = (int) post('qty', 1);
        if (Cart::guestUpdateQty($guestId, $itemId, $qty)) {
            flash('success', 'Quantity updated.');
        } else {
            flash('error', 'That item could not be updated.');
        }
    }
    redirect('/cart');
}

function ctrl_cart_remove(): void
{
    require_csrf();
    $customer = current_customer();
    if ($customer) {
        $itemId = (int) post('item_id', 0);
        if (Cart::remove((int) $customer['id'], $itemId)) {
            flash('success', 'Item removed from your order.');
        }
    } else {
        $guestId = guest_cart_id(true);
        $itemId = (int) post('item_id', 0);
        if (Cart::guestRemove($guestId, $itemId)) {
            flash('success', 'Item removed from your order.');
        }
    }
    redirect('/cart');
}

/* -------------------------------------------------------------------------
   Cart → Quote request (replaces checkout/payment flow)
   ------------------------------------------------------------------------- */

function ctrl_cart_quote(): void
{
    if (is_post()) {
        require_csrf();
    }

    $customer = current_customer();
    if ($customer) {
        $items = Cart::items((int) $customer['id']);
    } else {
        $guestId = guest_cart_id(true);
        $items = Cart::guestItems($guestId);
    }
    if (!$items) {
        flash('info', 'Your order is empty. Add a product to continue.');
        redirect('/catalog');
    }

    $totals = $customer ? Cart::totals($items) : Cart::guestTotals($items);
    $errors = [];
    $form = [
        'company_name' => $customer['company_name'] ?? '',
        'contact_name' => $customer['contact_name'] ?? '',
        'email' => $customer['email'] ?? '',
        'phone' => $customer['phone'] ?? '',
        'gstin' => $customer['gstin'] ?? '',
        'po_number' => '',
        'notes' => '',
    ];

    if (is_post()) {
        foreach (array_keys($form) as $key) {
            $form[$key] = trim((string) post($key, $form[$key]));
        }

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

        if (!$errors) {
            $now = date('Y-m-d H:i:s');
            $enquiryId = null;
            try {
                $enquiryId = db()->transaction(function () use ($customer, $form, $items, $totals, $now): int {
                    $enquiryId = db()->insert('enquiries', [
                        'customer_id' => $customer['id'] ?? null,
                        'name' => $form['contact_name'],
                        'company' => $form['company_name'],
                        'email' => $form['email'],
                        'phone' => $form['phone'],
                        'gstin' => strtoupper($form['gstin']),
                        'product_id' => null,
                        'product_name' => 'Cart quote request',
                        'qty' => (int) $totals['item_count'],
                        'configuration' => json_encode([
                            'po_number' => $form['po_number'],
                            'notes' => $form['notes'],
                            'items' => array_map(function ($item) {
                                return [
                                    'product_id' => (int) $item['product_id'],
                                    'product_name' => $item['name'],
                                    'sku' => $item['sku'],
                                    'qty' => (int) $item['qty'],
                                    'unit_price_paise' => (int) $item['unit_price_paise'],
                                    'line_total_paise' => (int) $item['line_total_paise'],
                                    'options' => json_decode((string) $item['options_json'], true) ?? [],
                                ];
                            }, $items),
                        ], JSON_UNESCAPED_UNICODE),
                        'message' => trim($form['notes']),
                        'status' => 'new',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    // Generate PDF and send email to sales team with full cart details
                    $pdfPath = null;
                    try {
                        $pdfPath = QuotePdf::generate((int) $enquiryId, $customer ?? [], $form, $items, $totals);
                    } catch (Throwable $pdfErr) {
                        error_log('Quote PDF generation failed: ' . $pdfErr->getMessage());
                    }
                    notify_cart_quote((int) $enquiryId, $customer ?? [], $form, $items, $totals, $pdfPath);

                    if ($customer) {
                        Cart::clear((int) $customer['id']);
                    } else {
                        Cart::guestClear(guest_cart_id(true));
                    }
                    return (int) $enquiryId;
                });

                flash('success', 'Your requirement has been sent to our sales team. We will reply with pricing and lead time.');
                if ($customer) {
                    redirect('/account/enquiries');
                }
                redirect('/quote?sent=1');
            } catch (Throwable $e) {
                error_log('Cart quote failed: ' . $e->getMessage());
                $errors['general'] = 'We could not send your request. Please try again or call our sales team.';
            }
        }
    }

    render('cart-quote', [
        'pageTitle' => 'Request Quote from Cart | Ronak Fire Store',
        'bodyClass' => 'page-cart-quote',
        'items' => $items,
        'totals' => $totals,
        'customer' => $customer ?? [],
        'form' => $form,
        'errors' => $errors,
        'activeNav' => '/cart',
    ]);
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

function ctrl_order_reorder(array $params): void
{
    require_customer();
    require_csrf();
    $customer = current_customer();
    $order = find_order_for_customer((string) ($params['no'] ?? ''), (int) $customer['id']);
    if (!$order) {
        http_response_code(404);
        exit('Order not found');
    }

    $items = db()->all('SELECT * FROM order_items WHERE order_id = ? ORDER BY id', [$order['id']]);
    $added = 0;
    $skipped = 0;
    foreach ($items as $item) {
        if (!(int) $item['product_id']) {
            $skipped++;
            continue;
        }
        $options = decode_json($item['options_json']) ?? [];
        // Reconstruct selection from stored options
        $selection = ['groups' => [], 'dimensions' => []];
        foreach ($options['groups'] ?? [] as $g) {
            $selection['groups'][$g['slug']] = $g['value_id'];
        }
        foreach ($options['dimensions'] ?? [] as $d) {
            $selection['dimensions'][$d['slug']] = ['w' => $d['w'], 'h' => $d['h'], 'd' => $d['d']];
        }
        $result = Cart::add((int) $customer['id'], (int) $item['product_id'], (int) $item['qty'], $selection);
        if ($result['ok']) {
            $added++;
        } else {
            $skipped++;
        }
    }

    if ($added > 0) {
        $msg = $added . ' ' . ($added === 1 ? 'item' : 'items') . ' added to your order.';
        if ($skipped > 0) {
            $msg .= ' ' . $skipped . ' item(s) could not be added (product may no longer be available).';
        }
        flash('success', $msg);
    } else {
        flash('error', 'No items could be added. The products may no longer be available.');
    }
    redirect('/cart');
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

function ctrl_account_profile(): void
{
    require_customer();

    render('account/profile', [
        'pageTitle' => 'My details | Ronak Fire Store',
        'bodyClass' => 'page-account',
        'activeNav' => '/account',
    ]);
}

function ctrl_account_profile_save(): void
{
    require_customer();
    require_csrf();
    $customer = current_customer();
    $id = (int) $customer['id'];

    $data = [
        'contact_name' => trim((string) post('contact_name', '')),
        'phone' => trim((string) post('phone', '')),
        'address_line1' => trim((string) post('address_line1', '')),
        'address_line2' => trim((string) post('address_line2', '')),
        'city' => trim((string) post('city', '')),
        'state' => trim((string) post('state', '')),
        'pincode' => trim((string) post('pincode', '')),
    ];

    $errors = [];
    if (mb_strlen($data['contact_name']) > 191) {
        $errors[] = 'Contact name is too long.';
    }
    if ($data['phone'] !== '' && !preg_match('/^[0-9+\-\s().]{6,32}$/', $data['phone'])) {
        $errors[] = 'Enter a valid phone number.';
    }
    if (mb_strlen($data['address_line1']) > 255 || mb_strlen($data['address_line2']) > 255) {
        $errors[] = 'Address lines must be 255 characters or fewer.';
    }
    if (mb_strlen($data['city']) > 100) {
        $errors[] = 'City name is too long.';
    }
    if ($data['state'] !== '' && !in_array($data['state'], indian_states(), true)) {
        $errors[] = 'Select a valid state.';
    }
    if ($data['pincode'] !== '' && !preg_match('/^[0-9]{6}$/', $data['pincode'])) {
        $errors[] = 'Enter a valid 6-digit PIN code.';
    }

    $hasAddress = $data['address_line1'] !== ''
        || $data['city'] !== ''
        || $data['state'] !== ''
        || $data['pincode'] !== '';

    if ($hasAddress) {
        foreach (['address_line1' => 'street address', 'city' => 'city', 'state' => 'state', 'pincode' => 'PIN code'] as $field => $label) {
            if ($data[$field] === '') {
                $errors[] = 'Enter your ' . $label . '.';
            }
        }
    }

    if ($errors !== []) {
        flash('error', implode(' ', $errors));
        redirect('/account/profile');
    }

    $data['updated_at'] = date('Y-m-d H:i:s');
    db()->update('customers', $data, 'id = :id', ['id' => $id]);

    flash('success', 'Your contact and address details have been updated.');
    redirect('/account/profile');
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

function ctrl_account_enquiry_reorder(array $params): void
{
    require_customer();
    require_csrf();
    $customer = current_customer();
    $id = (int) ($params['id'] ?? 0);
    $enquiry = db()->one('SELECT * FROM enquiries WHERE id = ? AND customer_id = ?', [$id, (int) $customer['id']]);
    if (!$enquiry) {
        flash('error', 'Enquiry not found.');
        redirect('/account/enquiries');
    }
    if (empty($enquiry['product_id'])) {
        flash('error', 'This enquiry has no linked product to reorder.');
        redirect('/account/enquiries');
    }
    $productId = (int) $enquiry['product_id'];
    $qty = max(1, (int) ($enquiry['qty'] ?? 1));
    $config = $enquiry['configuration'] ? json_decode((string) $enquiry['configuration'], true) : [];
    if (!is_array($config)) {
        $config = [];
    }
    $result = \App\Cart::add((int) $customer['id'], $productId, $qty, $config);
    if ($result['ok']) {
        flash('success', 'Enquiry added to cart.');
        redirect('/cart');
        return;
    }
    flash('error', $result['message'] ?? 'Could not add to cart.');
    redirect('/account/enquiries');
}

/* -------------------------------------------------------------------------
   Saved addresses
   ------------------------------------------------------------------------- */

function ctrl_account_addresses(): void
{
    require_customer();
    $customer = current_customer();
    $addresses = db()->all(
        'SELECT * FROM customer_addresses WHERE customer_id = ? ORDER BY is_default DESC, id ASC',
        [(int) $customer['id']]
    );
    render('account/addresses', [
        'pageTitle' => 'Saved Addresses | Ronak Fire Store',
        'bodyClass' => 'page-account',
        'addresses' => $addresses,
        'activeNav' => '/account',
    ]);
}

function ctrl_account_address_save(): void
{
    require_customer();
    require_csrf();
    $customer = current_customer();
    $customerId = (int) $customer['id'];

    $stateList = ['Andhra Pradesh','Arunachal Pradesh','Assam','Bihar','Chhattisgarh','Goa','Gujarat','Haryana','Himachal Pradesh','Jharkhand','Karnataka','Kerala','Madhya Pradesh','Maharashtra','Manipur','Meghalaya','Mizoram','Nagaland','Odisha','Punjab','Rajasthan','Sikkim','Tamil Nadu','Telangana','Tripura','Uttar Pradesh','Uttarakhand','West Bengal','Andaman and Nicobar Islands','Chandigarh','Dadra and Nagar Haveli and Daman and Diu','Delhi','Jammu and Kashmir','Ladakh','Lakshadweep','Puducherry'];

    $id = (int) post('id', 0);
    $data = [
        'label'        => substr(trim((string) post('label', 'Default')), 0, 64) ?: 'Default',
        'company_name' => trim((string) post('company_name', '')),
        'contact_name' => trim((string) post('contact_name', '')),
        'phone'        => trim((string) post('phone', '')),
        'address_line1'=> trim((string) post('address_line1', '')),
        'address_line2'=> trim((string) post('address_line2', '')),
        'city'         => trim((string) post('city', '')),
        'state'        => trim((string) post('state', '')),
        'pincode'      => trim((string) post('pincode', '')),
    ];

    $errors = [];
    if ($data['address_line1'] === '') {
        $errors[] = 'Address line 1 is required.';
    }
    if ($data['city'] === '') {
        $errors[] = 'City is required.';
    }
    if (!in_array($data['state'], $stateList, true)) {
        $errors[] = 'Choose a valid state.';
    }
    if (!preg_match('/^\d{6}$/', $data['pincode'])) {
        $errors[] = 'Enter a 6-digit PIN code.';
    }

    if ($errors) {
        flash('error', implode(' ', $errors));
        redirect('/account/addresses');
    }

    if ($id > 0) {
        // Only allow editing own addresses
        $existing = db()->one('SELECT id FROM customer_addresses WHERE id = ? AND customer_id = ?', [$id, $customerId]);
        if (!$existing) {
            flash('error', 'Address not found.');
            redirect('/account/addresses');
        }
        db()->update('customer_addresses', $data, 'id = ? AND customer_id = ?', [$id, $customerId]);
        flash('success', 'Address updated.');
    } else {
        $data['customer_id'] = $customerId;
        $data['is_default']  = 0;
        $data['created_at']  = date('Y-m-d H:i:s');
        $newId = db()->insert('customer_addresses', $data);
        // If this is the first address, make it default
        $count = (int) db()->value('SELECT COUNT(*) FROM customer_addresses WHERE customer_id = ?', [$customerId]);
        if ($count === 1) {
            db()->update('customer_addresses', ['is_default' => 1], 'id = ?', [$newId]);
        }
        flash('success', 'Address saved.');
    }
    redirect('/account/addresses');
}

function ctrl_account_address_delete(array $params): void
{
    require_customer();
    require_csrf();
    $customer = current_customer();
    $customerId = (int) $customer['id'];
    $id = (int) ($params['id'] ?? 0);
    $row = db()->one('SELECT id, is_default FROM customer_addresses WHERE id = ? AND customer_id = ?', [$id, $customerId]);
    if (!$row) {
        flash('error', 'Address not found.');
        redirect('/account/addresses');
    }
    db()->exec('DELETE FROM customer_addresses WHERE id = ?', [$id]);
    // If we deleted the default, promote the oldest remaining one
    if ((int) $row['is_default'] === 1) {
        $next = db()->one('SELECT id FROM customer_addresses WHERE customer_id = ? ORDER BY id ASC LIMIT 1', [$customerId]);
        if ($next) {
            db()->update('customer_addresses', ['is_default' => 1], 'id = ?', [(int) $next['id']]);
        }
    }
    flash('success', 'Address removed.');
    redirect('/account/addresses');
}

function ctrl_account_address_default(array $params): void
{
    require_customer();
    require_csrf();
    $customer = current_customer();
    $customerId = (int) $customer['id'];
    $id = (int) ($params['id'] ?? 0);
    $row = db()->one('SELECT id FROM customer_addresses WHERE id = ? AND customer_id = ?', [$id, $customerId]);
    if (!$row) {
        flash('error', 'Address not found.');
        redirect('/account/addresses');
    }
    db()->exec('UPDATE customer_addresses SET is_default = 0 WHERE customer_id = ?', [$customerId]);
    db()->update('customer_addresses', ['is_default' => 1], 'id = ?', [$id]);
    flash('success', 'Default address updated.');
    redirect('/account/addresses');
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
            $enquiryId = (int) db()->insert('enquiries', [
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
            notify_enquiry($form, $product, $configuration, (int) $form['qty'], $enquiryId);
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

    if ($portalReady && !$portalError) {
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

function ctrl_login_password(): void
{
    if (is_logged_in()) {
        redirect(safe_return_path(input('return', '/account')));
    }

    require_csrf();

    $return = safe_return_path(post('return', '/account'));
    $email = strtolower(trim((string) post('email', '')));
    $password = (string) post('password', '');

    if ($email === '' || $password === '') {
        flash('error', 'Enter your email and password.');
        redirect('/login?return=' . urlencode($return));
    }

    $customer = db()->one(
        'SELECT * FROM customers WHERE email = ? AND is_active = 1',
        [$email]
    );

    if (!$customer || empty($customer['password_hash']) || !password_verify($password, (string) $customer['password_hash'])) {
        // Slow down repeated guesses without blocking legitimate retries.
        usleep(300000);
        flash('error', 'Those credentials do not match an active customer account.');
        redirect('/login?return=' . urlencode($return));
    }

    session_regenerate_id(true);
    $_SESSION['customer_id'] = (int) $customer['id'];
    db()->update(
        'customers',
        ['last_login_at' => date('Y-m-d H:i:s')],
        'id = :id',
        ['id' => (int) $customer['id']]
    );

    flash('success', 'Welcome back, ' . $customer['company_name'] . '.');
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

        // Admin notification (plain text fallback)
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

        // Customer confirmation email (HTML)
        if (!empty($customer['email'])) {
            try {
                $billing = decode_json($order['billing_json']);
                $shipping = decode_json($order['shipping_json']);
                ob_start();
                require APP_PATH . '/views/emails/order-placed.php';
                $html = (string) ob_get_clean();
                @mail(
                    (string) $customer['email'],
                    'Order confirmation ' . $order['order_no'] . ' — Ronak Fire India',
                    $html,
                    'From: ' . config('store.support_email') . "\r\nMIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8"
                );
            } catch (Throwable $e) {
                error_log('Customer order email failed: ' . $e->getMessage());
            }
        }
    } catch (Throwable $e) {
        error_log('Order notification failed: ' . $e->getMessage());
    }
}

function notify_order_status_changed(int $orderId, string $oldStatus, string $newStatus): void
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
        if (empty($customer['email'])) {
            return;
        }

        if ($newStatus === 'dispatched') {
            ob_start();
            require APP_PATH . '/views/emails/dispatch.php';
            $html = (string) ob_get_clean();
            @mail(
                (string) $customer['email'],
                'Your order ' . $order['order_no'] . ' has been dispatched — Ronak Fire India',
                $html,
                'From: ' . config('store.support_email') . "\r\nMIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8"
            );
            return;
        }

        $subject = 'Order ' . $order['order_no'] . ' status updated to ' . order_status_label($newStatus);
        $body = "Hello " . ($customer['contact_name'] ?: $customer['company_name']) . ",\n\n"
            . 'Your order ' . $order['order_no'] . ' status has been updated from ' . order_status_label($oldStatus) . ' to ' . order_status_label($newStatus) . ".\n\n"
            . 'View order: ' . abs_url('/order/' . $order['order_no']) . "\n\n"
            . 'Ronak Fire India Pvt. Ltd.';
        @mail(
            (string) $customer['email'],
            $subject,
            $body,
            'From: ' . config('store.support_email') . "\r\nContent-Type: text/plain; charset=UTF-8"
        );
    } catch (Throwable $e) {
        error_log('Order status notification failed: ' . $e->getMessage());
    }
}

/**
 * Send the customer a "we have your requirement" acknowledgement.
 *
 * Works for logged-in customers and guests: the address entered on the form
 * wins, then the account email. Safe to call when mail is disabled.
 */
function send_quote_ack(array $customer, array $form, array $items, ?array $totals = null, ?int $enquiryId = null): void
{
    if (!config('store.notify_orders', false)) {
        return;
    }
    try {
        $to = trim((string) ($form['email'] ?? ''));
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $to = trim((string) ($customer['email'] ?? ''));
        }
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        ob_start();
        require APP_PATH . '/views/emails/quote-acknowledgement.php';
        $html = (string) ob_get_clean();

        $subject = 'Your requirement is with our sales team — Ronak Fire India';
        @mail(
            $to,
            $subject,
            chunk_split(base64_encode($html)),
            'From: ' . config('store.support_email') . "\r\nMIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64"
        );
    } catch (Throwable $e) {
        error_log('Quote acknowledgement failed: ' . $e->getMessage());
    }
}

/**
 * Render the internal sales notification using the same layout as the
 * customer acknowledgement.
 */
function render_quote_notification(string $kind, array $form, array $customer, array $items, ?int $enquiryId, string $configuration = '', string $message = ''): string
{
    ob_start();
    try {
        require APP_PATH . '/views/emails/quote-notification.php';
        return (string) ob_get_clean();
    } catch (Throwable $e) {
        ob_end_clean();
        throw $e;
    }
}

function notify_enquiry(array $form, ?array $product, string $configuration, int $qty, ?int $enquiryId = null): void
{
    if (!config('store.notify_orders', false)) {
        return;
    }
    try {
        $items = [[
            'product_name' => $product['name'] ?? 'General enquiry',
            'sku' => $product['sku'] ?? '',
            'qty' => max(1, $qty),
        ]];

        $html = render_quote_notification(
            'enquiry',
            $form,
            [],
            $items,
            $enquiryId,
            $configuration,
            (string) ($form['message'] ?? '')
        );

        @mail(
            (string) config('store.order_email', config('store.support_email')),
            'New quote request from ' . $form['company'],
            chunk_split(base64_encode($html)),
            'From: ' . config('store.support_email') . "\r\nMIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64"
        );

        send_quote_ack([], $form, $items, null, $enquiryId);
    } catch (Throwable $e) {
        error_log('Enquiry notification failed: ' . $e->getMessage());
    }
}

function notify_cart_quote(int $enquiryId, array $customer, array $form, array $items, array $totals, ?string $pdfPath = null): void
{
    if (!config('store.notify_orders', false)) {
        return;
    }
    try {
        $html = render_quote_notification(
            'cart',
            $form,
            $customer,
            $items,
            $enquiryId,
            '',
            (string) ($form['notes'] ?? '')
        );

        $to = (string) config('store.order_email', config('store.support_email'));
        $from = (string) config('store.support_email');
        $subject = 'Cart quote request #' . $enquiryId . ' — ' . ($form['company_name'] ?? $customer['company_name'] ?? '');

        if ($pdfPath && is_file($pdfPath)) {
            $filename = 'Quote-RF-ENQ-' . $enquiryId . '.pdf';
            $fileContent = file_get_contents($pdfPath);
            if ($fileContent !== false) {
                $boundary = 'rf-' . md5((string) microtime());
                $headers = "From: " . $from . "\r\n"
                    . "MIME-Version: 1.0\r\n"
                    . "Content-Type: multipart/mixed; boundary=\"" . $boundary . "\"";
                $message = "--" . $boundary . "\r\n"
                    . "Content-Type: text/html; charset=UTF-8\r\n"
                    . "Content-Transfer-Encoding: base64\r\n\r\n"
                    . chunk_split(base64_encode($html)) . "\r\n"
                    . "--" . $boundary . "\r\n"
                    . "Content-Type: application/pdf; name=\"" . $filename . "\"\r\n"
                    . "Content-Transfer-Encoding: base64\r\n"
                    . "Content-Disposition: attachment; filename=\"" . $filename . "\"\r\n\r\n"
                    . chunk_split(base64_encode($fileContent)) . "\r\n"
                    . "--" . $boundary . "--";
                @mail($to, $subject, $message, $headers);
                // Still send customer ack
                send_quote_ack($customer, $form, $items, $totals, $enquiryId);
                return;
            }
        }

        @mail(
            $to,
            $subject,
            chunk_split(base64_encode($html)),
            "From: " . $from . "\r\nMIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64"
        );

        // Customer acknowledgement
        send_quote_ack($customer, $form, $items, $totals, $enquiryId);
    } catch (Throwable $e) {
        error_log('Cart quote notification failed: ' . $e->getMessage());
    }
}


function ctrl_account_inventory(): void
{
    require_customer();
    $customer = current_customer();
    render('account/inventory', [
        'pageTitle' => 'Inventory | Ronak Fire Store',
        'bodyClass' => 'page-account',
        'customer' => $customer,
        'activeNav' => '/account',
    ]);
}

function ctrl_account_documents(): void
{
    require_customer();
    $customer = current_customer();
    render('account/documents', [
        'pageTitle' => 'Documents | Ronak Fire Store',
        'bodyClass' => 'page-account',
        'customer' => $customer,
        'activeNav' => '/account',
    ]);
}

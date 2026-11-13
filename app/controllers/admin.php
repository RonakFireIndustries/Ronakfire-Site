<?php
/**
 * Store administration.
 *
 * All actions are session-authenticated (admin_users), CSRF protected and
 * server-side validated. Money is stored in paise, GST in basis points.
 */
declare(strict_types=1);

use App\Auth;
use App\Catalog;

/* -------------------------------------------------------------------------
   Shared helpers
   ------------------------------------------------------------------------- */

function admin_render(string $view, array $data = []): void
{
    render('admin/' . $view, array_merge([
        'admin' => Auth::admin(),
        'adminNav' => '',
    ], $data), 'admin');
}

function admin_url_id(array $params): int
{
    return max(0, (int) ($params['id'] ?? 0));
}

function admin_find_order(int $id): ?array
{
    return db()->one(
        'SELECT o.*, c.company_name, c.customer_code, c.contact_name AS customer_contact,
                c.email AS customer_email, c.phone AS customer_phone, c.gstin AS customer_gstin,
                c.price_tier, c.discount_bp
         FROM orders o JOIN customers c ON c.id = o.customer_id
         WHERE o.id = ?',
        [$id]
    );
}

function admin_unique_slug(string $table, string $slug, int $ignoreId = 0): string
{
    $base = slugify($slug) ?: 'item';
    $candidate = $base;
    $i = 2;
    while (true) {
        $row = db()->one("SELECT id FROM {$table} WHERE slug = ?", [$candidate]);
        if (!$row || (int) $row['id'] === $ignoreId) {
            return $candidate;
        }
        $candidate = $base . '-' . $i++;
    }
}

/**
 * Move an uploaded image into the public tree and return its URL path.
 * Returns null when no file was submitted.
 */
function admin_upload_image(string $field, string $prefix, string $subdir = 'images/uploads'): ?string
{
    $file = $_FILES[$field] ?? null;
    if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ((int) $file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The image upload failed. Please try again.');
    }
    if ((int) $file['size'] > 4 * 1024 * 1024) {
        throw new RuntimeException('Images must be 4 MB or smaller.');
    }

    $info = @getimagesize((string) $file['tmp_name']);
    $mime = is_array($info) ? ($info['mime'] ?? '') : '';
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    if (!isset($extensions[$mime])) {
        throw new RuntimeException('Use a JPG, PNG or WebP image.');
    }

    $dir = BASE_PATH . '/' . trim($subdir, '/');
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException('The image folder could not be created.');
    }

    $name = (slugify($prefix) ?: 'image') . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(3)) . '.' . $extensions[$mime];
    if (!move_uploaded_file((string) $file['tmp_name'], $dir . '/' . $name)) {
        throw new RuntimeException('The image could not be saved.');
    }

    return '/' . trim($subdir, '/') . '/' . $name;
}

function admin_money_input(mixed $value): string
{
    return number_format(((int) $value) / 100, 2, '.', '');
}

function admin_pagination(int $total, int $perPage, int $page): array
{
    $pages = max(1, (int) ceil($total / $perPage));
    $page = max(1, min($page, $pages));
    return [
        'page' => $page,
        'pages' => $pages,
        'offset' => ($page - 1) * $perPage,
        'total' => $total,
        'per_page' => $perPage,
    ];
}

/* -------------------------------------------------------------------------
   Authentication
   ------------------------------------------------------------------------- */

function ctrl_admin_login(): void
{
    if (Auth::admin()) {
        redirect('/admin');
    }

    $error = '';
    $email = '';

    if (is_post()) {
        require_csrf();
        $email = strtolower(trim((string) post('email', '')));
        $password = (string) post('password', '');

        $last = (int) ($_SESSION['admin_attempt_at'] ?? 0);
        $throttle = (int) config('security.admin_throttle_seconds', 2);
        $_SESSION['admin_attempt_at'] = time();

        if ($throttle > 0 && $last > 0 && time() - $last < $throttle) {
            $error = 'Please wait a moment before trying again.';
        } elseif ($email === '' || $password === '') {
            $error = 'Enter your email and password.';
        } elseif (!Auth::attemptAdmin($email, $password)) {
            $error = 'Those credentials do not match an active administrator.';
            error_log('Failed admin login for ' . $email . ' from ' . ($_SERVER['REMOTE_ADDR'] ?? ''));
        } else {
            unset($_SESSION['admin_attempt_at']);
            redirect('/admin');
        }
    }

    render('admin/login', [
        'pageTitle' => 'Administrator sign in | Ronak Fire Store',
        'error' => $error,
        'email' => $email,
    ], 'admin-auth');
}

function ctrl_admin_logout(): void
{
    require_csrf();
    Auth::logoutAdmin();
    flash('success', 'You have been signed out of the admin panel.');
    redirect('/admin/login');
}

/* -------------------------------------------------------------------------
   Dashboard
   ------------------------------------------------------------------------- */

function ctrl_admin_dashboard(): void
{
    Auth::requireAdmin();

    $since = date('Y-m-d H:i:s', time() - 30 * 86400);
    $stats = [
        'new_orders' => (int) db()->value("SELECT COUNT(*) FROM orders WHERE status = 'placed'"),
        'open_orders' => (int) db()->value("SELECT COUNT(*) FROM orders WHERE status IN ('placed','confirmed','invoiced','dispatched')"),
        'orders_30d' => (int) db()->value('SELECT COUNT(*) FROM orders WHERE placed_at >= ?', [$since]),
        'revenue_30d' => (int) db()->value('SELECT COALESCE(SUM(grand_total_paise),0) FROM orders WHERE placed_at >= ? AND status <> ?', [$since, 'cancelled']),
        'customers' => (int) db()->value('SELECT COUNT(*) FROM customers WHERE is_active = 1'),
        'new_enquiries' => (int) db()->value("SELECT COUNT(*) FROM enquiries WHERE status = 'new'"),
    ];

    $recentOrders = db()->all(
        'SELECT o.*, c.company_name FROM orders o JOIN customers c ON c.id = o.customer_id
         ORDER BY o.placed_at DESC, o.id DESC LIMIT 8'
    );
    $recentEnquiries = db()->all('SELECT * FROM enquiries ORDER BY created_at DESC, id DESC LIMIT 6');
    $topProducts = db()->all(
        "SELECT oi.product_name, oi.sku, SUM(oi.qty) AS qty, SUM(oi.line_total_paise) AS value
         FROM order_items oi JOIN orders o ON o.id = oi.order_id
         WHERE o.status <> 'cancelled'
         GROUP BY oi.product_name, oi.sku ORDER BY qty DESC LIMIT 6"
    );
    $statusRows = db()->all('SELECT status, COUNT(*) AS total FROM orders GROUP BY status');
    $statusCounts = [];
    foreach ($statusRows as $row) {
        $statusCounts[$row['status']] = (int) $row['total'];
    }

    admin_render('dashboard', [
        'pageTitle' => 'Dashboard | Ronak Fire admin',
        'adminNav' => 'dashboard',
        'adminTitle' => 'Dashboard',
        'stats' => $stats,
        'recentOrders' => $recentOrders,
        'recentEnquiries' => $recentEnquiries,
        'topProducts' => $topProducts,
        'statusCounts' => $statusCounts,
    ]);
}

/* -------------------------------------------------------------------------
   Products
   ------------------------------------------------------------------------- */

function ctrl_admin_products(): void
{
    Auth::requireAdmin();

    $filters = [
        'q' => trim((string) input('q', '')),
        'category' => trim((string) input('category', '')),
        'type' => trim((string) input('type', '')),
        'active' => trim((string) input('active', '')),
    ];
    $page = max(1, (int) input('page', 1));
    $perPage = 20;

    $where = '1';
    $params = [];
    if ($filters['q'] !== '') {
        $like = '%' . $filters['q'] . '%';
        $where .= ' AND (p.name LIKE ? OR p.sku LIKE ? OR p.slug LIKE ?)';
        array_push($params, $like, $like, $like);
    }
    if ($filters['category'] !== '') {
        $where .= ' AND c.slug = ?';
        $params[] = $filters['category'];
    }
    if ($filters['type'] !== '') {
        $where .= ' AND p.product_type = ?';
        $params[] = $filters['type'];
    }
    if ($filters['active'] !== '') {
        $where .= ' AND p.is_active = ?';
        $params[] = $filters['active'] === '1' ? 1 : 0;
    }

    $total = (int) db()->value(
        'SELECT COUNT(*) FROM products p JOIN categories c ON c.id = p.category_id WHERE ' . $where,
        $params
    );
    $pager = admin_pagination($total, $perPage, $page);

    $products = db()->all(
        'SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON c.id = p.category_id
         WHERE ' . $where . ' ORDER BY c.sort_order, p.sort_order, p.name
         LIMIT ' . $perPage . ' OFFSET ' . $pager['offset'],
        $params
    );

    admin_render('products', [
        'pageTitle' => 'Products | Ronak Fire admin',
        'adminNav' => 'products',
        'adminTitle' => 'Products',
        'products' => $products,
        'categories' => Catalog::categories(false),
        'filters' => $filters,
        'pager' => $pager,
    ]);
}

function ctrl_admin_product_form(array $params = []): void
{
    Auth::requireAdmin();

    $id = admin_url_id($params);
    $product = $id > 0 ? Catalog::findById($id) : null;
    if ($id > 0 && !$product) {
        http_response_code(404);
        admin_render('not-found', [
            'pageTitle' => 'Product not found',
            'adminNav' => 'products',
            'adminTitle' => 'Product not found',
        ]);
        return;
    }

    if (!$product) {
        $now = date('Y-m-d H:i:s');
        $product = [
            'id' => 0,
            'category_id' => 0,
            'slug' => '',
            'name' => '',
            'sku' => '',
            'product_type' => 'configurable',
            'short_description' => '',
            'description' => '',
            'specs_json' => '[]',
            'image' => '',
            'gallery_json' => '[]',
            'datasheet' => '',
            'base_price_paise' => 0,
            'dim_rate_paise_sqm' => 0,
            'price_note' => '',
            'unit' => 'Nos',
            'hsn_code' => '',
            'gst_rate_bp' => 1800,
            'min_qty' => 1,
            'lead_time' => '',
            'warranty' => '',
            'is_featured' => 0,
            'is_active' => 1,
            'sort_order' => 0,
            'meta_title' => '',
            'meta_description' => '',
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    admin_render('product-form', [
        'pageTitle' => ($id ? 'Edit' : 'New') . ' product | Ronak Fire admin',
        'adminNav' => 'products',
        'adminTitle' => $id ? 'Edit product' : 'New product',
        'product' => $product,
        'categories' => Catalog::categories(false),
        'groups' => $id ? Catalog::optionGroups($id) : [],
        'bundleItems' => $id ? Catalog::bundleItems($id) : [],
        'allProducts' => db()->all('SELECT id, name, sku FROM products WHERE is_active = 1 ORDER BY name'),
        'specsText' => specs_to_text(decode_json($product['specs_json'] ?? '[]')),
        'galleryText' => implode("\n", decode_json($product['gallery_json'] ?? '[]')),
    ]);
}

function ctrl_admin_product_save(): void
{
    Auth::requireAdmin();
    require_csrf();

    $id = (int) post('id', 0);
    $existing = $id > 0 ? Catalog::findById($id) : null;
    if ($id > 0 && !$existing) {
        flash('error', 'That product no longer exists.');
        redirect('/admin/products');
    }

    $name = trim((string) post('name', ''));
    $categoryId = (int) post('category_id', 0);
    $errors = [];

    if ($name === '') {
        $errors[] = 'Product name is required.';
    }
    $category = $categoryId > 0 ? db()->one('SELECT * FROM categories WHERE id = ?', [$categoryId]) : null;
    if (!$category) {
        $errors[] = 'Choose a valid category.';
    }

    $slugInput = trim((string) post('slug', ''));
    $slug = admin_unique_slug('products', $slugInput !== '' ? $slugInput : $name, $id);

    $type = (string) post('product_type', 'configurable');
    if (!in_array($type, ['simple', 'configurable', 'bundle'], true)) {
        $type = 'simple';
    }

    $gstPercent = (float) post('gst_percent', 18);
    $gstBp = (int) round(max(0, min(100, $gstPercent)) * 100);

    $image = trim((string) post('image', (string) ($existing['image'] ?? '')));
    $galleryRaw = trim((string) post('gallery', ''));
    $gallery = [];
    foreach (preg_split('/\r\n|\r|\n/', $galleryRaw) ?: [] as $line) {
        $line = trim($line);
        if ($line !== '') {
            $gallery[] = $line;
        }
    }

    try {
        $uploaded = admin_upload_image('image_file', $slug, 'images/products/uploads');
        if ($uploaded !== null) {
            $image = $uploaded;
        }
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }

    if ($errors) {
        foreach ($errors as $message) {
            flash('error', $message);
        }
        redirect($id > 0 ? '/admin/products/' . $id . '/edit' : '/admin/products/new');
    }

    $now = date('Y-m-d H:i:s');
    $data = [
        'category_id' => $categoryId,
        'slug' => $slug,
        'name' => $name,
        'sku' => trim((string) post('sku', '')),
        'product_type' => $type,
        'short_description' => trim((string) post('short_description', '')),
        'description' => trim((string) post('description', '')),
        'specs_json' => json_encode(specs_from_text((string) post('specs', '')), JSON_UNESCAPED_UNICODE),
        'image' => $image,
        'gallery_json' => json_encode($gallery, JSON_UNESCAPED_UNICODE),
        'datasheet' => trim((string) post('datasheet', '')),
        'base_price_paise' => parse_money_to_paise(post('base_price', '0')),
        'dim_rate_paise_sqm' => parse_money_to_paise(post('dim_rate', '0')),
        'price_note' => trim((string) post('price_note', '')),
        'unit' => trim((string) post('unit', 'Nos')) ?: 'Nos',
        'hsn_code' => trim((string) post('hsn_code', '')),
        'gst_rate_bp' => $gstBp,
        'min_qty' => max(1, (int) post('min_qty', 1)),
        'lead_time' => trim((string) post('lead_time', '')),
        'warranty' => trim((string) post('warranty', '')),
        'is_featured' => post('is_featured') ? 1 : 0,
        'is_active' => post('is_active') ? 1 : 0,
        'sort_order' => (int) post('sort_order', 0),
        'meta_title' => trim((string) post('meta_title', '')),
        'meta_description' => trim((string) post('meta_description', '')),
        'updated_at' => $now,
    ];

    if ($existing) {
        db()->update('products', $data, 'id = :id', ['id' => $id]);
    } else {
        $data['created_at'] = $now;
        $id = db()->insert('products', $data);
    }

    $groups = $_POST['groups'] ?? [];
    admin_sync_option_groups($id, is_array($groups) ? $groups : []);

    flash('success', 'Product saved. Review the price and options before publishing.');
    redirect('/admin/products/' . $id . '/edit');
}

/**
 * Persist the dynamic option-group editor.
 *
 * @param array<int|string, array<string, mixed>> $groups
 */
function admin_sync_option_groups(int $productId, array $groups): void
{
    $existingGroups = db()->all('SELECT id FROM option_groups WHERE product_id = ?', [$productId]);
    $existingGroupIds = array_map(static fn (array $g): int => (int) $g['id'], $existingGroups);
    $keptGroups = [];
    $seenSlugs = [];

    foreach ($groups as $groupIndex => $group) {
        if (!is_array($group) || trim((string) ($group['name'] ?? '')) === '') {
            continue;
        }
        $name = trim((string) $group['name']);
        $baseSlug = slugify((string) ($group['slug'] ?? '')) ?: slugify($name) ?: 'option';
        $slug = $baseSlug;
        $n = 2;
        while (isset($seenSlugs[$slug])) {
            $slug = $baseSlug . '-' . $n++;
        }
        $seenSlugs[$slug] = true;

        $type = (string) ($group['input_type'] ?? 'radio');
        if (!in_array($type, ['radio', 'checkbox', 'dimension'], true)) {
            $type = 'radio';
        }

        $data = [
            'name' => $name,
            'slug' => $slug,
            'help_text' => trim((string) ($group['help_text'] ?? '')),
            'input_type' => $type,
            'is_required' => !empty($group['is_required']) ? 1 : 0,
            'sort_order' => (int) $groupIndex,
            'min_value' => ($group['min_value'] ?? '') !== '' ? (int) $group['min_value'] : null,
            'max_value' => ($group['max_value'] ?? '') !== '' ? (int) $group['max_value'] : null,
            'step_value' => ($group['step_value'] ?? '') !== '' ? (int) $group['step_value'] : null,
            'default_w' => ($group['default_w'] ?? '') !== '' ? (int) $group['default_w'] : null,
            'default_h' => ($group['default_h'] ?? '') !== '' ? (int) $group['default_h'] : null,
            'default_d' => ($group['default_d'] ?? '') !== '' ? (int) $group['default_d'] : null,
            'unit_label' => trim((string) ($group['unit_label'] ?? '')),
        ];

        $groupId = (int) ($group['id'] ?? 0);
        if ($groupId > 0 && in_array($groupId, $existingGroupIds, true)) {
            db()->update('option_groups', $data, 'id = :id', ['id' => $groupId]);
        } else {
            $data['product_id'] = $productId;
            $groupId = db()->insert('option_groups', $data);
        }
        $keptGroups[] = $groupId;

        $existingValues = db()->all('SELECT id FROM option_values WHERE group_id = ?', [$groupId]);
        $existingValueIds = array_map(static fn (array $v): int => (int) $v['id'], $existingValues);
        $keptValues = [];
        $defaultUsed = false;

        foreach (($group['values'] ?? []) as $valueIndex => $value) {
            if (!is_array($value) || trim((string) ($value['label'] ?? '')) === '') {
                continue;
            }
            $isDefault = !empty($value['is_default']) && !$defaultUsed ? 1 : 0;
            if ($isDefault) {
                $defaultUsed = true;
            }
            $valueData = [
                'label' => trim((string) $value['label']),
                'sku_suffix' => trim((string) ($value['sku_suffix'] ?? '')),
                'price_delta_paise' => parse_money_to_paise($value['price_delta'] ?? '0'),
                'price_multiplier_bp' => (int) round(((float) ($value['price_multiplier'] ?? 0)) * 100),
                'is_default' => $isDefault,
                'sort_order' => (int) $valueIndex,
                'meta_json' => '',
            ];
            $valueId = (int) ($value['id'] ?? 0);
            if ($valueId > 0 && in_array($valueId, $existingValueIds, true)) {
                db()->update('option_values', $valueData, 'id = :id', ['id' => $valueId]);
            } else {
                $valueData['group_id'] = $groupId;
                $valueId = db()->insert('option_values', $valueData);
            }
            $keptValues[] = $valueId;
        }

        if ($keptValues) {
            $placeholders = implode(',', array_fill(0, count($keptValues), '?'));
            db()->run(
                "DELETE FROM option_values WHERE group_id = ? AND id NOT IN ($placeholders)",
                array_merge([$groupId], $keptValues)
            );
        } else {
            db()->run('DELETE FROM option_values WHERE group_id = ?', [$groupId]);
        }
    }

    if ($keptGroups) {
        $placeholders = implode(',', array_fill(0, count($keptGroups), '?'));
        db()->run(
            "DELETE FROM option_groups WHERE product_id = ? AND id NOT IN ($placeholders)",
            array_merge([$productId], $keptGroups)
        );
    } else {
        db()->run('DELETE FROM option_groups WHERE product_id = ?', [$productId]);
    }
}

function ctrl_admin_product_toggle(array $params): void
{
    Auth::requireAdmin();
    require_csrf();
    $id = admin_url_id($params);
    $product = $id > 0 ? Catalog::findById($id) : null;
    if (!$product) {
        flash('error', 'Product not found.');
        redirect('/admin/products');
    }
    $active = (int) $product['is_active'] === 1 ? 0 : 1;
    db()->update('products', ['is_active' => $active, 'updated_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $id]);
    flash('success', $product['name'] . ($active ? ' is now visible in the store.' : ' has been hidden from the store.'));
    redirect('/admin/products');
}

/* -------------------------------------------------------------------------
   Categories
   ------------------------------------------------------------------------- */

function ctrl_admin_categories(): void
{
    Auth::requireAdmin();
    admin_render('categories', [
        'pageTitle' => 'Categories | Ronak Fire admin',
        'adminNav' => 'categories',
        'adminTitle' => 'Categories',
        'categories' => Catalog::categories(false),
        'productCounts' => Catalog::countsByCategory(),
    ]);
}

function ctrl_admin_categories_save(): void
{
    Auth::requireAdmin();
    require_csrf();

    $rows = $_POST['categories'] ?? [];
    if (is_array($rows)) {
        foreach ($rows as $id => $row) {
            $id = (int) $id;
            if ($id <= 0 || !is_array($row)) {
                continue;
            }
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $slug = admin_unique_slug('categories', (string) ($row['slug'] ?? $name), $id);
            $image = trim((string) ($row['image'] ?? ''));
            try {
                $uploaded = admin_upload_image('image_file_' . $id, $slug, 'images/uploads');
                if ($uploaded !== null) {
                    $image = $uploaded;
                }
            } catch (Throwable $e) {
                flash('error', $e->getMessage());
            }
            db()->update('categories', [
                'name' => $name,
                'slug' => $slug,
                'short_name' => trim((string) ($row['short_name'] ?? $name)),
                'tagline' => trim((string) ($row['tagline'] ?? '')),
                'description' => trim((string) ($row['description'] ?? '')),
                'image' => $image,
                'sort_order' => (int) ($row['sort_order'] ?? 0),
                'is_active' => !empty($row['is_active']) ? 1 : 0,
            ], 'id = :id', ['id' => $id]);
        }
    }

    $newName = trim((string) post('new_name', ''));
    if ($newName !== '') {
        db()->insert('categories', [
            'slug' => admin_unique_slug('categories', (string) post('new_slug', $newName)),
            'name' => $newName,
            'short_name' => $newName,
            'tagline' => trim((string) post('new_tagline', '')),
            'description' => '',
            'image' => '',
            'sort_order' => (int) post('new_sort_order', 99),
            'is_active' => 1,
        ]);
        flash('success', 'Category added. You can now assign products to it.');
    }

    flash('success', 'Categories saved.');
    redirect('/admin/categories');
}

/* -------------------------------------------------------------------------
   Orders
   ------------------------------------------------------------------------- */

function ctrl_admin_orders(): void
{
    Auth::requireAdmin();

    $status = trim((string) input('status', ''));
    $q = trim((string) input('q', ''));
    $page = max(1, (int) input('page', 1));
    $perPage = 20;

    $where = '1';
    $params = [];
    if ($status !== '' && in_array($status, order_statuses(), true)) {
        $where .= ' AND o.status = ?';
        $params[] = $status;
    }
    if ($q !== '') {
        $like = '%' . $q . '%';
        $where .= ' AND (o.order_no LIKE ? OR c.company_name LIKE ? OR c.email LIKE ? OR o.po_number LIKE ?)';
        array_push($params, $like, $like, $like, $like);
    }

    $total = (int) db()->value(
        'SELECT COUNT(*) FROM orders o JOIN customers c ON c.id = o.customer_id WHERE ' . $where,
        $params
    );
    $pager = admin_pagination($total, $perPage, $page);

    $orders = db()->all(
        'SELECT o.*, c.company_name, c.customer_code FROM orders o JOIN customers c ON c.id = o.customer_id
         WHERE ' . $where . ' ORDER BY o.placed_at DESC, o.id DESC
         LIMIT ' . $perPage . ' OFFSET ' . $pager['offset'],
        $params
    );

    $statusCounts = [];
    foreach (db()->all('SELECT status, COUNT(*) AS total FROM orders GROUP BY status') as $row) {
        $statusCounts[$row['status']] = (int) $row['total'];
    }

    admin_render('orders', [
        'pageTitle' => 'Orders | Ronak Fire admin',
        'adminNav' => 'orders',
        'adminTitle' => 'Orders',
        'orders' => $orders,
        'status' => $status,
        'q' => $q,
        'pager' => $pager,
        'statusCounts' => $statusCounts,
    ]);
}

function ctrl_admin_order(array $params): void
{
    Auth::requireAdmin();
    $order = admin_find_order(admin_url_id($params));
    if (!$order) {
        http_response_code(404);
        admin_render('not-found', [
            'pageTitle' => 'Order not found',
            'adminNav' => 'orders',
            'adminTitle' => 'Order not found',
        ]);
        return;
    }

    $items = db()->all('SELECT * FROM order_items WHERE order_id = ? ORDER BY id', [$order['id']]);
    foreach ($items as &$item) {
        $item['options'] = decode_json($item['options_json']);
    }
    unset($item);

    admin_render('order', [
        'pageTitle' => 'Order ' . $order['order_no'] . ' | Ronak Fire admin',
        'adminNav' => 'orders',
        'adminTitle' => 'Order ' . $order['order_no'],
        'order' => $order,
        'items' => $items,
        'billing' => decode_json($order['billing_json']),
        'shipping' => decode_json($order['shipping_json']),
    ]);
}

function ctrl_admin_order_status(array $params): void
{
    Auth::requireAdmin();
    require_csrf();

    $id = admin_url_id($params);
    $status = (string) post('status', '');
    $order = $id > 0 ? db()->one('SELECT * FROM orders WHERE id = ?', [$id]) : null;

    if (!$order) {
        flash('error', 'Order not found.');
        redirect('/admin/orders');
    }
    if (!in_array($status, order_statuses(), true)) {
        flash('error', 'Choose a valid order status.');
        redirect('/admin/orders/' . $id);
    }

    $oldStatus = (string) $order['status'];
    $data = ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')];

    // Optional tracking fields
    if (array_key_exists('courier_name', $_POST)) {
        $data['courier_name'] = trim((string) post('courier_name', '')) === '' ? null : trim((string) post('courier_name', ''));
    }
    if (array_key_exists('tracking_no', $_POST)) {
        $data['tracking_no'] = trim((string) post('tracking_no', '')) === '' ? null : trim((string) post('tracking_no', ''));
    }
    if (array_key_exists('tracking_url', $_POST)) {
        $data['tracking_url'] = trim((string) post('tracking_url', '')) === '' ? null : trim((string) post('tracking_url', ''));
    }

    $timeColumns = [
        'confirmed' => 'confirmed_at',
        'invoiced' => 'invoiced_at',
        'dispatched' => 'dispatched_at',
        'delivered' => 'delivered_at',
        'cancelled' => 'cancelled_at',
    ];
    if (isset($timeColumns[$status]) && empty($order[$timeColumns[$status]])) {
        $data[$timeColumns[$status]] = date('Y-m-d H:i:s');
    }
    db()->update('orders', $data, 'id = :id', ['id' => $id]);

    if ($oldStatus !== $status) {
        try {
            require_once APP_PATH . '/controllers/store.php';
            if (function_exists('notify_order_status_changed')) {
                notify_order_status_changed($id, $oldStatus, $status);
            }
        } catch (Throwable $e) {
            error_log('Status notification error: ' . $e->getMessage());
        }
    }

    flash('success', 'Order marked as ' . strtolower(order_status_label($status)) . '.');
    redirect('/admin/orders/' . $id);
}

function ctrl_admin_order_freight(array $params): void
{
    Auth::requireAdmin();
    require_csrf();

    $id = admin_url_id($params);
    $order = $id > 0 ? db()->one('SELECT * FROM orders WHERE id = ?', [$id]) : null;
    if (!$order) {
        flash('error', 'Order not found.');
        redirect('/admin/orders');
    }

    $freight = max(0, parse_money_to_paise(post('freight', '0')));
    $grand = (int) $order['subtotal_paise'] + (int) $order['gst_paise'] + $freight;
    db()->update('orders', [
        'freight_paise' => $freight,
        'grand_total_paise' => $grand,
        'updated_at' => date('Y-m-d H:i:s'),
    ], 'id = :id', ['id' => $id]);

    flash('success', 'Freight updated. The order total is now ' . money($grand) . '.');
    redirect('/admin/orders/' . $id);
}

function ctrl_admin_order_tracking(array $params): void
{
    Auth::requireAdmin();
    require_csrf();

    $id    = admin_url_id($params);
    $order = $id > 0 ? db()->one('SELECT * FROM orders WHERE id = ?', [$id]) : null;
    if (!$order) {
        flash('error', 'Order not found.');
        redirect('/admin/orders');
    }

    $courierName = trim((string) post('courier_name', ''));
    $trackingNo  = trim((string) post('tracking_no', ''));
    $trackingUrl = trim((string) post('tracking_url', ''));

    if ($trackingNo === '') {
        flash('error', 'Enter a tracking / docket number.');
        redirect('/admin/orders/' . $id);
    }

    db()->update('orders', [
        'courier_name' => $courierName,
        'tracking_no'  => $trackingNo,
        'tracking_url' => $trackingUrl,
        'updated_at'   => date('Y-m-d H:i:s'),
    ], 'id = :id', ['id' => $id]);

    // Notify customer by email (best effort)
    if (config('store.notify_orders', false)) {
        try {
            $customer = db()->one('SELECT * FROM customers WHERE id = ?', [$order['customer_id']]);
            if ($customer && $customer['email']) {
                $trackLine = $trackingUrl ? "\nTrack at: " . $trackingUrl : '';
                $body = "Your order {$order['order_no']} has been dispatched.\n\n"
                    . 'Courier: ' . ($courierName ?: 'To be advised') . "\n"
                    . 'Docket / tracking no: ' . $trackingNo
                    . $trackLine . "\n\n"
                    . 'View your order: ' . abs_url('/order/' . $order['order_no']);
                @mail(
                    $customer['email'],
                    'Your Ronak Fire order ' . $order['order_no'] . ' has been dispatched',
                    $body,
                    'From: ' . config('store.support_email') . "\r\nContent-Type: text/plain; charset=UTF-8"
                );
            }
        } catch (Throwable $e) {
            error_log('Dispatch notification failed: ' . $e->getMessage());
        }
    }

    flash('success', 'Tracking details saved and customer notified.');
    redirect('/admin/orders/' . $id);
}

function ctrl_admin_order_invoice(array $params): void
{
    Auth::requireAdmin();
    $order = admin_find_order(admin_url_id($params));
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
   Customers
   ------------------------------------------------------------------------- */

function ctrl_admin_customers(): void
{
    Auth::requireAdmin();

    $q = trim((string) input('q', ''));
    $page = max(1, (int) input('page', 1));
    $perPage = 20;

    $where = '1';
    $params = [];
    if ($q !== '') {
        $like = '%' . $q . '%';
        $where .= ' AND (company_name LIKE ? OR contact_name LIKE ? OR email LIKE ? OR customer_code LIKE ? OR gstin LIKE ?)';
        array_push($params, $like, $like, $like, $like, $like);
    }
    $total = (int) db()->value('SELECT COUNT(*) FROM customers WHERE ' . $where, $params);
    $pager = admin_pagination($total, $perPage, $page);

    $customers = db()->all(
        'SELECT c.*,
                (SELECT COUNT(*) FROM orders o WHERE o.customer_id = c.id) AS order_count,
                (SELECT COALESCE(SUM(o.grand_total_paise),0) FROM orders o WHERE o.customer_id = c.id AND o.status <> \'cancelled\') AS order_value
         FROM customers c WHERE ' . $where . ' ORDER BY c.company_name LIMIT ' . $perPage . ' OFFSET ' . $pager['offset'],
        $params
    );

    admin_render('customers', [
        'pageTitle' => 'Customers | Ronak Fire admin',
        'adminNav' => 'customers',
        'adminTitle' => 'Customers',
        'customers' => $customers,
        'q' => $q,
        'pager' => $pager,
    ]);
}

function ctrl_admin_customer(array $params): void
{
    Auth::requireAdmin();
    $id = admin_url_id($params);
    $customer = $id > 0 ? db()->one('SELECT * FROM customers WHERE id = ?', [$id]) : null;
    if (!$customer) {
        http_response_code(404);
        admin_render('not-found', [
            'pageTitle' => 'Customer not found',
            'adminNav' => 'customers',
            'adminTitle' => 'Customer not found',
        ]);
        return;
    }

    $orders = db()->all('SELECT * FROM orders WHERE customer_id = ? ORDER BY placed_at DESC, id DESC', [$id]);
    $enquiries = db()->all('SELECT * FROM enquiries WHERE customer_id = ? ORDER BY created_at DESC', [$id]);

    admin_render('customer', [
        'pageTitle' => $customer['company_name'] . ' | Ronak Fire admin',
        'adminNav' => 'customers',
        'adminTitle' => $customer['company_name'],
        'customer' => $customer,
        'orders' => $orders,
        'enquiries' => $enquiries,
    ]);
}

function ctrl_admin_customer_save(array $params): void
{
    Auth::requireAdmin();
    require_csrf();

    $id = admin_url_id($params);
    $customer = $id > 0 ? db()->one('SELECT * FROM customers WHERE id = ?', [$id]) : null;
    if (!$customer) {
        flash('error', 'Customer not found.');
        redirect('/admin/customers');
    }

    $company = trim((string) post('company_name', ''));
    if ($company === '') {
        flash('error', 'Company name is required.');
        redirect('/admin/customers/' . $id);
    }

    db()->update('customers', [
        'company_name' => $company,
        'contact_name' => trim((string) post('contact_name', '')),
        'email' => trim((string) post('email', '')),
        'phone' => trim((string) post('phone', '')),
        'gstin' => strtoupper(trim((string) post('gstin', ''))),
        'address_line1' => trim((string) post('address_line1', '')),
        'address_line2' => trim((string) post('address_line2', '')),
        'city' => trim((string) post('city', '')),
        'state' => trim((string) post('state', '')),
        'pincode' => trim((string) post('pincode', '')),
        'price_tier' => trim((string) post('price_tier', 'standard')),
        'discount_bp' => max(0, min(10000, (int) round(((float) post('discount_percent', 0)) * 100))),
        'credit_days' => max(0, (int) post('credit_days', 0)),
        'credit_limit_paise' => parse_money_to_paise(post('credit_limit', '0')),
        'is_active' => post('is_active') ? 1 : 0,
        'updated_at' => date('Y-m-d H:i:s'),
    ], 'id = :id', ['id' => $id]);

    flash('success', 'Customer account updated.');
    redirect('/admin/customers/' . $id);
}

function ctrl_admin_customer_new(): void
{
    Auth::requireAdmin();
    $stateList = ['Andhra Pradesh','Arunachal Pradesh','Assam','Bihar','Chhattisgarh','Goa','Gujarat','Haryana','Himachal Pradesh','Jharkhand','Karnataka','Kerala','Madhya Pradesh','Maharashtra','Manipur','Meghalaya','Mizoram','Nagaland','Odisha','Punjab','Rajasthan','Sikkim','Tamil Nadu','Telangana','Tripura','Uttar Pradesh','Uttarakhand','West Bengal','Andaman and Nicobar Islands','Chandigarh','Dadra and Nagar Haveli and Daman and Diu','Delhi','Jammu and Kashmir','Ladakh','Lakshadweep','Puducherry'];
    admin_render('customer-new', [
        'pageTitle' => 'Create customer account | Ronak Fire admin',
        'adminNav' => 'customers',
        'adminTitle' => 'Create customer',
        'states' => $stateList,
        'customer' => [
            'company_name' => '',
            'contact_name' => '',
            'email' => '',
            'phone' => '',
            'gstin' => '',
            'address_line1' => '',
            'address_line2' => '',
            'city' => '',
            'state' => '',
            'pincode' => '',
            'price_tier' => 'standard',
            'discount_percent' => 0,
            'credit_days' => 0,
            'credit_limit' => '0',
            'is_active' => 1,
        ],
    ]);
}

function ctrl_admin_customer_create(): void
{
    Auth::requireAdmin();
    require_csrf();

    $company = trim((string) post('company_name', ''));
    if ($company === '') {
        flash('error', 'Company name is required.');
        redirect('/admin/customers/new');
    }

    $email = strtolower(trim((string) post('email', '')));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('error', 'Enter a valid email address.');
        redirect('/admin/customers/new');
    }

    // Check duplicate email
    $existing = db()->one('SELECT id FROM customers WHERE email = ?', [$email]);
    if ($existing) {
        flash('error', 'A customer with this email already exists.');
        redirect('/admin/customers/new');
    }

    $now = date('Y-m-d H:i:s');
    $tempPassword = bin2hex(random_bytes(6)); // 12-char temp password
    $customerCode = 'RF-' . strtoupper(substr(sha1($email), 0, 6));

    $customerId = db()->insert('customers', [
        'portal_id' => $customerCode,
        'customer_code' => $customerCode,
        'company_name' => $company,
        'contact_name' => trim((string) post('contact_name', '')),
        'email' => $email,
        'phone' => trim((string) post('phone', '')),
        'gstin' => strtoupper(trim((string) post('gstin', ''))),
        'address_line1' => trim((string) post('address_line1', '')),
        'address_line2' => trim((string) post('address_line2', '')),
        'city' => trim((string) post('city', '')),
        'state' => trim((string) post('state', '')),
        'pincode' => trim((string) post('pincode', '')),
        'price_tier' => trim((string) post('price_tier', 'standard')),
        'discount_bp' => max(0, min(10000, (int) round(((float) post('discount_percent', 0)) * 100))),
        'credit_days' => max(0, (int) post('credit_days', 0)),
        'credit_limit_paise' => parse_money_to_paise(post('credit_limit', '0')),
        'is_active' => post('is_active') ? 1 : 0,
        'password_hash' => password_hash($tempPassword, PASSWORD_DEFAULT),
        'password_set_at' => $now,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    // Email credentials to customer
    if (config('store.notify_orders', false) && !empty($email)) {
        try {
            $body = "Your Ronak Fire customer account has been created\n\n"
                . 'Company: ' . $company . "\n"
                . 'Customer ID: ' . $customerCode . "\n"
                . 'Email (User ID): ' . $email . "\n"
                . 'Temporary password: ' . $tempPassword . "\n\n"
                . 'Please log in at ' . abs_url('/login') . ' and change your password after first login.' . "\n\n"
                . 'You can now view your orders, inventory and documents in your account.' . "\n\n"
                . 'Ronak Fire India Pvt. Ltd.';
            @mail(
                $email,
                'Your Ronak Fire customer account — login details',
                $body,
                'From: ' . config('store.support_email') . "\r\nContent-Type: text/plain; charset=UTF-8"
            );
        } catch (Throwable $e) {
            error_log('Customer credentials email failed: ' . $e->getMessage());
        }
    }

    flash('success', 'Customer account created. Login details have been emailed to ' . $email . '. Temporary password: ' . $tempPassword);
    redirect('/admin/customers/' . $customerId);
}

/* -------------------------------------------------------------------------
   Enquiries
   ------------------------------------------------------------------------- */

function ctrl_admin_enquiries(): void
{
    Auth::requireAdmin();

    $status = trim((string) input('status', ''));
    $q = trim((string) input('q', ''));
    $page = max(1, (int) input('page', 1));
    $perPage = 20;

    $where = '1';
    $params = [];
    if ($status !== '') {
        $where .= ' AND status = ?';
        $params[] = $status;
    }
    if ($q !== '') {
        $like = '%' . $q . '%';
        $where .= ' AND (name LIKE ? OR company LIKE ? OR email LIKE ? OR product_name LIKE ?)';
        array_push($params, $like, $like, $like, $like);
    }
    $total = (int) db()->value('SELECT COUNT(*) FROM enquiries WHERE ' . $where, $params);
    $pager = admin_pagination($total, $perPage, $page);
    $enquiries = db()->all(
        'SELECT * FROM enquiries WHERE ' . $where . ' ORDER BY created_at DESC, id DESC LIMIT ' . $perPage . ' OFFSET ' . $pager['offset'],
        $params
    );

    $statusCounts = [];
    foreach (db()->all('SELECT status, COUNT(*) AS total FROM enquiries GROUP BY status') as $row) {
        $statusCounts[$row['status']] = (int) $row['total'];
    }

    admin_render('enquiries', [
        'pageTitle' => 'Quote requests | Ronak Fire admin',
        'adminNav' => 'enquiries',
        'adminTitle' => 'Quote requests',
        'enquiries' => $enquiries,
        'status' => $status,
        'q' => $q,
        'pager' => $pager,
        'statusCounts' => $statusCounts,
    ]);
}

function ctrl_admin_enquiry(array $params): void
{
    Auth::requireAdmin();
    $id = admin_url_id($params);
    $enquiry = $id > 0 ? db()->one('SELECT * FROM enquiries WHERE id = ?', [$id]) : null;
    if (!$enquiry) {
        http_response_code(404);
        admin_render('not-found', [
            'pageTitle' => 'Enquiry not found',
            'adminNav' => 'enquiries',
            'adminTitle' => 'Enquiry not found',
        ]);
        return;
    }

    $product = $enquiry['product_id'] ? Catalog::findById((int) $enquiry['product_id']) : null;
    $customer = $enquiry['customer_id'] ? db()->one('SELECT * FROM customers WHERE id = ?', [$enquiry['customer_id']]) : null;

    admin_render('enquiry', [
        'pageTitle' => 'Quote request #' . $enquiry['id'] . ' | Ronak Fire admin',
        'adminNav' => 'enquiries',
        'adminTitle' => 'Quote request #' . $enquiry['id'],
        'enquiry' => $enquiry,
        'product' => $product,
        'customer' => $customer,
    ]);
}

function ctrl_admin_enquiry_status(array $params): void
{
    Auth::requireAdmin();
    require_csrf();

    $id = admin_url_id($params);
    $status = (string) post('status', '');
    if (!in_array($status, ['new', 'contacted', 'quoted', 'closed'], true)) {
        flash('error', 'Choose a valid enquiry status.');
        redirect('/admin/enquiries/' . $id);
    }
    $updated = db()->update('enquiries', [
        'status' => $status,
        'updated_at' => date('Y-m-d H:i:s'),
    ], 'id = :id', ['id' => $id]);

    if (!$updated) {
        flash('error', 'Enquiry not found.');
        redirect('/admin/enquiries');
    }
    flash('success', 'Quote request marked as ' . $status . '.');
    redirect('/admin/enquiries/' . $id);
}

function ctrl_admin_enquiry_convert(array $params): void
{
    Auth::requireAdmin();
    require_csrf();

    $id = admin_url_id($params);
    $enquiry = db()->one('SELECT * FROM enquiries WHERE id = ?', [$id]);
    if (!$enquiry) {
        flash('error', 'Enquiry not found.');
        redirect('/admin/enquiries');
    }
    if (!$enquiry['customer_id'] || !$enquiry['product_id']) {
        flash('error', 'This enquiry has no linked customer or product — cannot auto-convert. Create the order manually.');
        redirect('/admin/enquiries/' . $id);
    }

    $customerId = (int) $enquiry['customer_id'];
    $productId  = (int) $enquiry['product_id'];
    $qty        = max(1, (int) $enquiry['qty']);

    // Reconstruct selection from stored configuration (free-text, best effort)
    $selection = ['groups' => [], 'dimensions' => []];

    $result = \App\Cart::add($customerId, $productId, $qty, $selection);
    if (!$result['ok']) {
        flash('error', 'Could not add product to cart: ' . $result['message']);
        redirect('/admin/enquiries/' . $id);
    }

    // Mark enquiry as converted
    db()->update('enquiries', [
        'status'     => 'quoted',
        'updated_at' => date('Y-m-d H:i:s'),
    ], 'id = :id', ['id' => $id]);

    flash('success', 'Product added to customer cart. The customer can now check out at /cart.');
    redirect('/admin/customers/' . $customerId);
}

/* -------------------------------------------------------------------------
   Settings & admin account
   ------------------------------------------------------------------------- */

function ctrl_admin_settings(): void
{
    Auth::requireAdmin();

    admin_render('settings', [
        'pageTitle' => 'Store settings | Ronak Fire admin',
        'adminNav' => 'settings',
        'adminTitle' => 'Store settings',
        'values' => [
            'min_order_paise' => (string) setting('min_order_paise', config('store.min_order_paise', 0)),
            'support_email' => (string) setting('support_email', config('store.support_email', '')),
            'support_phone' => (string) setting('support_phone', config('store.support_phone', '')),
            'support_phone_href' => (string) setting('support_phone_href', config('store.support_phone_href', '')),
            'freight_note' => (string) setting('freight_note', config('store.freight_note', '')),
            'announcement' => (string) setting('announcement', 'Manufacturer-direct fire protection · GST invoices · Pan-India dispatch'),
            'prices_are_placeholders' => (string) setting('prices_are_placeholders', '0'),
        ],
    ]);
}

function ctrl_admin_settings_save(): void
{
    Auth::requireAdmin();
    require_csrf();

    set_setting('min_order_paise', (string) max(0, parse_money_to_paise(post('min_order', '0'))));
    set_setting('support_email', trim((string) post('support_email', '')));
    set_setting('support_phone', trim((string) post('support_phone', '')));
    set_setting('support_phone_href', trim((string) post('support_phone_href', '')));
    set_setting('freight_note', trim((string) post('freight_note', '')));
    set_setting('announcement', trim((string) post('announcement', '')));
    set_setting('prices_are_placeholders', post('prices_are_placeholders') ? '1' : '0');

    flash('success', 'Store settings saved.');
    redirect('/admin/settings');
}

function ctrl_admin_account(): void
{
    Auth::requireAdmin();
    admin_render('account', [
        'pageTitle' => 'Your admin account | Ronak Fire admin',
        'adminNav' => 'account',
        'adminTitle' => 'Admin account',
    ]);
}

function ctrl_admin_password(): void
{
    Auth::requireAdmin();
    require_csrf();

    $admin = Auth::admin();
    $current = (string) post('current_password', '');
    $new = (string) post('new_password', '');
    $confirm = (string) post('confirm_password', '');

    if (!$admin || !password_verify($current, (string) $admin['password_hash'])) {
        flash('error', 'Your current password is not correct.');
        redirect('/admin/account');
    }
    if (strlen($new) < 10) {
        flash('error', 'Use a new password of at least 10 characters.');
        redirect('/admin/account');
    }
    if ($new !== $confirm) {
        flash('error', 'The new passwords do not match.');
        redirect('/admin/account');
    }

    db()->update('admin_users', [
        'password_hash' => password_hash($new, PASSWORD_DEFAULT),
    ], 'id = :id', ['id' => $admin['id']]);

    flash('success', 'Your password has been changed.');
    redirect('/admin/account');
}

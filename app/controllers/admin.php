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

function admin_is_sales(): bool
{
    return Auth::isSales();
}

/**
 * Sales users log in to the same admin panel but only work with their own
 * customers. Catalog, staff and store settings stay with full admins.
 */
function admin_require_manager(): void
{
    if (admin_is_sales()) {
        flash('error', 'That area is available to store administrators only.');
        redirect('/admin');
    }
}

/**
 * SQL fragment + params that restrict a query to the signed-in sales user's
 * customers. Full admins get every row.
 *
 * @return array{0: string, 1: array<int, mixed>}
 */
function admin_owner_scope(string $alias = 'c'): array
{
    if (!admin_is_sales()) {
        return ['1 = 1', []];
    }
    $admin = Auth::admin();
    return [$alias . '.sales_owner_id = ?', [(int) ($admin['id'] ?? 0)]];
}

/**
 * Sales users that can own a customer. The signed-in user is always included
 * so an admin-created customer keeps a valid owner in the dropdown.
 */
function admin_assignable_users(?int $includeId = null): array
{
    $admin = Auth::admin();
    $currentId = (int) ($admin['id'] ?? 0);
    return db()->all(
        'SELECT id, name, email, role FROM admin_users
         WHERE is_active = 1 AND (role = \'sales\' OR id = ? OR id = ?)
         ORDER BY CASE WHEN role = \'sales\' THEN 0 ELSE 1 END, name',
        [$currentId, (int) ($includeId ?? 0)]
    );
}

/** Optional owner filter used by full admins on list screens. */
function admin_owner_filter(): ?int
{
    if (admin_is_sales()) {
        return null;
    }
    $owner = (int) input('owner', 0);
    return $owner > 0 ? $owner : null;
}

function admin_find_customer(int $id): ?array
{
    [$scope, $params] = admin_owner_scope('c');
    return db()->one(
        'SELECT c.*, au.name AS sales_owner_name
         FROM customers c LEFT JOIN admin_users au ON au.id = c.sales_owner_id
         WHERE c.id = ? AND ' . $scope,
        array_merge([$id], $params)
    );
}

function admin_find_enquiry(int $id): ?array
{
    [$scope, $params] = admin_owner_scope('c');
    return db()->one(
        'SELECT e.*, c.company_name AS customer_company, c.sales_owner_id, au.name AS sales_owner_name
         FROM enquiries e
         LEFT JOIN customers c ON c.id = e.customer_id
         LEFT JOIN admin_users au ON au.id = c.sales_owner_id
         WHERE e.id = ? AND ' . $scope,
        array_merge([$id], $params)
    );
}

function admin_find_order(int $id): ?array
{
    [$scope, $params] = admin_owner_scope('c');
    return db()->one(
        'SELECT o.*, c.company_name, c.customer_code, c.contact_name AS customer_contact,
                c.email AS customer_email, c.phone AS customer_phone, c.gstin AS customer_gstin,
                c.price_tier, c.discount_bp, c.sales_owner_id, au.name AS sales_owner_name
         FROM orders o JOIN customers c ON c.id = o.customer_id
         LEFT JOIN admin_users au ON au.id = c.sales_owner_id
         WHERE o.id = ? AND ' . $scope,
        array_merge([$id], $params)
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

    [$scope, $scopeParams] = admin_owner_scope('c');
    $since = date('Y-m-d H:i:s', time() - 30 * 86400);
    $salesJoin = ' JOIN customers c ON c.id = o.customer_id WHERE ' . $scope;

    $stats = [
        'new_orders' => (int) db()->value(
            'SELECT COUNT(*) FROM orders o' . $salesJoin . " AND o.status = 'placed'",
            $scopeParams
        ),
        'open_orders' => (int) db()->value(
            'SELECT COUNT(*) FROM orders o' . $salesJoin . " AND o.status IN ('placed','confirmed','invoiced','dispatched')",
            $scopeParams
        ),
        'orders_30d' => (int) db()->value(
            'SELECT COUNT(*) FROM orders o' . $salesJoin . ' AND o.placed_at >= ?',
            array_merge($scopeParams, [$since])
        ),
        'revenue_30d' => (int) db()->value(
            'SELECT COALESCE(SUM(o.grand_total_paise),0) FROM orders o' . $salesJoin . ' AND o.placed_at >= ? AND o.status <> ?',
            array_merge($scopeParams, [$since, 'cancelled'])
        ),
        'customers' => (int) db()->value(
            'SELECT COUNT(*) FROM customers c WHERE c.is_active = 1 AND ' . $scope,
            $scopeParams
        ),
        'new_enquiries' => (int) db()->value(
            'SELECT COUNT(*) FROM enquiries e LEFT JOIN customers c ON c.id = e.customer_id
             WHERE e.status = ? AND ' . $scope,
            array_merge(['new'], $scopeParams)
        ),
    ];

    $recentOrders = db()->all(
        'SELECT o.*, c.company_name, au.name AS sales_owner_name
         FROM orders o JOIN customers c ON c.id = o.customer_id
         LEFT JOIN admin_users au ON au.id = c.sales_owner_id
         WHERE ' . $scope . ' ORDER BY o.placed_at DESC, o.id DESC LIMIT 8',
        $scopeParams
    );
    $recentEnquiries = db()->all(
        'SELECT e.*, au.name AS sales_owner_name
         FROM enquiries e LEFT JOIN customers c ON c.id = e.customer_id
         LEFT JOIN admin_users au ON au.id = c.sales_owner_id
         WHERE ' . $scope . ' ORDER BY e.created_at DESC, e.id DESC LIMIT 6',
        $scopeParams
    );
    $topProducts = db()->all(
        "SELECT oi.product_name, oi.sku, SUM(oi.qty) AS qty, SUM(oi.line_total_paise) AS value
         FROM order_items oi JOIN orders o ON o.id = oi.order_id
         JOIN customers c ON c.id = o.customer_id
         WHERE o.status <> 'cancelled' AND " . $scope . '
         GROUP BY oi.product_name, oi.sku ORDER BY qty DESC LIMIT 6',
        $scopeParams
    );
    $statusRows = db()->all(
        'SELECT o.status, COUNT(*) AS total FROM orders o JOIN customers c ON c.id = o.customer_id
         WHERE ' . $scope . ' GROUP BY o.status',
        $scopeParams
    );
    $statusCounts = [];
    foreach ($statusRows as $row) {
        $statusCounts[$row['status']] = (int) $row['total'];
    }

    $salesSummary = [];
    $unassignedCustomers = 0;
    if (!admin_is_sales()) {
        $salesSummary = db()->all(
            "SELECT au.id, au.name, au.email,
                    (SELECT COUNT(*) FROM customers c WHERE c.sales_owner_id = au.id AND c.is_active = 1) AS customer_count,
                    (SELECT COUNT(*) FROM orders o JOIN customers c ON c.id = o.customer_id WHERE c.sales_owner_id = au.id) AS order_count,
                    (SELECT COALESCE(SUM(o.grand_total_paise),0) FROM orders o JOIN customers c ON c.id = o.customer_id
                     WHERE c.sales_owner_id = au.id AND o.status <> 'cancelled') AS order_value,
                    (SELECT COUNT(*) FROM enquiries e JOIN customers c ON c.id = e.customer_id WHERE c.sales_owner_id = au.id) AS enquiry_count
             FROM admin_users au
             WHERE au.is_active = 1 AND au.role IN ('sales', 'admin')
             ORDER BY order_value DESC, customer_count DESC, au.name"
        );
        $unassignedCustomers = (int) db()->value('SELECT COUNT(*) FROM customers WHERE sales_owner_id IS NULL');
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
        'salesSummary' => $salesSummary,
        'unassignedCustomers' => $unassignedCustomers,
        'isSales' => admin_is_sales(),
    ]);
}

/* -------------------------------------------------------------------------
   Products
   ------------------------------------------------------------------------- */

function ctrl_admin_products(): void
{
    Auth::requireAdmin();
    admin_require_manager();

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
    admin_require_manager();

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
    admin_require_manager();
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
    admin_require_manager();
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
    admin_require_manager();
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
    admin_require_manager();
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
    $ownerFilter = admin_owner_filter();
    $page = max(1, (int) input('page', 1));
    $perPage = 20;

    [$scope, $params] = admin_owner_scope('c');
    $where = $scope;
    if ($status !== '' && in_array($status, order_statuses(), true)) {
        $where .= ' AND o.status = ?';
        $params[] = $status;
    }
    if ($ownerFilter !== null && !admin_is_sales()) {
        $where .= ' AND c.sales_owner_id = ?';
        $params[] = $ownerFilter;
    }
    if ($q !== '') {
        $like = '%' . $q . '%';
        $where .= ' AND (o.order_no LIKE ? OR c.company_name LIKE ? OR c.email LIKE ? OR o.po_number LIKE ? OR au.name LIKE ?)';
        array_push($params, $like, $like, $like, $like, $like);
    }
    $join = ' FROM orders o JOIN customers c ON c.id = o.customer_id
              LEFT JOIN admin_users au ON au.id = c.sales_owner_id WHERE ';

    $total = (int) db()->value('SELECT COUNT(*)' . $join . $where, $params);
    $pager = admin_pagination($total, $perPage, $page);

    $orders = db()->all(
        'SELECT o.*, c.company_name, c.customer_code, c.sales_owner_id, au.name AS sales_owner_name' . $join
        . $where . ' ORDER BY o.placed_at DESC, o.id DESC
         LIMIT ' . $perPage . ' OFFSET ' . $pager['offset'],
        $params
    );

    $statusCounts = [];
    [$scope, $scopeParams] = admin_owner_scope('c');
    foreach (db()->all(
        'SELECT o.status, COUNT(*) AS total FROM orders o JOIN customers c ON c.id = o.customer_id
         WHERE ' . $scope . ' GROUP BY o.status',
        $scopeParams
    ) as $row) {
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
        'owners' => admin_is_sales() ? [] : admin_assignable_users(),
        'ownerFilter' => $ownerFilter,
        'isSales' => admin_is_sales(),
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
    $order = $id > 0 ? admin_find_order($id) : null;

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
    $order = $id > 0 ? admin_find_order($id) : null;
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
    $order = $id > 0 ? admin_find_order($id) : null;
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
    $ownerFilter = admin_owner_filter();
    $page = max(1, (int) input('page', 1));
    $perPage = 20;

    [$scope, $params] = admin_owner_scope('c');
    $where = $scope;
    if ($q !== '') {
        $like = '%' . $q . '%';
        $where .= ' AND (c.company_name LIKE ? OR c.contact_name LIKE ? OR c.email LIKE ? OR c.customer_code LIKE ? OR c.gstin LIKE ? OR au.name LIKE ?)';
        array_push($params, $like, $like, $like, $like, $like, $like);
    }
    if ($ownerFilter !== null && !admin_is_sales()) {
        $where .= ' AND c.sales_owner_id = ?';
        $params[] = $ownerFilter;
    }
    $join = ' FROM customers c LEFT JOIN admin_users au ON au.id = c.sales_owner_id WHERE ';

    $total = (int) db()->value('SELECT COUNT(*)' . $join . $where, $params);
    $pager = admin_pagination($total, $perPage, $page);

    $customers = db()->all(
        'SELECT c.*, au.name AS sales_owner_name,
                (SELECT COUNT(*) FROM orders o WHERE o.customer_id = c.id) AS order_count,
                (SELECT COALESCE(SUM(o.grand_total_paise),0) FROM orders o WHERE o.customer_id = c.id AND o.status <> \'cancelled\') AS order_value'
        . $join . $where . ' ORDER BY c.company_name LIMIT ' . $perPage . ' OFFSET ' . $pager['offset'],
        $params
    );

    admin_render('customers', [
        'pageTitle' => 'Customers | Ronak Fire admin',
        'adminNav' => 'customers',
        'adminTitle' => 'Customers',
        'customers' => $customers,
        'q' => $q,
        'pager' => $pager,
        'owners' => admin_is_sales() ? [] : admin_assignable_users(),
        'ownerFilter' => $ownerFilter,
        'isSales' => admin_is_sales(),
    ]);
}

function ctrl_admin_customer(array $params): void
{
    Auth::requireAdmin();
    $id = admin_url_id($params);
    $customer = $id > 0 ? admin_find_customer($id) : null;
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
        'owners' => admin_is_sales() ? [] : admin_assignable_users((int) $customer['sales_owner_id']),
        'isSales' => admin_is_sales(),
    ]);
}

function ctrl_admin_customer_save(array $params): void
{
    Auth::requireAdmin();
    require_csrf();

    $id = admin_url_id($params);
    $customer = $id > 0 ? admin_find_customer($id) : null;
    if (!$customer) {
        flash('error', 'Customer not found.');
        redirect('/admin/customers');
    }

    $company = trim((string) post('company_name', ''));
    if ($company === '') {
        flash('error', 'Company name is required.');
        redirect('/admin/customers/' . $id);
    }

    $data = [
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
    ];

    // Only full admins can reassign ownership; sales users keep their own link.
    if (!admin_is_sales()) {
        $ownerId = (int) post('sales_owner_id', 0);
        if ($ownerId > 0) {
            $validOwner = db()->one('SELECT id FROM admin_users WHERE id = ? AND is_active = 1', [$ownerId]);
            if (!$validOwner) {
                flash('error', 'Choose an active sales user as the owner.');
                redirect('/admin/customers/' . $id);
            }
        }
        $data['sales_owner_id'] = $ownerId > 0 ? $ownerId : null;
    }

    db()->update('customers', $data, 'id = :id', ['id' => $id]);

    flash('success', 'Customer account updated.');
    redirect('/admin/customers/' . $id);
}

function ctrl_admin_customer_new(): void
{
    Auth::requireAdmin();
    $stateList = ['Andhra Pradesh','Arunachal Pradesh','Assam','Bihar','Chhattisgarh','Goa','Gujarat','Haryana','Himachal Pradesh','Jharkhand','Karnataka','Kerala','Madhya Pradesh','Maharashtra','Manipur','Meghalaya','Mizoram','Nagaland','Odisha','Punjab','Rajasthan','Sikkim','Tamil Nadu','Telangana','Tripura','Uttar Pradesh','Uttarakhand','West Bengal','Andaman and Nicobar Islands','Chandigarh','Dadra and Nagar Haveli and Daman and Diu','Delhi','Jammu and Kashmir','Ladakh','Lakshadweep','Puducherry'];
    $admin = Auth::admin();
    admin_render('customer-new', [
        'pageTitle' => 'Create customer account | Ronak Fire admin',
        'adminNav' => 'customers',
        'adminTitle' => 'Create customer',
        'states' => $stateList,
        'owners' => admin_is_sales() ? [] : admin_assignable_users(),
        'isSales' => admin_is_sales(),
        'currentAdmin' => $admin,
        'customer' => [
            'sales_owner_id' => (int) ($admin['id'] ?? 0),
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
        // Whoever creates the account becomes its sales owner.
        'sales_owner_id' => (int) (Auth::admin()['id'] ?? 0),
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
    $ownerFilter = admin_owner_filter();
    $page = max(1, (int) input('page', 1));
    $perPage = 20;

    [$scope, $params] = admin_owner_scope('c');
    $where = $scope;
    if ($status !== '') {
        $where .= ' AND e.status = ?';
        $params[] = $status;
    }
    if ($ownerFilter !== null && !admin_is_sales()) {
        $where .= ' AND c.sales_owner_id = ?';
        $params[] = $ownerFilter;
    }
    if ($q !== '') {
        $like = '%' . $q . '%';
        $where .= ' AND (e.name LIKE ? OR e.company LIKE ? OR e.email LIKE ? OR e.product_name LIKE ? OR au.name LIKE ?)';
        array_push($params, $like, $like, $like, $like, $like);
    }
    $join = ' FROM enquiries e LEFT JOIN customers c ON c.id = e.customer_id
              LEFT JOIN admin_users au ON au.id = c.sales_owner_id WHERE ';

    $total = (int) db()->value('SELECT COUNT(*)' . $join . $where, $params);
    $pager = admin_pagination($total, $perPage, $page);
    $enquiries = db()->all(
        'SELECT e.*, c.sales_owner_id, au.name AS sales_owner_name' . $join . $where
        . ' ORDER BY e.created_at DESC, e.id DESC LIMIT ' . $perPage . ' OFFSET ' . $pager['offset'],
        $params
    );

    $statusCounts = [];
    [$scope, $scopeParams] = admin_owner_scope('c');
    foreach (db()->all(
        'SELECT e.status, COUNT(*) AS total FROM enquiries e LEFT JOIN customers c ON c.id = e.customer_id
         WHERE ' . $scope . ' GROUP BY e.status',
        $scopeParams
    ) as $row) {
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
        'owners' => admin_is_sales() ? [] : admin_assignable_users(),
        'ownerFilter' => $ownerFilter,
        'isSales' => admin_is_sales(),
    ]);
}

function ctrl_admin_enquiry(array $params): void
{
    Auth::requireAdmin();
    $id = admin_url_id($params);
    $enquiry = $id > 0 ? admin_find_enquiry($id) : null;
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
    $customer = $enquiry['customer_id'] ? admin_find_customer((int) $enquiry['customer_id']) : null;

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
    $enquiry = $id > 0 ? admin_find_enquiry($id) : null;
    if (!$enquiry) {
        flash('error', 'Enquiry not found.');
        redirect('/admin/enquiries');
    }
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
    $enquiry = admin_find_enquiry($id);
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
    admin_require_manager();

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
    admin_require_manager();
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

/* -------------------------------------------------------------------------
   Sales team & ownership report
   ------------------------------------------------------------------------- */

function ctrl_admin_team(): void
{
    Auth::requireAdmin();
    admin_require_manager();

    $users = db()->all(
        'SELECT u.*,
                (SELECT COUNT(*) FROM customers c WHERE c.sales_owner_id = u.id) AS customer_count,
                (SELECT COUNT(*) FROM customers c WHERE c.sales_owner_id = u.id AND c.is_active = 1) AS active_customer_count
         FROM admin_users u ORDER BY CASE WHEN u.role = \'sales\' THEN 0 ELSE 1 END, u.name'
    );

    admin_render('team', [
        'pageTitle' => 'Sales team | Ronak Fire admin',
        'adminNav' => 'team',
        'adminTitle' => 'Sales team',
        'users' => $users,
    ]);
}

function ctrl_admin_team_new(): void
{
    Auth::requireAdmin();
    admin_require_manager();

    admin_render('team-new', [
        'pageTitle' => 'Add sales user | Ronak Fire admin',
        'adminNav' => 'team',
        'adminTitle' => 'Add sales user',
    ]);
}

function ctrl_admin_team_create(): void
{
    Auth::requireAdmin();
    admin_require_manager();
    require_csrf();

    $name = trim((string) post('name', ''));
    $email = strtolower(trim((string) post('email', '')));
    $role = (string) post('role', 'sales');
    $password = (string) post('password', '');
    $confirm = (string) post('confirm_password', '');

    if ($name === '') {
        flash('error', 'Name is required.');
        redirect('/admin/team/new');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('error', 'Enter a valid email address.');
        redirect('/admin/team/new');
    }
    if (!in_array($role, ['admin', 'sales'], true)) {
        flash('error', 'Choose a valid role.');
        redirect('/admin/team/new');
    }
    if (strlen($password) < 10) {
        flash('error', 'Password must be at least 10 characters.');
        redirect('/admin/team/new');
    }
    if ($password !== $confirm) {
        flash('error', 'The passwords do not match.');
        redirect('/admin/team/new');
    }
    $existing = db()->one('SELECT id FROM admin_users WHERE email = ?', [$email]);
    if ($existing) {
        flash('error', 'A user with that email already exists.');
        redirect('/admin/team/new');
    }

    db()->insert('admin_users', [
        'name' => $name,
        'email' => $email,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'role' => $role,
        'is_active' => 1,
        'created_at' => date('Y-m-d H:i:s'),
    ]);

    flash('success', 'Sales user created. They can now sign in at /admin/login.');
    redirect('/admin/team');
}

function ctrl_admin_team_role(array $params): void
{
    Auth::requireAdmin();
    admin_require_manager();
    require_csrf();

    $id = admin_url_id($params);
    $user = $id > 0 ? db()->one('SELECT * FROM admin_users WHERE id = ?', [$id]) : null;
    if (!$user) {
        flash('error', 'User not found.');
        redirect('/admin/team');
    }
    $role = (string) post('role', '');
    if (!in_array($role, ['admin', 'sales'], true)) {
        flash('error', 'Choose a valid role.');
        redirect('/admin/team');
    }
    db()->update('admin_users', ['role' => $role], 'id = :id', ['id' => $id]);
    flash('success', 'Role updated.');
    redirect('/admin/team');
}

function ctrl_admin_team_toggle(array $params): void
{
    Auth::requireAdmin();
    admin_require_manager();
    require_csrf();

    $id = admin_url_id($params);
    $user = $id > 0 ? db()->one('SELECT * FROM admin_users WHERE id = ?', [$id]) : null;
    if (!$user) {
        flash('error', 'User not found.');
        redirect('/admin/team');
    }
    if ($id === (int) (Auth::admin()['id'] ?? 0)) {
        flash('error', 'You cannot deactivate your own account.');
        redirect('/admin/team');
    }
    $newState = (int) $user['is_active'] === 1 ? 0 : 1;
    db()->update('admin_users', ['is_active' => $newState], 'id = :id', ['id' => $id]);
    flash('success', $newState === 1 ? 'User activated.' : 'User deactivated.');
    redirect('/admin/team');
}

function ctrl_admin_sales_report(): void
{
    Auth::requireAdmin();
    admin_require_manager();

    $summary = db()->all(
        "SELECT au.id, au.name, au.email, au.role,
                (SELECT COUNT(*) FROM customers c WHERE c.sales_owner_id = au.id) AS customer_count,
                (SELECT COUNT(*) FROM customers c WHERE c.sales_owner_id = au.id AND c.is_active = 1) AS active_customer_count,
                (SELECT COUNT(*) FROM orders o JOIN customers c ON c.id = o.customer_id WHERE c.sales_owner_id = au.id) AS order_count,
                (SELECT COALESCE(SUM(o.grand_total_paise),0) FROM orders o JOIN customers c ON c.id = o.customer_id
                 WHERE c.sales_owner_id = au.id AND o.status <> 'cancelled') AS order_value,
                (SELECT COUNT(*) FROM enquiries e JOIN customers c ON c.id = e.customer_id WHERE c.sales_owner_id = au.id) AS enquiry_count
         FROM admin_users au
         WHERE au.is_active = 1 AND au.role IN ('admin', 'sales')
         ORDER BY order_value DESC, customer_count DESC, au.name"
    );
    $unassigned = (int) db()->value('SELECT COUNT(*) FROM customers WHERE sales_owner_id IS NULL');

    admin_render('sales-report', [
        'pageTitle' => 'Sales ownership report | Ronak Fire admin',
        'adminNav' => 'sales-report',
        'adminTitle' => 'Sales ownership report',
        'summary' => $summary,
        'unassigned' => $unassigned,
    ]);
}

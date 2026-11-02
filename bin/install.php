<?php
/**
 * Installer / seeder.
 *
 * Local:
 *   php bin/install.php --fresh
 *
 * Production (database already created in cPanel):
 *   php bin/install.php --seed
 *   php bin/install.php --seed --admin-email=you@ronakfire.com --admin-password='...'
 */
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

$options = [];
foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--')) {
        [$key, $value] = array_pad(explode('=', substr($arg, 2), 2), 2, true);
        $options[$key] = $value;
    }
}

$fresh = isset($options['fresh']);
$forceSeed = $fresh || isset($options['force-seed']);
$seedOnly = isset($options['seed-only']) || isset($options['seed']);

$db = db();
$driver = $db->driver();

echo "Ronak Fire store installer\n";
echo "Driver: {$driver}\n";

if ($fresh) {
    echo "Dropping existing tables...\n";
    $tables = [
        'order_items', 'orders', 'cart_items', 'carts', 'enquiries',
        'bundle_items', 'option_values', 'option_groups', 'products',
        'categories', 'customers', 'admin_users', 'settings',
    ];
    if ($driver === 'mysql') {
        $db->pdo()->exec('SET FOREIGN_KEY_CHECKS=0');
    } else {
        $db->pdo()->exec('PRAGMA foreign_keys=OFF');
    }
    foreach ($tables as $table) {
        $db->pdo()->exec('DROP TABLE IF EXISTS ' . $table);
    }
    if ($driver === 'mysql') {
        $db->pdo()->exec('SET FOREIGN_KEY_CHECKS=1');
    } else {
        $db->pdo()->exec('PRAGMA foreign_keys=ON');
    }
}

if (!$seedOnly || $fresh) {
    $schemaFile = BASE_PATH . '/database/schema.' . ($driver === 'mysql' ? 'mysql' : 'sqlite') . '.sql';
    if (!is_file($schemaFile)) {
        fwrite(STDERR, "Schema file not found: {$schemaFile}\n");
        exit(1);
    }
    echo "Applying schema: database/" . basename($schemaFile) . "\n";
    $schema = file_get_contents($schemaFile);
    $db->pdo()->exec($schema);
}

$catalogCount = (int) $db->value('SELECT COUNT(*) FROM products');
if ($catalogCount > 0 && !$forceSeed) {
    echo "Catalog already has {$catalogCount} products; skipping seed (use --force-seed to replace).\n";
} else {
    if ($catalogCount > 0) {
        echo "Replacing catalog...\n";
        $db->pdo()->exec('DELETE FROM bundle_items');
        $db->pdo()->exec('DELETE FROM option_values');
        $db->pdo()->exec('DELETE FROM option_groups');
        $db->pdo()->exec('DELETE FROM cart_items');
        try { $db->pdo()->exec('DELETE FROM order_items'); } catch (Throwable $e) {}
        try { $db->pdo()->exec('DELETE FROM orders'); } catch (Throwable $e) {}
        $db->pdo()->exec('DELETE FROM products');
        $db->pdo()->exec('DELETE FROM categories');
    }

    $seed = require BASE_PATH . '/database/seed.php';
    $now = date('Y-m-d H:i:s');

    $categoryIds = [];
    foreach ($seed['categories'] as $index => $category) {
        $categoryIds[$category['slug']] = $db->insert('categories', [
            'slug' => $category['slug'],
            'name' => $category['name'],
            'short_name' => $category['short_name'] ?? $category['name'],
            'tagline' => $category['tagline'] ?? '',
            'description' => $category['description'] ?? '',
            'image' => $category['image'] ?? '',
            'sort_order' => $category['sort'] ?? $index,
            'is_active' => 1,
        ]);
    }

    $productIds = [];
    $pendingBundles = [];

    foreach ($seed['products'] as $index => $product) {
        $categoryId = $categoryIds[$product['category']] ?? null;
        if (!$categoryId) {
            fwrite(STDERR, "Unknown category for {$product['slug']}\n");
            exit(1);
        }

        $productId = $db->insert('products', [
            'category_id' => $categoryId,
            'slug' => $product['slug'],
            'name' => $product['name'],
            'sku' => $product['sku'] ?? '',
            'product_type' => $product['type'] ?? 'simple',
            'short_description' => $product['short'] ?? '',
            'description' => $product['description'] ?? '',
            'specs_json' => json_encode($product['specs'] ?? [], JSON_UNESCAPED_UNICODE),
            'image' => $product['image'] ?? '',
            'gallery_json' => json_encode($product['gallery'] ?? [], JSON_UNESCAPED_UNICODE),
            'datasheet' => $product['datasheet'] ?? '',
            'base_price_paise' => (int) ($product['base'] ?? 0),
            'dim_rate_paise_sqm' => (int) ($product['dim_rate'] ?? 0),
            'price_note' => $product['price_note'] ?? '',
            'unit' => $product['unit'] ?? 'Nos',
            'hsn_code' => $product['hsn'] ?? '',
            'gst_rate_bp' => (int) ($product['gst'] ?? 1800),
            'min_qty' => (int) ($product['min_qty'] ?? 1),
            'lead_time' => $product['lead_time'] ?? '',
            'warranty' => $product['warranty'] ?? '',
            'is_featured' => (int) ($product['featured'] ?? 0),
            'is_active' => 1,
            'sort_order' => $product['sort'] ?? $index,
            'meta_title' => $product['meta_title'] ?? ($product['name'] . ' | Ronak Fire'),
            'meta_description' => $product['meta_description'] ?? ($product['short'] ?? ''),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $productIds[$product['slug']] = $productId;

        foreach ($product['groups'] ?? [] as $groupIndex => $group) {
            $groupId = $db->insert('option_groups', [
                'product_id' => $productId,
                'name' => $group['name'],
                'slug' => $group['slug'],
                'help_text' => $group['help'] ?? '',
                'input_type' => $group['type'] ?? 'radio',
                'is_required' => (int) ($group['required'] ?? 1),
                'sort_order' => $groupIndex,
                'min_value' => isset($group['min']) ? (int) $group['min'] : null,
                'max_value' => isset($group['max']) ? (int) $group['max'] : null,
                'step_value' => isset($group['step']) ? (int) $group['step'] : null,
                'default_w' => isset($group['default_w']) ? (int) $group['default_w'] : null,
                'default_h' => isset($group['default_h']) ? (int) $group['default_h'] : null,
                'default_d' => isset($group['default_d']) ? (int) $group['default_d'] : null,
                'unit_label' => $group['unit'] ?? '',
            ]);

            foreach ($group['values'] ?? [] as $valueIndex => $value) {
                $db->insert('option_values', [
                    'group_id' => $groupId,
                    'label' => $value['label'],
                    'sku_suffix' => $value['sku'] ?? '',
                    'price_delta_paise' => (int) ($value['delta'] ?? 0),
                    'price_multiplier_bp' => (int) ($value['mult'] ?? 0),
                    'is_default' => (int) ($value['default'] ?? 0),
                    'sort_order' => $valueIndex,
                    'meta_json' => '',
                ]);
            }
        }

        if (!empty($product['bundles'])) {
            $pendingBundles[] = ['product' => $productId, 'items' => $product['bundles']];
        }
    }

    foreach ($pendingBundles as $bundle) {
        foreach ($bundle['items'] as $sort => $item) {
            if (!isset($productIds[$item['slug']])) {
                fwrite(STDERR, "Bundle component not found: {$item['slug']}\n");
                continue;
            }
            $db->insert('bundle_items', [
                'product_id' => $bundle['product'],
                'component_product_id' => $productIds[$item['slug']],
                'qty' => (int) $item['qty'],
                'sort_order' => $sort,
            ]);
        }
    }

    foreach ($seed['settings'] as $key => $value) {
        $existing = $db->one('SELECT `key` FROM settings WHERE `key` = ?', [$key]);
        if ($existing) {
            $db->update('settings', ['value' => $value], '`key` = :key', ['key' => $key]);
        } else {
            $db->insert('settings', ['key' => $key, 'value' => $value]);
        }
    }

    echo 'Seeded ' . count($seed['categories']) . ' categories and ' . count($seed['products']) . " products.\n";
}

/* -------------------------------------------------------------------------
   Admin account
   ------------------------------------------------------------------------- */

$email = strtolower((string) ($options['admin-email'] ?? getenv('RF_ADMIN_EMAIL') ?: 'admin@ronakfire.com'));
$password = (string) ($options['admin-password'] ?? getenv('RF_ADMIN_PASSWORD') ?: '');
$resetAdmin = isset($options['reset-admin']);
$existingAdmin = $db->one('SELECT id, email FROM admin_users WHERE email = ?', [$email]);

if ($existingAdmin && !$resetAdmin) {
    echo "Admin account already exists: {$email} (use --reset-admin to change the password).\n";
} else {
    if ($password === '') {
        if (is_local()) {
            $password = 'Ronak@2026!';
        } else {
            $password = bin2hex(random_bytes(8)) . 'Aa1!';
        }
    }
    if (strlen($password) < 10) {
        fwrite(STDERR, "Admin password must be at least 10 characters.\n");
        exit(1);
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    if ($existingAdmin) {
        $db->update('admin_users', ['password_hash' => $hash], 'id = :id', ['id' => $existingAdmin['id']]);
        echo "Admin password reset.\n";
    } else {
        $db->insert('admin_users', [
            'name' => 'Store Administrator',
            'email' => $email,
            'password_hash' => $hash,
            'role' => 'admin',
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    echo "\n  Admin URL:  " . abs_url('/admin') . "\n";
    echo "  Email:      {$email}\n";
    echo "  Password:   {$password}\n";
    echo "  Change this password after first login.\n\n";
}

echo "Done.\n";

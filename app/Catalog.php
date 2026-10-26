<?php

namespace App;

final class Catalog
{
    public static function categories(bool $activeOnly = true): array
    {
        $sql = 'SELECT * FROM categories';
        if ($activeOnly) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY sort_order, name';
        return db()->all($sql);
    }

    public static function findCategory(string $slug): ?array
    {
        return db()->one('SELECT * FROM categories WHERE slug = ? AND is_active = 1', [$slug]);
    }

    public static function products(array $filters = []): array
    {
        $sql = 'SELECT p.*, c.name AS category_name, c.slug AS category_slug
                FROM products p
                JOIN categories c ON c.id = p.category_id
                WHERE p.is_active = 1';
        $params = [];

        if (!empty($filters['category'])) {
            $sql .= ' AND c.slug = ?';
            $params[] = $filters['category'];
        }
        if (!empty($filters['q'])) {
            $like = '%' . $filters['q'] . '%';
            $sql .= ' AND (p.name LIKE ? OR p.short_description LIKE ? OR p.sku LIKE ? OR p.description LIKE ?)';
            array_push($params, $like, $like, $like, $like);
        }
        if (!empty($filters['configurable'])) {
            $sql .= " AND p.product_type = 'configurable'";
        }
        if (!empty($filters['featured'])) {
            $sql .= ' AND p.is_featured = 1';
        }

        $sql .= ' ORDER BY p.sort_order, p.name';
        $products = db()->all($sql, $params);

        $sort = $filters['sort'] ?? '';
        if ($sort === 'price_asc' || $sort === 'price_desc') {
            $customer = current_customer();
            $discount = (int) ($customer['discount_bp'] ?? 0);
            // Resolve each product's from-price once instead of on every comparison.
            $fromPrices = [];
            foreach ($products as $product) {
                $fromPrices[(int) $product['id']] = self::fromPrice($product, $discount);
            }
            usort($products, static function (array $a, array $b) use ($sort, $fromPrices): int {
                $pa = $fromPrices[(int) $a['id']] ?? 0;
                $pb = $fromPrices[(int) $b['id']] ?? 0;
                return $sort === 'price_asc' ? $pa <=> $pb : $pb <=> $pa;
            });
        } elseif ($sort === 'newest') {
            usort($products, static fn (array $a, array $b): int => strcmp((string) $b['created_at'], (string) $a['created_at']));
        }

        if (isset($filters['limit'])) {
            $offset = (int) ($filters['offset'] ?? 0);
            $products = array_slice($products, $offset, (int) $filters['limit']);
        }

        return $products;
    }

    public static function count(array $filters = []): int
    {
        unset($filters['limit'], $filters['offset'], $filters['sort']);
        return count(self::products($filters));
    }

    public static function featured(int $limit = 4): array
    {
        return self::products(['featured' => 1, 'limit' => $limit]);
    }

    public static function countsByCategory(): array
    {
        $rows = db()->all(
            'SELECT c.slug, COUNT(p.id) AS total
             FROM categories c
             LEFT JOIN products p ON p.category_id = c.id AND p.is_active = 1
             WHERE c.is_active = 1
             GROUP BY c.slug'
        );
        $counts = [];
        foreach ($rows as $row) {
            $counts[$row['slug']] = (int) $row['total'];
        }
        return $counts;
    }

    public static function find(string $slug): ?array
    {
        return db()->one(
            'SELECT p.*, c.name AS category_name, c.slug AS category_slug
             FROM products p JOIN categories c ON c.id = p.category_id
             WHERE p.slug = ? AND p.is_active = 1',
            [$slug]
        );
    }

    public static function findById(int $id): ?array
    {
        return db()->one(
            'SELECT p.*, c.name AS category_name, c.slug AS category_slug
             FROM products p JOIN categories c ON c.id = p.category_id
             WHERE p.id = ?',
            [$id]
        );
    }

    /**
     * Option groups with their values, ordered for the configurator.
     */
    public static function optionGroups(int $productId): array
    {
        $groups = db()->all(
            'SELECT * FROM option_groups WHERE product_id = ? ORDER BY sort_order, id',
            [$productId]
        );
        if (!$groups) {
            return [];
        }

        $ids = array_map(static fn (array $g): int => (int) $g['id'], $groups);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $values = db()->all(
            "SELECT * FROM option_values WHERE group_id IN ($placeholders) ORDER BY sort_order, id",
            $ids
        );

        $byGroup = [];
        foreach ($values as $value) {
            $byGroup[(int) $value['group_id']][] = $value;
        }
        foreach ($groups as &$group) {
            $group['values'] = $byGroup[(int) $group['id']] ?? [];
        }
        unset($group);

        return $groups;
    }

    public static function bundleItems(int $productId): array
    {
        return db()->all(
            'SELECT bi.qty, p.id, p.slug, p.name, p.sku, p.image, p.unit
             FROM bundle_items bi
             JOIN products p ON p.id = bi.component_product_id
             WHERE bi.product_id = ?
             ORDER BY bi.sort_order, bi.id',
            [$productId]
        );
    }

    public static function related(array $product, int $limit = 4): array
    {
        $rows = db()->all(
            'SELECT p.*, c.name AS category_name, c.slug AS category_slug
             FROM products p JOIN categories c ON c.id = p.category_id
             WHERE p.is_active = 1 AND p.id <> ? AND p.category_id = ?
             ORDER BY p.is_featured DESC, p.sort_order, p.name
             LIMIT ' . (int) $limit,
            [$product['id'], $product['category_id']]
        );

        if (count($rows) < $limit) {
            $exclude = array_merge([(int) $product['id']], array_map(static fn (array $r): int => (int) $r['id'], $rows));
            $placeholders = implode(',', array_fill(0, count($exclude), '?'));
            $extra = db()->all(
                "SELECT p.*, c.name AS category_name, c.slug AS category_slug
                 FROM products p JOIN categories c ON c.id = p.category_id
                 WHERE p.is_active = 1 AND p.id NOT IN ($placeholders)
                 ORDER BY p.is_featured DESC, p.sort_order, p.name
                 LIMIT " . (int) ($limit - count($rows)),
                $exclude
            );
            $rows = array_merge($rows, $extra);
        }

        return $rows;
    }

    /**
     * Lowest achievable unit price for a product, including option deltas,
     * dimension charge and the customer's discount.
     */
    public static function fromPrice(array $product, int $discountBp = 0): int
    {
        $base = (int) $product['base_price_paise'];

        if (($product['product_type'] ?? 'simple') === 'configurable') {
            $groups = self::optionGroups((int) $product['id']);
            $deltas = 0;
            $multBp = 0;
            foreach ($groups as $group) {
                if ($group['input_type'] === 'dimension') {
                    $w = (int) ($group['default_w'] ?: $group['min_value'] ?: 500);
                    $h = (int) ($group['default_h'] ?: $group['min_value'] ?: 500);
                    $d = (int) ($group['default_d'] ?: $group['min_value'] ?: 200);
                    $area = 2 * (($w * $h) + ($w * $d) + ($h * $d)) / 1000000;
                    $base += (int) round($area * (int) $product['dim_rate_paise_sqm']);
                    continue;
                }
                if (!$group['values']) {
                    continue;
                }
                $min = null;
                foreach ($group['values'] as $value) {
                    $candidate = (int) $value['price_delta_paise'];
                    if ($min === null || $candidate < $min) {
                        $min = $candidate;
                        $multBp = (int) $value['price_multiplier_bp'];
                    }
                }
                $deltas += (int) $min;
            }
            $base += $deltas;
            if ($multBp !== 0) {
                $base = (int) round($base * (10000 + $multBp) / 10000);
            }
        }

        if ($discountBp > 0) {
            $base = (int) round($base * (10000 - $discountBp) / 10000);
        }

        return max(0, $base);
    }

    public static function badges(array $product): array
    {
        $badges = [];
        $specs = decode_json($product['specs_json'] ?? null);
        foreach ($specs as $spec) {
            $label = strtolower((string) ($spec['label'] ?? ''));
            $value = (string) ($spec['value'] ?? '');
            if (str_contains($label, 'approval') || str_contains($label, 'standard') || str_contains($label, 'reference')) {
                if (stripos($value, 'ISI') !== false || stripos($value, 'IS:') !== false || stripos($value, 'IS ') !== false || stripos($value, 'BIS') !== false) {
                    $badges[] = 'IS / BIS tested';
                    break;
                }
            }
        }
        if (!empty($product['warranty'])) {
            $badges[] = $product['warranty'];
        }
        if (($product['product_type'] ?? '') === 'bundle') {
            $badges[] = 'Kit';
        } elseif (($product['product_type'] ?? '') === 'configurable') {
            $badges[] = 'Configurable';
        }
        return array_values(array_unique($badges));
    }
}

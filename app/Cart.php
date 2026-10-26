<?php

namespace App;

final class Cart
{
    public static function cartId(int $customerId, bool $create = true): ?int
    {
        $row = db()->one('SELECT id FROM carts WHERE customer_id = ?', [$customerId]);
        if ($row) {
            return (int) $row['id'];
        }
        if (!$create) {
            return null;
        }
        $now = date('Y-m-d H:i:s');
        return db()->insert('carts', [
            'customer_id' => $customerId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * @return array{ok: bool, message: string, count?: int, errors?: array}
     */
    public static function add(int $customerId, int $productId, int $qty, array $selection): array
    {
        $product = Catalog::findById($productId);
        if (!$product) {
            return ['ok' => false, 'message' => 'That product is no longer available.'];
        }

        $qty = max((int) $product['min_qty'], min(999, $qty));
        $quote = Pricing::quote($product, $selection);
        if (!$quote['ok']) {
            return ['ok' => false, 'message' => implode(' ', $quote['errors']), 'errors' => $quote['errors']];
        }

        $options = $quote['options'];
        $hash = Pricing::hash($productId, $options);
        $cartId = self::cartId($customerId);
        $now = date('Y-m-d H:i:s');

        $existing = db()->one(
            'SELECT id, qty FROM cart_items WHERE cart_id = ? AND product_id = ? AND config_hash = ?',
            [$cartId, $productId, $hash]
        );

        if ($existing) {
            $newQty = min(999, (int) $existing['qty'] + $qty);
            db()->update(
                'cart_items',
                [
                    'qty' => $newQty,
                    'unit_price_paise' => $quote['unit_price_paise'],
                    'gst_rate_bp' => (int) $product['gst_rate_bp'],
                    'options_json' => json_encode($options, JSON_UNESCAPED_UNICODE),
                    'updated_at' => $now,
                ],
                'id = :id',
                ['id' => $existing['id']]
            );
        } else {
            db()->insert('cart_items', [
                'cart_id' => $cartId,
                'product_id' => $productId,
                'qty' => $qty,
                'options_json' => json_encode($options, JSON_UNESCAPED_UNICODE),
                'config_hash' => $hash,
                'unit_price_paise' => $quote['unit_price_paise'],
                'gst_rate_bp' => (int) $product['gst_rate_bp'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        db()->update('carts', ['updated_at' => $now], 'id = :id', ['id' => $cartId]);

        return [
            'ok' => true,
            'message' => $product['name'] . ' added to your order.',
            'count' => self::count($customerId),
        ];
    }

    public static function items(int $customerId): array
    {
        $cartId = self::cartId($customerId, false);
        if (!$cartId) {
            return [];
        }

        $items = db()->all(
            'SELECT ci.*, p.name, p.slug, p.sku, p.image, p.unit, p.min_qty, p.product_type,
                    p.short_description, c.name AS category_name
             FROM cart_items ci
             JOIN products p ON p.id = ci.product_id
             JOIN categories c ON c.id = p.category_id
             WHERE ci.cart_id = ?
             ORDER BY ci.id',
            [$cartId]
        );

        foreach ($items as &$item) {
            $item['options'] = decode_json($item['options_json']);
            $item['line_subtotal_paise'] = (int) $item['unit_price_paise'] * (int) $item['qty'];
            $item['line_gst_paise'] = (int) round($item['line_subtotal_paise'] * (int) $item['gst_rate_bp'] / 10000);
            $item['line_total_paise'] = $item['line_subtotal_paise'] + $item['line_gst_paise'];
        }
        unset($item);

        return $items;
    }

    public static function updateQty(int $customerId, int $itemId, int $qty): bool
    {
        $cartId = self::cartId($customerId, false);
        if (!$cartId) {
            return false;
        }

        $item = db()->one(
            'SELECT ci.id, p.min_qty
             FROM cart_items ci JOIN products p ON p.id = ci.product_id
             WHERE ci.id = ? AND ci.cart_id = ?',
            [$itemId, $cartId]
        );
        if (!$item) {
            return false;
        }

        $qty = max((int) $item['min_qty'], min(999, $qty));
        db()->update(
            'cart_items',
            ['qty' => $qty, 'updated_at' => date('Y-m-d H:i:s')],
            'id = :id',
            ['id' => $itemId]
        );
        db()->update('carts', ['updated_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $cartId]);
        return true;
    }

    public static function remove(int $customerId, int $itemId): bool
    {
        $cartId = self::cartId($customerId, false);
        if (!$cartId) {
            return false;
        }
        return db()->run(
            'DELETE FROM cart_items WHERE id = ? AND cart_id = ?',
            [$itemId, $cartId]
        )->rowCount() > 0;
    }

    public static function clear(int $customerId): void
    {
        $cartId = self::cartId($customerId, false);
        if ($cartId) {
            db()->run('DELETE FROM cart_items WHERE cart_id = ?', [$cartId]);
            db()->update('carts', ['updated_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $cartId]);
        }
    }

    public static function count(int $customerId): int
    {
        return (int) db()->value(
            'SELECT COALESCE(SUM(ci.qty), 0)
             FROM cart_items ci JOIN carts c ON c.id = ci.cart_id
             WHERE c.customer_id = ?',
            [$customerId]
        );
    }

    public static function totals(array $items): array
    {
        $subtotal = 0;
        $gst = 0;
        $count = 0;
        foreach ($items as $item) {
            $subtotal += (int) $item['line_subtotal_paise'];
            $gst += (int) $item['line_gst_paise'];
            $count += (int) $item['qty'];
        }

        return [
            'subtotal_paise' => $subtotal,
            'gst_paise' => $gst,
            'total_paise' => $subtotal + $gst,
            'item_count' => $count,
        ];
    }
}

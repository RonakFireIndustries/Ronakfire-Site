#!/usr/bin/env php
<?php
declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}
require BASE_PATH . '/app/bootstrap.php';

$url = (string) config('portal.stock_url', '');
if ($url === '') {
    echo "portal.stock_url not configured\n";
    exit(0);
}

try {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
    $resp = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code < 200 || $code >= 300 || !is_string($resp)) {
        echo "Failed to fetch stock feed (HTTP {$code})\n";
        exit(1);
    }
    $data = json_decode($resp, true);
    if (!is_array($data)) {
        echo "Invalid stock feed JSON\n";
        exit(1);
    }

    $now = date('Y-m-d H:i:s');
    $updated = 0;
    foreach ($data as $row) {
        if (!is_array($row)) {
            continue;
        }
        $sku = trim((string) ($row['sku'] ?? $row['product_sku'] ?? ''));
        if ($sku === '') {
            continue;
        }
        $qty = isset($row['qty']) ? (int) $row['qty'] : (isset($row['stock_qty']) ? (int) $row['stock_qty'] : null);
        $lead = isset($row['lead_time']) ? trim((string) $row['lead_time']) : null;
        $product = db()->one('SELECT id FROM products WHERE sku = ?', [$sku]);
        if (!$product) {
            continue;
        }
        $fields = ['stock_updated_at' => $now];
        if ($qty !== null) {
            $fields['stock_qty'] = $qty;
        }
        if ($lead !== null && $lead !== '') {
            $fields['lead_time'] = $lead;
        }
        db()->update('products', $fields, 'id = :id', ['id' => (int) $product['id']]);
        $updated++;
    }
    echo "Updated {$updated} products\n";
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

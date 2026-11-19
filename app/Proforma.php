<?php

declare(strict_types=1);

namespace App;

use RuntimeException;

/**
 * Proforma invoices raised from quote requests.
 *
 * A proforma is backed by a real order so the existing invoice, customer
 * order history and document screens keep working. Prices, quantities, GST,
 * freight and discount are supplied by the sales user and are never derived
 * from the public storefront price.
 */
final class Proforma
{
    /**
     * Build the editable line list shown on the admin enquiry page.
     *
     * @param array $items order_items rows for an existing proforma
     * @return list<array<string, mixed>>
     */
    public static function linesForEnquiry(array $enquiry, ?array $order, array $items): array
    {
        if ($order && $items) {
            $lines = [];
            foreach ($items as $item) {
                $lines[] = [
                    'product_id' => (int) ($item['product_id'] ?? 0),
                    'name' => (string) ($item['product_name'] ?? ''),
                    'sku' => (string) ($item['sku'] ?? ''),
                    'options_json' => (string) ($item['options_json'] ?? ''),
                    'qty' => max(1, (int) ($item['qty'] ?? 1)),
                    'unit_price' => self::rupeeInput((int) ($item['unit_price_paise'] ?? 0)),
                    'gst_rate' => self::percentInput((int) ($item['gst_rate_bp'] ?? 1800)),
                ];
            }
            return $lines;
        }

        $config = enquiry_configuration((string) ($enquiry['configuration'] ?? ''));

        if ($config['items']) {
            $lines = [];
            foreach ($config['items'] as $item) {
                $lines[] = [
                    'product_id' => (int) ($item['product_id'] ?? 0),
                    'name' => (string) ($item['name'] ?? ''),
                    'sku' => (string) ($item['sku'] ?? ''),
                    'options_json' => self::optionsJsonFor($item['options'] ?? []),
                    'qty' => max(1, (int) ($item['qty'] ?? 1)),
                    'unit_price' => self::rupeeInput((int) ($item['unit_price_paise'] ?? 0)),
                    'gst_rate' => self::percentInput(1800),
                ];
            }
            return $lines;
        }

        $productId = (int) ($enquiry['product_id'] ?? 0);
        $product = $productId > 0 ? Catalog::findById($productId) : null;
        $summary = array_values(array_filter(array_map('trim', explode("\n", $config['text'])), 'strlen'));

        return [[
            'product_id' => $productId,
            'name' => (string) ($enquiry['product_name'] ?? '') ?: ($product['name'] ?? 'Requirement'),
            'sku' => (string) ($product['sku'] ?? ''),
            'options_json' => self::optionsJsonFor($summary),
            'qty' => max(1, (int) ($enquiry['qty'] ?? 1)),
            'unit_price' => self::rupeeInput((int) ($product['base_price_paise'] ?? 0)),
            'gst_rate' => self::percentInput((int) ($product['gst_rate_bp'] ?? 1800)),
        ]];
    }

    /**
     * Validate and normalise the submitted proforma form.
     *
     * @return array{lines:list<array<string,mixed>>,meta:array<string,mixed>,errors:list<string>}
     */
    public static function normalize(array $post): array
    {
        $errors = [];
        $lines = [];
        $rawLines = $post['lines'] ?? [];
        if (!is_array($rawLines)) {
            $rawLines = [];
        }

        foreach ($rawLines as $raw) {
            if (!is_array($raw)) {
                continue;
            }
            $name = trim((string) ($raw['name'] ?? ''));
            $sku = trim((string) ($raw['sku'] ?? ''));
            $qtyRaw = trim((string) ($raw['qty'] ?? ''));
            $priceRaw = trim((string) ($raw['unit_price'] ?? ''));
            $gstRaw = trim((string) ($raw['gst_rate'] ?? '18'));

            if ($name === '' && $sku === '' && $qtyRaw === '' && $priceRaw === '') {
                continue;
            }
            if ($name === '') {
                $errors[] = 'Enter a description for every proforma line.';
            }

            $qty = (int) $qtyRaw;
            if ($qty < 1) {
                $errors[] = 'Quantity must be at least 1 for every line.';
                $qty = 1;
            }

            $unitPricePaise = parse_money_to_paise($priceRaw);
            if ($unitPricePaise < 0) {
                $errors[] = 'Unit prices cannot be negative.';
                $unitPricePaise = 0;
            }

            $gstRate = is_numeric($gstRaw) ? (float) $gstRaw : 0.0;
            if ($gstRate < 0 || $gstRate > 100) {
                $errors[] = 'GST must be between 0% and 100%.';
                $gstRate = max(0.0, min(100.0, $gstRate));
            }

            $optionsJson = trim((string) ($raw['options_json'] ?? ''));
            if ($optionsJson !== '' && !json_decode($optionsJson, true)) {
                $optionsJson = '';
            }

            $lines[] = [
                'product_id' => (int) ($raw['product_id'] ?? 0),
                'name' => $name,
                'sku' => $sku,
                'options_json' => $optionsJson,
                'qty' => $qty,
                'unit_price' => $priceRaw,
                'gst_rate' => $gstRaw,
                'unit_price_paise' => $unitPricePaise,
                'gst_rate_bp' => (int) round($gstRate * 100),
            ];
        }

        if (!$lines) {
            $errors[] = 'Add at least one proforma line.';
        }

        $email = trim((string) ($post['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid customer email address.';
        }

        $validUntil = trim((string) ($post['valid_until'] ?? ''));
        if ($validUntil !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $validUntil)) {
            $errors[] = 'Enter a valid quote validity date.';
            $validUntil = '';
        }

        $freightRaw = trim((string) ($post['freight'] ?? '0'));
        $discountRaw = trim((string) ($post['discount'] ?? '0'));
        $freightPaise = max(0, parse_money_to_paise($freightRaw));
        $discountPaise = max(0, parse_money_to_paise($discountRaw));

        $meta = [
            'email' => $email,
            'po_number' => trim((string) ($post['po_number'] ?? '')),
            'notes' => trim((string) ($post['notes'] ?? '')),
            'valid_until' => $validUntil,
            'freight' => $freightRaw,
            'discount' => $discountRaw,
            'freight_paise' => $freightPaise,
            'discount_paise' => $discountPaise,
        ];

        return [
            'lines' => $lines,
            'meta' => $meta,
            'errors' => array_values(array_unique($errors)),
        ];
    }

    /**
     * Calculate line and order totals in paise.
     */
    public static function calculate(array $lines, int $freightPaise, int $discountPaise): array
    {
        $subtotal = 0;
        $gst = 0;
        foreach ($lines as $index => $line) {
            $unitPricePaise = array_key_exists('unit_price_paise', $line)
                ? (int) $line['unit_price_paise']
                : parse_money_to_paise((string) ($line['unit_price'] ?? '0'));
            $gstRateBp = array_key_exists('gst_rate_bp', $line)
                ? (int) $line['gst_rate_bp']
                : (int) round((float) ($line['gst_rate'] ?? 0) * 100);
            $lines[$index]['unit_price_paise'] = $unitPricePaise;
            $lines[$index]['gst_rate_bp'] = $gstRateBp;
            $lineSubtotal = (int) $line['qty'] * $unitPricePaise;
            $lineGst = (int) round($lineSubtotal * $gstRateBp / 10000);
            $lines[$index]['line_subtotal_paise'] = $lineSubtotal;
            $lines[$index]['line_gst_paise'] = $lineGst;
            $lines[$index]['line_total_paise'] = $lineSubtotal + $lineGst;
            $subtotal += $lineSubtotal;
            $gst += $lineGst;
        }

        $gross = $subtotal + $gst + $freightPaise;
        $discount = min($discountPaise, $gross);

        return [
            'lines' => $lines,
            'subtotal_paise' => $subtotal,
            'gst_paise' => $gst,
            'freight_paise' => $freightPaise,
            'discount_paise' => $discount,
            'grand_total_paise' => $gross - $discount,
        ];
    }

    /**
     * Form values for the first render of the editor.
     */
    public static function defaultForm(array $enquiry, ?array $customer, ?array $proforma, array $lines): array
    {
        $config = enquiry_configuration((string) ($enquiry['configuration'] ?? ''));

        return [
            'email' => (string) ($proforma['email'] ?? $enquiry['email'] ?? $customer['email'] ?? ''),
            'po_number' => (string) ($proforma['po_number'] ?? $config['po_number'] ?? ''),
            'notes' => (string) ($proforma['notes'] ?? ''),
            'valid_until' => (string) ($proforma['valid_until'] ?? date('Y-m-d', strtotime('+14 days'))),
            'freight' => self::rupeeInput((int) ($proforma['freight_paise'] ?? 0)),
            'discount' => self::rupeeInput((int) ($proforma['discount_paise'] ?? 0)),
            'lines' => $lines,
        ];
    }

    /**
     * Find or create the customer that owns the proforma.
     *
     * Guest quote requests become customer accounts so the document can be
     * viewed in their account. Temporary credentials are returned so the
     * caller can include them in the proforma email.
     *
     * @return array{customer:array,temp_password:string}
     */
    public static function customerForEnquiry(array $enquiry, string $email): array
    {
        $customerId = (int) ($enquiry['customer_id'] ?? 0);
        if ($customerId > 0) {
            $customer = db()->one('SELECT * FROM customers WHERE id = ?', [$customerId]);
            if ($customer) {
                return ['customer' => $customer, 'temp_password' => ''];
            }
        }

        $existing = db()->one('SELECT * FROM customers WHERE email = ?', [$email]);
        if ($existing) {
            db()->update('enquiries', [
                'customer_id' => (int) $existing['id'],
                'updated_at' => date('Y-m-d H:i:s'),
            ], 'id = :id', ['id' => (int) $enquiry['id']]);
            return ['customer' => $existing, 'temp_password' => ''];
        }

        $now = date('Y-m-d H:i:s');
        $code = '';
        for ($i = 0; $i < 8; $i++) {
            $candidate = 'RF-' . strtoupper(substr(sha1($email . (int) $enquiry['id'] . $i), 0, 6));
            if (!db()->one('SELECT id FROM customers WHERE portal_id = ?', [$candidate])) {
                $code = $candidate;
                break;
            }
        }
        if ($code === '') {
            throw new RuntimeException('Could not allocate a customer code.');
        }

        $tempPassword = bin2hex(random_bytes(6));
        $customerId = db()->insert('customers', [
            'portal_id' => $code,
            'customer_code' => $code,
            'company_name' => trim((string) ($enquiry['company'] ?? '')) ?: trim((string) ($enquiry['name'] ?? 'Customer')),
            'contact_name' => (string) ($enquiry['name'] ?? ''),
            'email' => $email,
            'phone' => (string) ($enquiry['phone'] ?? ''),
            'gstin' => strtoupper(trim((string) ($enquiry['gstin'] ?? ''))),
            'price_tier' => 'standard',
            'sales_owner_id' => (int) (Auth::admin()['id'] ?? 0) ?: null,
            'is_active' => 1,
            'password_hash' => password_hash($tempPassword, PASSWORD_DEFAULT),
            'password_set_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        db()->update('enquiries', [
            'customer_id' => $customerId,
            'updated_at' => $now,
        ], 'id = :id', ['id' => (int) $enquiry['id']]);

        $customer = db()->one('SELECT * FROM customers WHERE id = ?', [$customerId]);
        return ['customer' => $customer ?? [], 'temp_password' => $tempPassword];
    }

    /**
     * Persist the proforma, its backing order and its line items.
     *
     * @return array{proforma:array,order:array,items:array,customer:array,billing:array,shipping:array}
     */
    public static function persist(
        array $enquiry,
        array $payload,
        array $customer,
        int $adminId
    ): array {
        $now = date('Y-m-d H:i:s');
        $totals = $payload['totals'];
        $meta = $payload['meta'];
        $enquiryId = (int) $enquiry['id'];

        return db()->transaction(function () use (
            $enquiry,
            $enquiryId,
            $payload,
            $totals,
            $meta,
            $customer,
            $adminId,
            $now
        ): array {
            $existing = db()->one('SELECT * FROM proformas WHERE enquiry_id = ?', [$enquiryId]);
            $existingOrder = $existing
                ? db()->one('SELECT * FROM orders WHERE id = ?', [(int) $existing['order_id']])
                : null;

            $billing = self::billingFor($enquiry, $customer, $meta['email']);
            $shipping = $billing;

            $orderData = [
                'customer_id' => (int) $customer['id'],
                'status' => 'placed',
                'po_number' => $meta['po_number'],
                'notes' => $meta['notes'],
                'subtotal_paise' => $totals['subtotal_paise'],
                'gst_paise' => $totals['gst_paise'],
                'freight_paise' => $totals['freight_paise'],
                'discount_paise' => $totals['discount_paise'],
                'grand_total_paise' => $totals['grand_total_paise'],
                'billing_json' => json_encode($billing, JSON_UNESCAPED_UNICODE),
                'shipping_json' => json_encode($shipping, JSON_UNESCAPED_UNICODE),
                'admin_note' => 'Proforma created from quote request #' . $enquiryId . '.',
                'valid_until' => $meta['valid_until'] ?: null,
                'source_enquiry_id' => $enquiryId,
                'updated_at' => $now,
            ];

            if ($existingOrder) {
                $orderId = (int) $existingOrder['id'];
                db()->update('orders', $orderData, 'id = :id', ['id' => $orderId]);
                db()->run('DELETE FROM order_items WHERE order_id = ?', [$orderId]);
                $orderNo = (string) $existingOrder['order_no'];
                $order = db()->one('SELECT * FROM orders WHERE id = ?', [$orderId]);
            } else {
                $orderData['order_no'] = generate_order_no();
                $orderData['placed_at'] = $now;
                $orderData['created_at'] = $now;
                $orderId = db()->insert('orders', $orderData);
                $orderNo = $orderData['order_no'];
                $order = db()->one('SELECT * FROM orders WHERE id = ?', [$orderId]);
            }

            foreach ($totals['lines'] as $line) {
                db()->insert('order_items', [
                    'order_id' => $orderId,
                    'product_id' => (int) $line['product_id'] ?: null,
                    'product_name' => $line['name'],
                    'sku' => $line['sku'],
                    'options_json' => $line['options_json'] ?: null,
                    'qty' => (int) $line['qty'],
                    'unit_price_paise' => (int) $line['unit_price_paise'],
                    'gst_rate_bp' => (int) $line['gst_rate_bp'],
                    'line_subtotal_paise' => (int) $line['line_subtotal_paise'],
                    'line_gst_paise' => (int) $line['line_gst_paise'],
                    'line_total_paise' => (int) $line['line_total_paise'],
                ]);
            }

            $proformaData = [
                'enquiry_id' => $enquiryId,
                'order_id' => $orderId,
                'customer_id' => (int) $customer['id'],
                'email' => $meta['email'],
                'po_number' => $meta['po_number'],
                'notes' => $meta['notes'],
                'valid_until' => $meta['valid_until'] ?: null,
                'freight_paise' => $totals['freight_paise'],
                'discount_paise' => $totals['discount_paise'],
                'subtotal_paise' => $totals['subtotal_paise'],
                'gst_paise' => $totals['gst_paise'],
                'grand_total_paise' => $totals['grand_total_paise'],
                'created_by' => $adminId,
                'updated_at' => $now,
            ];

            if ($existing) {
                $proformaId = (int) $existing['id'];
                db()->update('proformas', $proformaData, 'id = :id', ['id' => $proformaId]);
                $proformaNo = (string) $existing['proforma_no'];
            } else {
                $proformaData['proforma_no'] = generate_proforma_no();
                $proformaData['created_at'] = $now;
                $proformaId = db()->insert('proformas', $proformaData);
                $proformaNo = $proformaData['proforma_no'];
            }

            db()->update('enquiries', [
                'status' => 'quoted',
                'customer_id' => (int) $customer['id'],
                'updated_at' => $now,
            ], 'id = :id', ['id' => $enquiryId]);

            return [
                'proforma' => db()->one('SELECT * FROM proformas WHERE id = ?', [$proformaId]) ?? [],
                'order' => $order ?? [],
                'order_no' => $orderNo,
                'items' => db()->all('SELECT * FROM order_items WHERE order_id = ? ORDER BY id', [$orderId]),
                'customer' => $customer,
                'billing' => $billing,
                'shipping' => $shipping,
            ];
        });
    }

    /**
     * Resolve a stored PDF path, guarding against path traversal.
     */
    public static function pdfPath(?string $relativePath): ?string
    {
        if (!$relativePath) {
            return null;
        }
        $base = realpath(BASE_PATH . '/storage/proformas');
        $file = realpath(BASE_PATH . '/' . ltrim($relativePath, '/'));
        if (!$base || !$file || !is_file($file) || strpos($file, $base . DIRECTORY_SEPARATOR) !== 0) {
            return null;
        }
        return $file;
    }

    public static function streamPdf(array $proforma): never
    {
        $file = self::pdfPath($proforma['pdf_path'] ?? null);
        if (!$file) {
            http_response_code(404);
            exit('Proforma PDF not found. It may need to be regenerated.');
        }

        $name = 'Proforma-' . ($proforma['proforma_no'] ?? 'invoice') . '.pdf';
        header('Content-Type: application/pdf');
        header('Content-Length: ' . filesize($file));
        header('Content-Disposition: inline; filename="' . $name . '"');
        header('Cache-Control: private, max-age=0');
        readfile($file);
        exit;
    }

    /**
     * @param array<string,mixed> $customer
     * @return array<string,string>
     */
    private static function billingFor(array $enquiry, array $customer, string $email): array
    {
        return [
            'company_name' => (string) ($customer['company_name'] ?? '') ?: (string) ($enquiry['company'] ?? ''),
            'contact_name' => (string) ($enquiry['name'] ?? '') ?: (string) ($customer['contact_name'] ?? ''),
            'email' => $email,
            'phone' => (string) ($enquiry['phone'] ?? '') ?: (string) ($customer['phone'] ?? ''),
            'gstin' => (string) ($enquiry['gstin'] ?? '') ?: (string) ($customer['gstin'] ?? ''),
            'address_line1' => (string) ($customer['address_line1'] ?? ''),
            'address_line2' => (string) ($customer['address_line2'] ?? ''),
            'city' => (string) ($customer['city'] ?? ''),
            'state' => (string) ($customer['state'] ?? ''),
            'pincode' => (string) ($customer['pincode'] ?? ''),
        ];
    }

    private static function optionsJsonFor(array $summary): string
    {
        $lines = [];
        foreach ($summary as $line) {
            $line = trim((string) $line);
            if ($line !== '') {
                $lines[] = $line;
            }
        }
        if (!$lines) {
            return '';
        }
        return (string) json_encode(['summary' => $lines], JSON_UNESCAPED_UNICODE);
    }

    private static function rupeeInput(int $paise): string
    {
        return number_format($paise / 100, 2, '.', '');
    }

    private static function percentInput(int $bp): string
    {
        return rtrim(rtrim(number_format($bp / 100, 2, '.', ''), '0'), '.');
    }
}

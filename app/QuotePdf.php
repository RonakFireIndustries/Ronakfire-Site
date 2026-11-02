<?php

declare(strict_types=1);

use Dompdf\Dompdf;
use Dompdf\Options;

final class QuotePdf
{
    public static function generate(int $enquiryId, array $customer, array $form, array $items, array $totals): ?string
    {
        $storageDir = BASE_PATH . '/storage/quotes';
        if (!is_dir($storageDir)) {
            @mkdir($storageDir, 0755, true);
        }
        $filename = 'Quote-RF-ENQ-' . $enquiryId . '.pdf';
        $filepath = $storageDir . '/' . $filename;

        try {
            $options = new Options();
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'Helvetica');

            $dompdf = new Dompdf($options);
            $html = self::renderHtml($enquiryId, $customer, $form, $items, $totals);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            file_put_contents($filepath, $dompdf->output());
            return $filepath;
        } catch (Throwable $e) {
            error_log('Quote PDF generation failed: ' . $e->getMessage());
            return null;
        }
    }

    private static function renderHtml(int $enquiryId, array $customer, array $form, array $items, array $totals): string
    {
        $date = date('d-m-Y H:i');
        $companyName = htmlspecialchars($form['company_name'] ?? ($customer['company_name'] ?? ''));
        $contactName = htmlspecialchars($form['contact_name'] ?? ($customer['contact_name'] ?? ''));
        $email = htmlspecialchars($form['email'] ?? ($customer['email'] ?? ''));
        $phone = htmlspecialchars($form['phone'] ?? ($customer['phone'] ?? ''));
        $gstin = htmlspecialchars($form['gstin'] ?? ($customer['gstin'] ?? ''));
        $poNumber = htmlspecialchars($form['po_number'] ?? '');
        $notes = nl2br(htmlspecialchars($form['notes'] ?? ''));

        $rows = '';
        foreach ($items as $item) {
            $name = htmlspecialchars($item['name'] ?? '');
            $sku = htmlspecialchars($item['sku'] ?? '');
            $qty = (int) ($item['qty'] ?? 0);
            $options = json_decode((string) ($item['options_json'] ?? ''), true) ?? [];
            $optHtml = '';
            if (!empty($options['summary']) && is_array($options['summary'])) {
                $optHtml = '<br><small>' . htmlspecialchars(implode(' | ', $options['summary'])) . '</small>';
            }
            $unitPrice = money((int) ($item['unit_price_paise'] ?? 0));
            $lineTotal = money((int) ($item['line_total_paise'] ?? 0));
            $rows .= "<tr>
                <td>{$name}{$optHtml}<br><small>SKU: {$sku}</small></td>
                <td style=\"text-align:center;\">{$qty}</td>
                <td style=\"text-align:right;\">{$unitPrice}</td>
                <td style=\"text-align:right;\">{$lineTotal}</td>
            </tr>";
        }

        $subtotal = money((int) ($totals['subtotal_paise'] ?? 0));
        $gst = money((int) ($totals['gst_paise'] ?? 0));
        $total = money((int) ($totals['total_paise'] ?? 0));

        return '<!doctype html>
<html>
<head>
<meta charset="utf-8">
<style>
body { font-family: Helvetica, Arial, sans-serif; font-size: 11px; color: #222; }
.header { border-bottom: 2px solid #d62828; padding-bottom: 10px; margin-bottom: 20px; }
.header h1 { margin: 0; color: #d62828; font-size: 18px; }
.header p { margin: 2px 0; }
.meta { margin-bottom: 15px; }
.meta table { width: 100%; }
.meta td { padding: 2px 0; }
.section { margin-bottom: 18px; }
.section h2 { font-size: 13px; margin: 0 0 8px 0; color: #333; border-bottom: 1px solid #eee; padding-bottom: 4px; }
.items { width: 100%; border-collapse: collapse; }
.items th { background: #f7f7f7; border: 1px solid #ddd; padding: 6px; text-align: left; font-size: 10px; }
.items td { border: 1px solid #ddd; padding: 6px; vertical-align: top; }
.items small { color: #555; }
.totals { width: 280px; float: right; border-collapse: collapse; margin-top: 10px; }
.totals td { padding: 4px 6px; }
.totals .total { font-weight: bold; border-top: 2px solid #333; }
</style>
</head>
<body>
<div class="header">
    <h1>Ronak Fire India Pvt. Ltd.</h1>
    <p>' . htmlspecialchars(setting('support_phone', config('store.support_phone', ''))) . ' | ' . htmlspecialchars(setting('support_email', config('store.support_email', 'sales@ronakfire.com'))) . '</p>
    <p>' . htmlspecialchars(config('store.address', '')) . '</p>
</div>

<div class="meta">
    <table>
        <tr>
            <td><strong>Quote Request</strong></td>
            <td style="text-align:right;"><strong>Enquiry ID:</strong> #' . $enquiryId . '</td>
        </tr>
        <tr>
            <td></td>
            <td style="text-align:right;"><strong>Date:</strong> ' . $date . '</td>
        </tr>
    </table>
</div>

<div class="section">
    <h2>Customer Details</h2>
    <p><strong>Company/Institution:</strong> ' . $companyName . '</p>
    <p><strong>Contact Name:</strong> ' . $contactName . '</p>
    <p><strong>Email:</strong> ' . $email . '</p>
    <p><strong>Phone:</strong> ' . $phone . '</p>
    ' . ($gstin ? '<p><strong>GSTIN:</strong> ' . $gstin . '</p>' : '') . '
    ' . ($poNumber ? '<p><strong>PO Number:</strong> ' . $poNumber . '</p>' : '') . '
</div>

<div class="section">
    <h2>Quote Items</h2>
    <table class="items">
        <thead>
            <tr>
                <th>Item</th>
                <th style="width:60px; text-align:center;">Qty</th>
                <th style="width:90px; text-align:right;">Unit Price</th>
                <th style="width:90px; text-align:right;">Line Total</th>
            </tr>
        </thead>
        <tbody>' . $rows . '</tbody>
    </table>
    <table class="totals">
        <tr><td>Subtotal (excl. GST):</td><td style="text-align:right;">' . $subtotal . '</td></tr>
        <tr><td>GST:</td><td style="text-align:right;">' . $gst . '</td></tr>
        <tr class="total"><td>Total:</td><td style="text-align:right;">' . $total . '</td></tr>
    </table>
    <div style="clear:both;"></div>
</div>

' . ($notes ? '<div class="section"><h2>Notes</h2><p>' . $notes . '</p></div>' : '') . '

<div style="margin-top:40px; font-size:10px; color:#666;">
    <p>This is a quote request generated from the Ronak Fire India Pvt. Ltd. website.</p>
</div>
</body>
</html>';
    }
}

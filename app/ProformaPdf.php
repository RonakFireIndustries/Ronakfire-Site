<?php

declare(strict_types=1);

namespace App;

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Server-side PDF for proformas sent by email.
 *
 * The printable invoice view is reused so the emailed document matches the
 * copy the customer sees in their account.
 */
final class ProformaPdf
{
    /**
     * @param array $context Output of Proforma::persist()
     */
    public static function generate(array $context): ?string
    {
        $proforma = $context['proforma'] ?? [];
        $order = $context['order'] ?? [];
        if (!$proforma || !$order) {
            return null;
        }

        $dir = BASE_PATH . '/storage/proformas';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $proformaNo = (string) ($proforma['proforma_no'] ?? 'PF-' . date('Ymd'));
        $filePath = $dir . '/Proforma-' . preg_replace('/[^A-Za-z0-9_-]/', '-', $proformaNo) . '.pdf';

        $items = [];
        foreach (($context['items'] ?? []) as $item) {
            $items[] = [
                'product_id' => (int) ($item['product_id'] ?? 0) ?: null,
                'product_name' => (string) ($item['product_name'] ?? $item['name'] ?? ''),
                'sku' => (string) ($item['sku'] ?? ''),
                'options_json' => (string) ($item['options_json'] ?? ''),
                'qty' => (int) ($item['qty'] ?? 0),
                'unit_price_paise' => (int) ($item['unit_price_paise'] ?? 0),
                'gst_rate_bp' => (int) ($item['gst_rate_bp'] ?? 1800),
                'line_subtotal_paise' => (int) ($item['line_subtotal_paise'] ?? 0),
                'line_gst_paise' => (int) ($item['line_gst_paise'] ?? 0),
                'line_total_paise' => (int) ($item['line_total_paise'] ?? 0),
            ];
        }

        try {
            $html = render_to_string('invoice', [
                'pageTitle' => 'Proforma invoice ' . $proformaNo,
                'order' => array_merge($order, [
                    'order_no' => $proformaNo,
                    'placed_at' => $proforma['created_at'] ?? ($order['placed_at'] ?? date('Y-m-d H:i:s')),
                    'po_number' => $proforma['po_number'] ?? ($order['po_number'] ?? ''),
                    'notes' => $proforma['notes'] ?? ($order['notes'] ?? ''),
                    'customer_code' => $order['customer_code'] ?? ($context['customer']['customer_code'] ?? '—'),
                ]),
                'items' => $items,
                'billing' => $context['billing'] ?? [],
                'shipping' => $context['shipping'] ?? [],
                'proforma' => true,
            ]);

            $cssFile = BASE_PATH . '/assets/css/print.css';
            $css = is_file($cssFile) ? (string) file_get_contents($cssFile) : '';
            $html = '<!doctype html><html><head><meta charset="utf-8"><style>'
                . $css
                . "\n@page { margin: 12mm; }"
                . "\nbody { background: #fff; padding: 0; font-family: Helvetica, Arial, sans-serif; }"
                . "\n.print-toolbar { display: none !important; }"
                . '</style></head><body>' . $html . '</body></html>';

            // dompdf needs GD to decode images; skip the logo on installations
            // where that extension is unavailable rather than failing the PDF.
            $logo = BASE_PATH . '/images/logo.png';
            if (extension_loaded('gd') && is_file($logo)) {
                $html = str_replace(
                    'src="/images/logo.png"',
                    'src="data:image/png;base64,' . base64_encode((string) file_get_contents($logo)) . '"',
                    $html
                );
            }

            $options = new Options();
            $options->set('defaultFont', 'Helvetica');
            $options->set('isRemoteEnabled', false);
            $options->set('isHtml5ParserEnabled', true);

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            file_put_contents($filePath, $dompdf->output());

            return 'storage/proformas/' . basename($filePath);
        } catch (\Throwable $e) {
            error_log('Proforma PDF generation failed: ' . $e->getMessage());
            return null;
        }
    }
}

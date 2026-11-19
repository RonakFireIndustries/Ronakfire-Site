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
                . "\n@page { size: A4; margin: 5mm 8mm 6mm; }"
                // DejaVu Sans is bundled with dompdf and includes the ₹ glyph.
                . "\n* { font-family: 'DejaVu Sans', sans-serif !important; }"
                . "\nbody { background: #fff; padding: 0; }"
                . "\n.print-toolbar { display: none !important; }"
                // dompdf defaults to the "screen" media type, so the narrow-screen
                // stacking rules in print.css would otherwise collapse these tables.
                . "\n.invoice__parties-table { display: table !important; width: 100% !important; }"
                . "\n.invoice__parties-table > tbody { display: table-row-group !important; }"
                . "\n.invoice__parties-table > tbody > tr { display: table-row !important; }"
                . "\n.invoice__parties-table > tbody > tr > td { display: table-cell !important; width: 50% !important; padding: 0 14px 0 0 !important; }"
                . "\n.invoice__parties-table > tbody > tr > td:last-child { padding-right: 0 !important; padding-left: 14px !important; }"
                . "\n.invoice__meta table { display: table !important; width: 100% !important; }"
                . "\n.invoice__meta th { width: 18% !important; }"
                . "\n.invoice__meta td { width: 32% !important; }"
                . "\n.invoice__totals-table { display: table !important; width: 100% !important; }"
                . "\n.invoice__totals-table > tbody { display: table-row-group !important; }"
                . "\n.invoice__totals-table > tbody > tr { display: table-row !important; }"
                . "\n.invoice__totals-table > tbody > tr > td { display: table-cell !important; padding: 0 !important; }"
                . "\n.invoice__totals-table > tbody > tr > td:first-child { width: 58% !important; padding-right: 14px !important; }"
                . "\n.invoice__totals-table > tbody > tr > td:last-child { width: 42% !important; padding-left: 14px !important; }"
                // Compact spacing so more product rows fit on the first page;
                // anything that does not fit flows onto further pages cleanly.
                . "\n.invoice__header { padding-bottom: 8px; }"
                . "\n.invoice__brand img { width: 30mm; }"
                . "\n.invoice__brand h1 { font-size: 13px; }"
                . "\n.invoice__brand p { font-size: 8px; line-height: 1.3; max-width: 100mm; }"
                . "\n.invoice__title strong { font-size: 16px; }"
                . "\n.invoice__title em { margin-top: 3px; padding: 2px 6px; }"
                . "\n.invoice__parties { padding: 7px 0; }"
                . "\n.invoice__parties h2 { margin-bottom: 3px; }"
                . "\n.invoice__parties p { margin-bottom: 1px; line-height: 1.35; }"
                . "\n.invoice__meta { margin-top: 5px; padding-top: 5px; }"
                . "\n.invoice__meta th, .invoice__meta td { padding: 0 5px; line-height: 1.35; }"
                . "\n.invoice__items { margin-top: 8px; }"
                . "\n.invoice__items thead { display: table-header-group; }"
                . "\n.invoice__items thead th { padding: 4px 5px; font-size: 7.5px; }"
                . "\n.invoice__items tbody td { padding: 2px 4px; font-size: 8.5px; line-height: 1.3; }"
                . "\n.invoice__items tbody td strong { font-size: 8.8px; }"
                . "\n.invoice__items tbody td small { font-size: 7.6px; }"
                . "\n.invoice__items tbody td em { margin-top: 0; font-size: 7.4px; }"
                . "\n.invoice__items tr { break-inside: avoid; page-break-inside: avoid; }"
                . "\n.invoice__totals { margin-top: 8px; padding-top: 6px; }"
                . "\n.invoice__tax h2 { margin-bottom: 3px; }"
                . "\n.invoice__tax table th, .invoice__tax table td { padding: 1px 4px; font-size: 7.5px; }"
                . "\n.invoice__tax p { margin-top: 2px; font-size: 7.5px; }"
                . "\n.invoice__sum > div { padding: 0; font-size: 9px; }"
                . "\n.invoice__sum .is-total { margin-top: 3px; padding-top: 4px; font-size: 10px; }"
                . "\n.invoice__sum .is-total dd { font-size: 12px; }"
                . "\n.invoice__terms { margin-top: 4px; padding-top: 3px; }"
                . "\n.invoice__terms ol { gap: 0; }"
                . "\n.invoice__terms li { font-size: 7.5px; line-height: 1.2; }"
                . "\n.invoice__sign { margin-top: 5px; padding-top: 2px; break-inside: avoid; page-break-inside: avoid; }"
                . "\n.invoice__sign-line { padding-top: 5px; }"
                . "\n.invoice__footer { margin-top: 2px; padding-top: 2px; font-size: 6.5px; break-inside: avoid; page-break-inside: avoid; }"
                . "\n.invoice__parties, .invoice__meta, .invoice__totals, .invoice__terms { break-inside: avoid; page-break-inside: avoid; }"
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
            $options->set('defaultFont', 'DejaVu Sans');
            $options->setDefaultMediaType('print');
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

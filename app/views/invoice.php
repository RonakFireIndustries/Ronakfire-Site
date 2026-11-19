<?php
/** @var array $order */
/** @var array $items */
/** @var array $billing */
/** @var array $shipping */
/** @var bool $proforma */
$companyState = 'Maharashtra';
$placeOfSupply = (string) ($billing['state'] ?? $companyState);
$isIntraState = strcasecmp($placeOfSupply, $companyState) === 0;
$taxGroups = [];
foreach ($items as $item) {
    $rate = (int) $item['gst_rate_bp'];
    if (!isset($taxGroups[$rate])) {
        $taxGroups[$rate] = ['taxable' => 0, 'tax' => 0];
    }
    $taxGroups[$rate]['taxable'] += (int) $item['line_subtotal_paise'];
    $taxGroups[$rate]['tax'] += (int) $item['line_gst_paise'];
}
$totalTax = 0;
foreach ($taxGroups as $group) {
    $totalTax += $group['tax'];
}
$documentLabel = $proforma ? 'Proforma invoice' : 'Tax invoice';

/* Document facts are rendered as label/value pairs in two columns. */
$metaPairs = [
    ['Order no.', (string) ($order['order_no'] ?? '')],
    ['Order date', format_date($order['placed_at'])],
    ['Customer code', (string) ($order['customer_code'] ?? '') ?: '—'],
    ['PO number', (string) ($order['po_number'] ?? '') ?: '—'],
];
if (!empty($order['valid_until'])) {
    $metaPairs[] = ['Valid until', format_date((string) $order['valid_until'], 'd M Y')];
}
$metaPairs[] = ['Place of supply', $placeOfSupply];
$metaPairs[] = ['Payment', 'On invoice'];
if (count($metaPairs) % 2 !== 0) {
    $metaPairs[] = ['', ''];
}
$metaRows = array_chunk($metaPairs, 2);
?>
<main class="invoice">
    <header class="invoice__header">
        <div class="invoice__brand">
            <img src="/images/logo.png" alt="Ronak Fire India Pvt. Ltd.">
            <div>
                <h1>Ronak Fire India Pvt. Ltd.</h1>
                <p><?= e(config('store.address')) ?></p>
                <p><?= e(setting('support_phone', config('store.support_phone'))) ?> · <?= e(setting('support_email', config('store.support_email'))) ?></p>
                <?php if (config('store.gstin')): ?><p>GSTIN: <?= e(config('store.gstin')) ?></p><?php endif; ?>
            </div>
        </div>
        <div class="invoice__title">
            <span><?= e($documentLabel) ?></span>
            <strong><?= e($order['order_no']) ?></strong>
            <small><?= e(format_datetime($order['placed_at'])) ?></small>
            <?php if ($proforma): ?><em>Not a tax invoice. For order confirmation only.</em><?php endif; ?>
        </div>
    </header>

    <section class="invoice__parties">
        <table class="invoice__parties-table">
            <tbody>
            <tr>
                <td>
                    <h2>Billed to</h2>
                    <strong><?= e($billing['company_name'] ?? $order['company_name']) ?></strong>
                    <p><?= e($billing['contact_name'] ?? '') ?></p>
                    <p><?= e($billing['address_line1'] ?? '') ?><?php if (!empty($billing['address_line2'])): ?><br><?= e($billing['address_line2']) ?><?php endif; ?><br><?= e($billing['city'] ?? '') ?>, <?= e($billing['state'] ?? '') ?> <?= e($billing['pincode'] ?? '') ?></p>
                    <p><?= e($billing['phone'] ?? '') ?> · <?= e($billing['email'] ?? '') ?></p>
                    <?php if (!empty($billing['gstin'])): ?><p><strong>GSTIN:</strong> <?= e($billing['gstin']) ?></p><?php endif; ?>
                </td>
                <td>
                    <h2>Shipped to</h2>
                    <strong><?= e($shipping['company_name'] ?? $order['company_name']) ?></strong>
                    <p><?= e($shipping['contact_name'] ?? '') ?></p>
                    <p><?= e($shipping['address_line1'] ?? '') ?><?php if (!empty($shipping['address_line2'])): ?><br><?= e($shipping['address_line2']) ?><?php endif; ?><br><?= e($shipping['city'] ?? '') ?>, <?= e($shipping['state'] ?? '') ?> <?= e($shipping['pincode'] ?? '') ?></p>
                    <p><?= e($shipping['phone'] ?? '') ?></p>
                </td>
            </tr>
            </tbody>
        </table>
        <div class="invoice__meta">
            <table>
                <tbody>
                <?php foreach ($metaRows as $row): ?>
                    <tr>
                        <th><?= e($row[0][0]) ?></th>
                        <td><?= e($row[0][1]) ?></td>
                        <th><?= e($row[1][0]) ?></th>
                        <td><?= e($row[1][1]) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <table class="invoice__items">
        <thead>
        <tr>
            <th>#</th>
            <th>Description</th>
            <th>HSN</th>
            <th class="num">Qty</th>
            <th class="num">Rate</th>
            <th class="num">Taxable</th>
            <th class="num">GST</th>
            <th class="num">Amount</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $index => $item): ?>
            <?php $itemOptions = decode_json($item['options_json']); ?>
            <tr>
                <td><?= $index + 1 ?></td>
                <td>
                    <strong><?= e($item['product_name']) ?></strong>
                    <?php
                    $itemDetail = trim((string) $item['sku']);
                    if (!empty($itemOptions['summary'])) {
                        $optionText = implode(' · ', $itemOptions['summary']);
                        $itemDetail = trim($itemDetail . ($itemDetail !== '' ? ' · ' : '') . $optionText);
                    }
                    ?>
                    <?php if ($itemDetail !== ''): ?><small><?= e($itemDetail) ?></small><?php endif; ?>
                </td>
                <td><?= e(db()->value('SELECT hsn_code FROM products WHERE id = ?', [$item['product_id']]) ?: '—') ?></td>
                <td class="num"><?= (int) $item['qty'] ?></td>
                <td class="num"><?= money((int) $item['unit_price_paise']) ?></td>
                <td class="num"><?= money((int) $item['line_subtotal_paise']) ?></td>
                <td class="num"><?= gst_label((int) $item['gst_rate_bp']) ?> <?= money((int) $item['line_gst_paise']) ?></td>
                <td class="num"><?= money((int) $item['line_total_paise']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <section class="invoice__totals">
        <table class="invoice__totals-table">
            <tbody>
            <tr>
                <td class="invoice__tax">
                    <h2>Tax summary</h2>
                    <table>
                        <thead><tr><th>Rate</th><th class="num">Taxable</th><th class="num"><?= $isIntraState ? 'CGST' : 'IGST' ?></th><?php if ($isIntraState): ?><th class="num">SGST</th><?php endif; ?><th class="num">Tax</th></tr></thead>
                        <tbody>
                        <?php foreach ($taxGroups as $rate => $group): ?>
                            <?php $half = (int) round($group['tax'] / 2); ?>
                            <tr>
                                <td><?= gst_label($rate) ?></td>
                                <td class="num"><?= money($group['taxable']) ?></td>
                                <td class="num"><?= money($isIntraState ? $half : $group['tax']) ?></td>
                                <?php if ($isIntraState): ?><td class="num"><?= money($group['tax'] - $half) ?></td><?php endif; ?>
                                <td class="num"><?= money($group['tax']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p><?= $isIntraState ? 'Intra-state supply: CGST and SGST applied.' : 'Inter-state supply: IGST applied.' ?></p>
                </td>
                <td class="invoice__sum-cell">
                    <dl class="invoice__sum">
                        <div><dt>Subtotal</dt><dd><?= money((int) $order['subtotal_paise']) ?></dd></div>
                        <div><dt>GST</dt><dd><?= money((int) $order['gst_paise']) ?></dd></div>
                        <div><dt>Freight</dt><dd><?= (int) $order['freight_paise'] > 0 ? money((int) $order['freight_paise']) : 'To be confirmed' ?></dd></div>
                        <?php if ((int) ($order['discount_paise'] ?? 0) > 0): ?><div><dt>Discount</dt><dd>&minus; <?= money((int) $order['discount_paise']) ?></dd></div><?php endif; ?>
                        <div class="is-total"><dt>Grand total</dt><dd><?= money((int) $order['grand_total_paise']) ?></dd></div>
                    </dl>
                </td>
            </tr>
            </tbody>
        </table>
    </section>

    <section class="invoice__terms">
        <h2>Terms &amp; notes</h2>
        <ol>
            <li>This is a <?= $proforma ? 'proforma document for order confirmation and is not a demand for payment' : 'tax invoice' ?>. GST is charged at the rates shown.</li>
            <li>Freight, unloading and site access are confirmed separately by the sales team.</li>
            <li>Goods remain the property of Ronak Fire India Pvt. Ltd. until payment is received in full.</li>
            <li>Warranty is as per the standard terms for each product and excludes misuse, incorrect installation and consumable wear.</li>
            <?php if (!empty($order['notes'])): ?><li><strong>Order notes:</strong> <?= e($order['notes']) ?></li><?php endif; ?>
        </ol>
        <div class="invoice__sign">
            <div><span>Prepared by</span><strong>Ronak Fire sales desk</strong></div>
            <div><span>For Ronak Fire India Pvt. Ltd.</span><strong class="invoice__sign-line">Authorised signatory</strong></div>
        </div>
    </section>

    <footer class="invoice__footer">
        <p>Ronak Fire India Pvt. Ltd. · <?= e(config('store.address')) ?></p>
        <p>This document was generated from the Ronak Fire trade store on <?= e(format_datetime(date('Y-m-d H:i:s'))) ?>.</p>
    </footer>
</main>

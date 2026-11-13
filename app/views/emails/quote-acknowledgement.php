<?php
/**
 * Customer acknowledgement for a quote request.
 *
 * Used by both the cart quote (/cart/quote) and the single-product quote
 * (/quote) flows, so it must work with:
 *   - cart items:  name, sku, qty, line_total_paise
 *   - quote items: product_name, sku, qty, line_total_paise (may be null)
 *   - guest requests, where $customer is empty and $form holds the details
 *
 * @var array      $form
 * @var array      $customer
 * @var array      $items
 * @var array|null $totals
 * @var int|null   $enquiryId
 */

$enquiryId = $enquiryId ?? null;
$contactName = trim((string) ($form['contact_name'] ?? $form['name'] ?? $customer['contact_name'] ?? ''));
$companyName = trim((string) ($form['company_name'] ?? $form['company'] ?? $customer['company_name'] ?? ''));
$reference = $enquiryId ? 'RF-ENQ-' . (int) $enquiryId : '';
$isCustomer = !empty($customer['id']);
$actionUrl = $isCustomer ? abs_url('/account/enquiries') : abs_url('/catalog');
$actionLabel = $isCustomer ? 'View your requests' : 'Browse the catalogue';
$notes = trim((string) ($form['notes'] ?? ''));
$poNumber = trim((string) ($form['po_number'] ?? ''));
$phone = setting('support_phone', config('store.support_phone'));
$phoneHref = setting('support_phone_href', config('store.support_phone_href'));
$supportEmail = setting('support_email', config('store.support_email'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Your requirement is with our sales team — Ronak Fire India</title>
</head>
<body style="margin:0;background:#f6f7fb;font-family:Arial,Helvetica,sans-serif;color:#1f2937;line-height:1.5;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f6f7fb;">
        <tr>
            <td align="center" style="padding:24px 12px;">
                <table role="presentation" width="640" cellspacing="0" cellpadding="0" style="background:#ffffff;border-radius:12px;overflow:hidden;">
                    <tr>
                        <td style="background:#b91c1c;color:#ffffff;padding:24px 32px;">
                            <h1 style="margin:0;font-size:20px;">Ronak Fire India Pvt. Ltd.</h1>
                            <p style="margin:6px 0 0;font-size:13px;opacity:0.85;">Requirement received</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px 32px;">
                            <p style="margin:0 0 16px;">Hello <?= e($contactName !== '' ? $contactName : 'there') ?>,</p>
                            <p style="margin:0 0 16px;">
                                Thank you for your enquiry<?= $companyName !== '' ? ' from ' . e($companyName) : '' ?>.
                                Our sales team has received it and will reply with pricing, stock availability and lead time.
                            </p>

                            <?php if ($reference !== ''): ?>
                                <p style="margin:0 0 20px;font-size:16px;font-weight:bold;color:#b91c1c;">
                                    Reference: <?= e($reference) ?>
                                </p>
                            <?php endif; ?>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;margin:0 0 20px;">
                                <thead>
                                    <tr>
                                        <th style="text-align:left;padding:10px;border-bottom:1px solid #e2e8f0;color:#475569;font-size:12px;text-transform:uppercase;letter-spacing:0.05em;">Product</th>
                                        <th style="text-align:center;padding:10px;border-bottom:1px solid #e2e8f0;color:#475569;font-size:12px;text-transform:uppercase;letter-spacing:0.05em;">Qty</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($items as $item): ?>
                                    <?php
                                    $itemName = (string) ($item['product_name'] ?? $item['name'] ?? '');
                                    $itemSku = (string) ($item['sku'] ?? '');
                                    $itemQty = (int) ($item['qty'] ?? 1);
                                    ?>
                                    <tr>
                                        <td style="padding:10px;border-bottom:1px solid #e2e8f0;">
                                            <?= e($itemName !== '' ? $itemName : 'Your requirement') ?><?= $itemSku !== '' ? ' (' . e($itemSku) . ')' : '' ?>
                                        </td>
                                        <td style="padding:10px;border-bottom:1px solid #e2e8f0;text-align:center;"><?= $itemQty ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>

                            <?php if ($poNumber !== ''): ?>
                                <p style="margin:0 0 8px;"><strong>Your PO number:</strong> <?= e($poNumber) ?></p>
                            <?php endif; ?>
                            <?php if ($notes !== ''): ?>
                                <p style="margin:0 0 8px;"><strong>Your notes:</strong><br><?= nl2br(e($notes)) ?></p>
                            <?php endif; ?>

                            <p style="margin:16px 0;">
                                Pricing, GST and freight are not included in this acknowledgement. Our sales team will send the formal quotation separately.
                            </p>
                            <p style="margin:16px 0;">
                                Questions? Call <a href="tel:<?= e($phoneHref) ?>" style="color:#b91c1c;"><?= e($phone) ?></a>
                                or email <a href="mailto:<?= e($supportEmail) ?>" style="color:#b91c1c;"><?= e($supportEmail) ?></a>.
                            </p>

                            <p style="margin:24px 0 0;">
                                <a href="<?= e($actionUrl) ?>" style="display:inline-block;background:#b91c1c;color:#ffffff;padding:12px 24px;text-decoration:none;border-radius:6px;font-weight:bold;"><?= e($actionLabel) ?></a>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 32px;background:#f8fafc;font-size:12px;color:#64748b;">
                            <p style="margin:0 0 6px;">Ronak Fire India Pvt. Ltd. — Manufacturer of fire protection equipment · GST invoices · Pan-India dispatch</p>
                            <p style="margin:0;">This email was sent in response to your quote request on <?= e(config('app_url')) ?>.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

<?php
/**
 * Internal notification to the sales team for a quote request.
 *
 * Deliberately uses the same layout as the customer acknowledgement
 * (quote-acknowledgement.php) so both sides see the same document.
 * Prices are not included; sales quotes separately.
 *
 * @var array      $form
 * @var array      $customer
 * @var array      $items
 * @var int|null   $enquiryId
 * @var string     $configuration
 * @var string     $message
 * @var string     $kind  "cart" for /cart/quote, "enquiry" for /quote
 */

$enquiryId = $enquiryId ?? null;
$configuration = trim((string) ($configuration ?? ''));
$message = trim((string) ($message ?? ''));
$kind = ($kind ?? 'enquiry') === 'cart' ? 'cart' : 'enquiry';

$companyName = trim((string) ($form['company_name'] ?? $form['company'] ?? $customer['company_name'] ?? ''));
$contactName = trim((string) ($form['contact_name'] ?? $form['name'] ?? $customer['contact_name'] ?? ''));
$email = trim((string) ($form['email'] ?? $customer['email'] ?? ''));
$phone = trim((string) ($form['phone'] ?? $customer['phone'] ?? ''));
$gstin = trim((string) ($form['gstin'] ?? $customer['gstin'] ?? ''));
$poNumber = trim((string) ($form['po_number'] ?? ''));
$reference = $enquiryId ? 'RF-ENQ-' . (int) $enquiryId : '';
$heading = $kind === 'cart' ? 'New cart quote request' : 'New quote request';
$adminUrl = $enquiryId ? abs_url('/admin/enquiries/' . (int) $enquiryId) : '';
$accountUrl = !empty($customer['id']) ? abs_url('/admin/customers/' . (int) $customer['id']) : '';
$phoneHref = setting('support_phone_href', config('store.support_phone_href'));
$supportEmail = setting('support_email', config('store.support_email'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($heading) ?> — Ronak Fire India</title>
</head>
<body style="margin:0;background:#f6f7fb;font-family:Arial,Helvetica,sans-serif;color:#1f2937;line-height:1.5;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f6f7fb;">
        <tr>
            <td align="center" style="padding:24px 12px;">
                <table role="presentation" width="640" cellspacing="0" cellpadding="0" style="background:#ffffff;border-radius:12px;overflow:hidden;">
                    <tr>
                        <td style="background:#b91c1c;color:#ffffff;padding:24px 32px;">
                            <h1 style="margin:0;font-size:20px;">Ronak Fire India Pvt. Ltd.</h1>
                            <p style="margin:6px 0 0;font-size:13px;opacity:0.85;"><?= e($heading) ?></p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px 32px;">
                            <p style="margin:0 0 16px;">
                                A new quote request has been submitted on the website<?= $companyName !== '' ? ' by ' . e($companyName) : '' ?>.
                                Reply with pricing, stock availability and lead time.
                            </p>

                            <?php if ($reference !== ''): ?>
                                <p style="margin:0 0 20px;font-size:16px;font-weight:bold;color:#b91c1c;">
                                    Reference: <?= e($reference) ?>
                                </p>
                            <?php endif; ?>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;margin:0 0 20px;">
                                <tbody>
                                    <?php if ($companyName !== ''): ?>
                                        <tr>
                                            <td style="padding:8px 10px;border-bottom:1px solid #e2e8f0;color:#475569;font-size:12px;text-transform:uppercase;letter-spacing:0.05em;width:150px;">Company</td>
                                            <td style="padding:8px 10px;border-bottom:1px solid #e2e8f0;"><?= e($companyName) ?></td>
                                        </tr>
                                    <?php endif; ?>
                                    <?php if ($contactName !== ''): ?>
                                        <tr>
                                            <td style="padding:8px 10px;border-bottom:1px solid #e2e8f0;color:#475569;font-size:12px;text-transform:uppercase;letter-spacing:0.05em;">Contact</td>
                                            <td style="padding:8px 10px;border-bottom:1px solid #e2e8f0;"><?= e($contactName) ?></td>
                                        </tr>
                                    <?php endif; ?>
                                    <?php if ($email !== ''): ?>
                                        <tr>
                                            <td style="padding:8px 10px;border-bottom:1px solid #e2e8f0;color:#475569;font-size:12px;text-transform:uppercase;letter-spacing:0.05em;">Email</td>
                                            <td style="padding:8px 10px;border-bottom:1px solid #e2e8f0;"><a href="mailto:<?= e($email) ?>" style="color:#b91c1c;"><?= e($email) ?></a></td>
                                        </tr>
                                    <?php endif; ?>
                                    <?php if ($phone !== ''): ?>
                                        <tr>
                                            <td style="padding:8px 10px;border-bottom:1px solid #e2e8f0;color:#475569;font-size:12px;text-transform:uppercase;letter-spacing:0.05em;">Phone</td>
                                            <td style="padding:8px 10px;border-bottom:1px solid #e2e8f0;"><a href="tel:<?= e($phone) ?>" style="color:#b91c1c;"><?= e($phone) ?></a></td>
                                        </tr>
                                    <?php endif; ?>
                                    <?php if ($gstin !== ''): ?>
                                        <tr>
                                            <td style="padding:8px 10px;border-bottom:1px solid #e2e8f0;color:#475569;font-size:12px;text-transform:uppercase;letter-spacing:0.05em;">GSTIN</td>
                                            <td style="padding:8px 10px;border-bottom:1px solid #e2e8f0;"><?= e($gstin) ?></td>
                                        </tr>
                                    <?php endif; ?>
                                    <?php if ($poNumber !== ''): ?>
                                        <tr>
                                            <td style="padding:8px 10px;border-bottom:1px solid #e2e8f0;color:#475569;font-size:12px;text-transform:uppercase;letter-spacing:0.05em;">PO Number</td>
                                            <td style="padding:8px 10px;border-bottom:1px solid #e2e8f0;"><?= e($poNumber) ?></td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>

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
                                    $options = json_decode((string) ($item['options_json'] ?? ''), true) ?? [];
                                    $optText = '';
                                    if (!empty($options['summary']) && is_array($options['summary'])) {
                                        $optText = implode(' | ', array_map('strval', $options['summary']));
                                    }
                                    ?>
                                    <tr>
                                        <td style="padding:10px;border-bottom:1px solid #e2e8f0;">
                                            <?= e($itemName !== '' ? $itemName : 'Your requirement') ?><?= $itemSku !== '' ? ' (' . e($itemSku) . ')' : '' ?>
                                            <?php if ($optText !== ''): ?>
                                                <br><small style="color:#64748b;"><?= e($optText) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding:10px;border-bottom:1px solid #e2e8f0;text-align:center;"><?= $itemQty ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>

                            <?php if ($configuration !== ''): ?>
                                <p style="margin:0 0 8px;"><strong>Configuration:</strong><br><?= nl2br(e($configuration)) ?></p>
                            <?php endif; ?>
                            <?php if ($message !== ''): ?>
                                <p style="margin:0 0 8px;"><strong>Requirement / notes:</strong><br><?= nl2br(e($message)) ?></p>
                            <?php endif; ?>

                            <p style="margin:16px 0;">
                                Prices are not shown here<?= $kind === 'cart' ? ' or in the attached PDF' : '' ?>. Add pricing in the formal quotation and reply to the customer at
                                <a href="mailto:<?= e($email) ?>" style="color:#b91c1c;"><?= e($email) ?></a>.
                            </p>

                            <?php if ($adminUrl !== ''): ?>
                                <p style="margin:24px 0 0;">
                                    <a href="<?= e($adminUrl) ?>" style="display:inline-block;background:#b91c1c;color:#ffffff;padding:12px 24px;text-decoration:none;border-radius:6px;font-weight:bold;">Open in admin</a>
                                    <?php if ($accountUrl !== ''): ?>
                                        <a href="<?= e($accountUrl) ?>" style="display:inline-block;margin-left:8px;padding:12px 24px;text-decoration:none;border-radius:6px;font-weight:bold;border:1px solid #b91c1c;color:#b91c1c;">Customer account</a>
                                    <?php endif; ?>
                                </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 32px;background:#f8fafc;font-size:12px;color:#64748b;">
                            <p style="margin:0 0 6px;">Ronak Fire India Pvt. Ltd. — Manufacturer of fire protection equipment · GST invoices · Pan-India dispatch</p>
                            <p style="margin:0;">Internal notification from <?= e(config('app_url')) ?>. Customer support: <a href="mailto:<?= e($supportEmail) ?>" style="color:#b91c1c;"><?= e($supportEmail) ?></a> · <a href="tel:<?= e($phoneHref) ?>" style="color:#b91c1c;"><?= e(setting('support_phone', config('store.support_phone'))) ?></a></p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

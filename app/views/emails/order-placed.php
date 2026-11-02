<?php
/** @var array $order */
/** @var array $customer */
/** @var array $items */
/** @var array $billing */
/** @var array $shipping */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order <?= e($order['order_no']) ?></title>
</head>
<body style="margin:0;background:#f6f7fb;font-family:Arial,Helvetica,sans-serif;color:#1a1a1a;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td align="center" style="padding:24px 12px;">
                <table role="presentation" width="640" cellspacing="0" cellpadding="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 10px 30px rgba(15,23,42,0.08);">
                    <tr>
                        <td style="background:#111827;color:#ffffff;padding:20px 32px;">
                            <h1 style="margin:0;font-size:20px;">Ronak Fire India Pvt. Ltd.</h1>
                            <p style="margin:6px 0 0;font-size:13px;opacity:0.8;">Order Confirmation</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px 32px;">
                            <p style="margin:0 0 12px;">Hello <?= e($customer['contact_name'] ?: $customer['company_name']) ?>,</p>
                            <p style="margin:0 0 18px;">Thank you for your order. We have received it and our sales team will confirm stock and freight shortly.</p>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f8fafc;border-radius:10px;margin:0 0 22px;">
                                <tr>
                                    <td style="padding:16px 18px;">
                                        <p style="margin:0;font-size:13px;text-transform:uppercase;letter-spacing:0.08em;color:#475569;">Order Details</p>
                                        <p style="margin:6px 0 0;font-size:16px;font-weight:bold;">Order No: <?= e($order['order_no']) ?></p>
                                        <p style="margin:6px 0 0;font-size:13px;color:#475569;">Placed: <?= e(format_datetime($order['placed_at'] ?? $order['created_at'])) ?></p>
                                    </td>
                                </tr>
                            </table>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;">
                                <thead>
                                    <tr style="background:#f8fafc;">
                                        <th align="left" style="padding:10px 16px;font-size:13px;">Item</th>
                                        <th align="center" style="padding:10px 16px;font-size:13px;">Qty</th>
                                        <th align="right" style="padding:10px 16px;font-size:13px;">Unit Price</th>
                                        <th align="right" style="padding:10px 16px;font-size:13px;">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td style="padding:12px 16px;border-top:1px solid #e5e7eb;">
                                            <strong><?= e($item['product_name']) ?></strong>
                                            <?php if ($item['sku']): ?><br><span style="font-size:12px;color:#475569;">SKU: <?= e($item['sku']) ?></span><?php endif; ?>
                                        </td>
                                        <td align="center" style="padding:12px 16px;border-top:1px solid #e5e7eb;"><?= (int) $item['qty'] ?></td>
                                        <td align="right" style="padding:12px 16px;border-top:1px solid #e5e7eb;"><?= money((int) $item['unit_price_paise']) ?></td>
                                        <td align="right" style="padding:12px 16px;border-top:1px solid #e5e7eb;"><?= money((int) $item['line_total_paise']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <table role="presentation" align="right" width="280" cellspacing="0" cellpadding="0" style="margin-top:16px;">
                                <tr>
                                    <td style="padding:6px 0;">Subtotal (excl. GST)</td>
                                    <td align="right" style="padding:6px 0;"><?= money((int) $order['subtotal_paise']) ?></td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0;">GST</td>
                                    <td align="right" style="padding:6px 0;"><?= money((int) $order['gst_paise']) ?></td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 0;border-top:1px solid #e5e7eb;font-weight:bold;">Total</td>
                                    <td align="right" style="padding:10px 0;border-top:1px solid #e5e7eb;font-weight:bold;"><?= money((int) $order['grand_total_paise']) ?></td>
                                </tr>
                            </table>
                            <div style="clear:both;"></div>
                            <p style="margin:28px 0 0;font-size:13px;color:#475569;">If you have any questions, reply to this email or call <?= e(setting('support_phone', config('store.support_phone'))) ?>.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#f8fafc;padding:16px 32px;text-align:center;font-size:12px;color:#475569;">
                            Ronak Fire India Pvt. Ltd. • Sales: <?= e(setting('support_email', config('store.support_email'))) ?>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

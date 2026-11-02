<?php
/** @var array $order */
/** @var array $customer */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order <?= e($order['order_no']) ?> Dispatched</title>
</head>
<body style="margin:0;background:#f6f7fb;font-family:Arial,Helvetica,sans-serif;color:#1a1a1a;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td align="center" style="padding:24px 12px;">
                <table role="presentation" width="640" cellspacing="0" cellpadding="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 10px 30px rgba(15,23,42,0.08);">
                    <tr>
                        <td style="background:#111827;color:#ffffff;padding:20px 32px;">
                            <h1 style="margin:0;font-size:20px;">Ronak Fire India Pvt. Ltd.</h1>
                            <p style="margin:6px 0 0;font-size:13px;opacity:0.8;">Dispatch Update</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px 32px;">
                            <p style="margin:0 0 12px;">Hello <?= e($customer['contact_name'] ?: $customer['company_name']) ?>,</p>
                            <p style="margin:0 0 18px;">Your order <strong><?= e($order['order_no']) ?></strong> has been dispatched.</p>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f8fafc;border-radius:10px;margin:0 0 22px;">
                                <tr>
                                    <td style="padding:16px 18px;">
                                        <?php if (!empty($order['courier_name'])): ?><p style="margin:0 0 6px;">Courier: <strong><?= e($order['courier_name']) ?></strong></p><?php endif; ?>
                                        <?php if (!empty($order['tracking_no'])): ?><p style="margin:0 0 6px;">Tracking No: <strong><?= e($order['tracking_no']) ?></strong></p><?php endif; ?>
                                        <?php if (!empty($order['tracking_url'])): ?><p style="margin:10px 0 0;"><a href="<?= e($order['tracking_url']) ?>" style="color:#b91c1c;">Track your shipment</a></p><?php endif; ?>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:0;font-size:13px;color:#475569;">View your order details at <a href="<?= e(abs_url('/order/' . $order['order_no'])) ?>"><?= e(abs_url('/order/' . $order['order_no'])) ?></a>.</p>
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

<?php
/** @var array $order */
/** @var array $items */
/** @var array $billing */
$orderNo = $order['order_no'] ?? '';
$customerName = $billing['contact_name'] ?? $order['customer_contact'] ?? '';
$companyName = $billing['company_name'] ?? $order['company_name'] ?? '';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Your requirement is with our sales team — Ronak Fire India</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; line-height: 1.5; color: #1f2937; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; padding: 24px; }
        .header { background: #b91c1c; color: #fff; padding: 24px; text-align: center; border-radius: 8px 8px 0 0; }
        .header h1 { margin: 0; font-size: 20px; }
        .content { background: #f8fafc; padding: 24px; border-radius: 0 0 8px 8px; }
        .order-no { font-size: 18px; font-weight: bold; color: #b91c1c; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { text-align: left; padding: 10px; border-bottom: 1px solid #e2e8f0; }
        th { color: #475569; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; }
        .total-row { font-weight: bold; background: #fff; border-top: 2px solid #b91c1c; }
        .footer { margin-top: 24px; font-size: 12px; color: #64748b; border-top: 1px solid #e2e8f0; padding-top: 16px; }
        .btn { display: inline-block; background: #b91c1c; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; margin-top: 12px; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Requirement received — <?php echo e($order['order_no'] ?? ''); ?></h1>
    </div>
    <div class="content">
        <p>Hello <?php echo e($customerName); ?>,</p>
        <p>We have received your requirement from the Ronak Fire website. Our sales team will review the specification and reply with pricing, stock availability and lead time.</p>

        <div class="order-no">Reference: <?php echo e($orderNo); ?></div>

        <table>
            <thead>
                <tr><th>Product</th><th style="text-align:center">Qty</th><th style="text-align:right">Amount</th></tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?php echo e($item['product_name'] ?? ''); ?> (<?php echo e($item['sku'] ?? ''); ?>)</td>
                    <td style="text-align:center"><?php echo (int) ($item['qty'] ?? 1); ?></td>
                    <td style="text-align:right"><?php echo money((int) ($item['line_total_paise'] ?? 0)); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="total-row"><td>Total</td><td style="text-align:center"></td><td style="text-align:right"><?php echo money((int) ($order['grand_total_paise'] ?? 0)); ?></td></tr>
            </tfoot>
        </table>

        <p>We will contact you shortly to confirm the quotation. Once the deal is finalised, we will create your customer account and send login details so you can view your orders, inventory and documents on our site.</p>

        <p>Questions? Call us at <a href="tel:<?php echo e(setting('support_phone_href', config('store.support_phone_href'))); ?>"><?php echo e(setting('support_phone', config('store.support_phone'))); ?></a> or email <a href="mailto:<?php echo e(setting('support_email', config('store.support_email'))); ?>"><?php echo e(setting('support_email', config('store.support_email'))); ?></a>.</p>

        <a class="btn" href="<?php echo abs_url('/account/enquiries'); ?>">View your requests</a>

        <div class="footer">
            <p>Ronak Fire India Pvt. Ltd. — Manufacturer of fire protection equipment · GST invoices · Pan-India dispatch</p>
            <p>This email was sent in response to your quote request on <?php echo e(config('app_url')); ?>.</p>
        </div>
    </div>
</div>
</body>
</html>

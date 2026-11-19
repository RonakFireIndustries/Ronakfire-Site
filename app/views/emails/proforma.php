<?php
/** @var array $proforma */
/** @var string $orderNo */
/** @var array $customer */
/** @var string $tempPassword */
?>
<div style="font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.55;color:#222;">
    <p>Dear <?= e($customer['contact_name'] ?: ($proforma['email'] ?? 'Customer')) ?>,</p>
    <p>Please find your proforma invoice <strong><?= e($proforma['proforma_no'] ?? '') ?></strong> attached (order reference <?= e($orderNo) ?>).</p>
    <?php if (!empty($proforma['valid_until'])): ?>
        <p>This quote is valid until <strong><?= e(format_date((string) $proforma['valid_until'], 'd M Y')) ?></strong>.</p>
    <?php endif; ?>
    <p>Grand total: <strong><?= money((int) ($proforma['grand_total_paise'] ?? 0)) ?></strong></p>
    <?php if ($tempPassword !== ''): ?>
        <p>We have also created your customer account. Sign in with <strong><?= e($proforma['email'] ?? '') ?></strong> and temporary password <strong><?= e($tempPassword) ?></strong> at <a href="<?= e(abs_url('/login')) ?>"><?= e(abs_url('/login')) ?></a>. Please change your password after logging in.</p>
    <?php endif; ?>
    <p>You can also download the proforma from your account documents at <a href="<?= e(abs_url('/account/documents')) ?>"><?= e(abs_url('/account/documents')) ?></a>.</p>
    <p>Regards,<br>Ronak Fire India Pvt. Ltd.</p>
</div>

<?php
/** @var array $customer */
$addresses = [];
try {
    if ($customer && isset($customer['id'])) {
        $addresses = db()->all('SELECT * FROM customer_addresses WHERE customer_id = ? ORDER BY is_default DESC, id ASC', [(int) $customer['id']]);
    }
} catch (Throwable $e) {
    $addresses = [];
}
?>
<?php if ($addresses): ?>
<div class="form-card" aria-labelledby="savedAddrHeading">
    <div class="form-card__head">
        <div>
            <h2 id="savedAddrHeading">Saved addresses</h2>
            <p>Choose a saved address to prefill the billing/delivery details.</p>
        </div>
        <a class="text-link" href="/account/addresses">Manage addresses</a>
    </div>
    <div class="address-select">
        <?php foreach ($addresses as $addr): ?>
            <button type="button" class="address-select__card" data-address-select
                data-company="<?= e($addr['company_name'] ?: '') ?>"
                data-contact="<?= e($addr['contact_name'] ?: '') ?>"
                data-phone="<?= e($addr['phone'] ?: '') ?>"
                data-line1="<?= e($addr['address_line1']) ?>"
                data-line2="<?= e($addr['address_line2'] ?: '') ?>"
                data-city="<?= e($addr['city']) ?>"
                data-state="<?= e($addr['state']) ?>"
                data-pincode="<?= e($addr['pincode']) ?>"
            >
                <strong><?= e($addr['label']) ?><?= (int) $addr['is_default'] ? ' (Default)' : '' ?></strong>
                <p><?= e($addr['address_line1']) ?><?= $addr['address_line2'] ? ', ' . e($addr['address_line2']) : '' ?>, <?= e($addr['city']) ?>, <?= e($addr['state']) ?> - <?= e($addr['pincode']) ?></p>
            </button>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

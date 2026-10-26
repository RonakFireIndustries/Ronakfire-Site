<?php /** @var string $adminTitle */ ?>
<div class="admin-stack">
    <section class="admin-panel">
        <div class="admin-empty">
            <span class="admin-empty__code">404</span>
            <?= icon('search') ?>
            <h2><?= e($adminTitle ?? 'Not found') ?></h2>
            <p>The record you requested does not exist, or it was removed.</p>
            <a class="admin-btn admin-btn--primary" href="/admin">Back to dashboard</a>
        </div>
    </section>
</div>

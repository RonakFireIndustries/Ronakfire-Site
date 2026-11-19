<?php
/** @var array $customer */
/** @var array $proformas */
?>
<section class="page-hero page-hero--compact">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="/">Home</a><?= icon('chevron-right') ?><a href="/account">Account</a><?= icon('chevron-right') ?><span>Documents</span>
        </nav>
        <div class="page-hero__grid">
            <div>
                <p class="eyebrow"><span></span> Documents</p>
                <h1>Your documents</h1>
                <p class="page-hero__lede">Invoices, quotes and certificates shared with you.</p>
            </div>
        </div>
    </div>
</section>

<section class="section section--tight">
    <div class="container account-layout">
        <?php partial('account-nav'); ?>
        <div class="account-main">
            <div class="panel">
                <header class="panel__header">
                    <div>
                        <h2>Proforma invoices</h2>
                        <p><?= count($proformas) ?> <?= count($proformas) === 1 ? 'document' : 'documents' ?></p>
                    </div>
                </header>
                <div class="panel__body">
                    <?php if ($proformas): ?>
                        <div class="document-list">
                            <?php foreach ($proformas as $proforma): ?>
                                <article class="document-item">
                                    <div>
                                        <h3><?= e($proforma['proforma_no']) ?></h3>
                                        <p>Order <?= e($proforma['order_no'] ?: '—') ?> · <?= e(format_date($proforma['created_at'])) ?></p>
                                        <?php if (!empty($proforma['valid_until'])): ?>
                                            <p>Valid until <?= e(format_date((string) $proforma['valid_until'], 'd M Y')) ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="document-item__actions">
                                        <strong><?= money((int) $proforma['grand_total_paise']) ?></strong>
                                        <a class="btn btn--outline btn--sm" href="/account/proformas/<?= (int) $proforma['id'] ?>/pdf" target="_blank" rel="noopener"><?= icon('download') ?> Download PDF</a>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="muted">Invoices, proforma invoices and supporting documents will appear here when shared.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

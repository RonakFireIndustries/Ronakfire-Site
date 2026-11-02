<?php
/** @var array $customer */
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
                        <h2>Documents</h2>
                        <p>No documents shared yet.</p>
                    </div>
                </header>
                <div class="panel__body">
                    <p class="muted">Invoices, proforma invoices and supporting documents will appear here when shared.</p>
                </div>
            </div>
        </div>
    </div>
</section>

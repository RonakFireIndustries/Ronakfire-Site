<?php
/** @var array $customer */
?>
<section class="page-hero page-hero--compact">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="/">Home</a><?= icon('chevron-right') ?><a href="/account">Account</a><?= icon('chevron-right') ?><span>Inventory</span>
        </nav>
        <div class="page-hero__grid">
            <div>
                <p class="eyebrow"><span></span> Inventory</p>
                <h1>Your inventory</h1>
                <p class="page-hero__lede">Items available to your account will appear here.</p>
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
                        <h2>Inventory</h2>
                        <p>No inventory items assigned to your account yet.</p>
                    </div>
                </header>
                <div class="panel__body">
                    <p class="muted">Once our sales team allocates inventory to your account, it will be listed here.</p>
                </div>
            </div>
        </div>
    </div>
</section>

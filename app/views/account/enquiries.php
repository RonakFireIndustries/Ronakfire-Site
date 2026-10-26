<?php /** @var array $enquiries */ ?>
<section class="page-hero page-hero--compact">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="/">Home</a><?= icon('chevron-right') ?><a href="/account">Account</a><?= icon('chevron-right') ?><span>Quote requests</span>
        </nav>
        <div class="page-hero__grid">
            <div>
                <p class="eyebrow"><span></span> Specifications sent</p>
                <h1>Quote requests</h1>
                <p class="page-hero__lede">Requirements you have sent to our sales engineers, with the latest response status.</p>
            </div>
            <div class="page-hero__aside page-hero__aside--account">
                <a class="btn btn--primary" href="/quote"><?= icon('plus') ?> New request</a>
            </div>
        </div>
    </div>
</section>

<section class="section section--tight">
    <div class="container account-layout">
        <?php partial('account-nav'); ?>
        <div class="account-main">
            <div class="panel">
                <div class="panel__head">
                    <div><h2>Your requests</h2><p><?= count($enquiries) ?> <?= count($enquiries) === 1 ? 'request' : 'requests' ?></p></div>
                </div>
                <?php if ($enquiries): ?>
                    <div class="quote-request-list">
                        <?php foreach ($enquiries as $enquiry): ?>
                            <article class="quote-request">
                                <div class="quote-request__head">
                                    <div>
                                        <p class="cart-item__meta">Request #<?= (int) $enquiry['id'] ?> · <?= e(format_datetime($enquiry['created_at'])) ?></p>
                                        <h3><?= e($enquiry['product_name'] ?: 'General requirement') ?></h3>
                                    </div>
                                    <span class="status-pill status-<?= e($enquiry['status']) ?>"><?= e(ucfirst($enquiry['status'])) ?></span>
                                </div>
                                <div class="quote-request__details">
                                    <span><small>Quantity</small><strong><?= (int) $enquiry['qty'] ?></strong></span>
                                    <?php if (!empty($enquiry['configuration'])): ?>
                                        <span class="quote-request__config"><small>Configuration</small><strong><?= e($enquiry['configuration']) ?></strong></span>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($enquiry['message'])): ?>
                                    <p class="quote-request__message"><?= nl2br(e($enquiry['message'])) ?></p>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state empty-state--inline">
                        <span class="empty-state__icon"><?= icon('clipboard') ?></span>
                        <h2>Nothing sent yet</h2>
                        <p>Send a product specification, drawing or BOQ and our team will respond with a quotation.</p>
                        <a class="btn btn--primary" href="/quote">Request a quote</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

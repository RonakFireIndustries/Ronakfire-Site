<?php
$sections = [
    ['confirmation', '1. Order confirmation'],
    ['cancellation', '2. Cancellation policy'],
    ['refunds', '3. Refund policy'],
    ['replacement', '4. Replacement policy'],
    ['installed', '5. Installed or used products'],
    ['shipping', '6. Shipping, delivery & returns'],
    ['refund-mode', '7. Refund mode & timeline'],
    ['non-refundable', '8. Non-refundable items'],
    ['dispute', '9. Dispute'],
    ['contact', '10. Refund & cancellation requests'],
];
?>
<section class="page-hero page-hero--compact">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="/">Home</a><?= icon('chevron-right') ?><span>Refund &amp; cancellation</span>
        </nav>
        <div class="page-hero__grid">
            <div>
                <p class="eyebrow"><span></span> Legal</p>
                <h1>Refund &amp; cancellation policy</h1>
                <p class="page-hero__lede">The terms that govern orders, cancellations, replacements and refunds for products purchased from Ronak Fire India Pvt. Ltd.</p>
            </div>
            <div class="page-hero__aside">
                <strong>10</strong>
                <span>Sections</span>
                <small>Updated 15 November 2025</small>
            </div>
        </div>
    </div>
</section>

<section class="section section--tight">
    <div class="container legal-layout">
        <aside class="legal-toc" aria-label="On this page">
            <h2>On this page</h2>
            <nav>
                <?php foreach ($sections as [$id, $label]): ?>
                    <a href="#<?= e($id) ?>"><?= e($label) ?></a>
                <?php endforeach; ?>
            </nav>
        </aside>

        <article class="legal-prose">
            <p class="legal-updated">Last updated: 15 November 2025</p>
            <p>This Refund &amp; Cancellation Policy (“Policy”) governs all orders, purchases, payments and product-related transactions made with Ronak Fire India Pvt. Ltd. (“Company”, “we”, “our” or “us”). By placing an order with us, the customer (“Client”, “Customer”, “you”) agrees to the terms outlined herein.</p>

            <section id="confirmation">
                <h2>1. Order confirmation</h2>
                <p>Orders are considered confirmed only upon receipt of advance payment as per the quotation or proforma invoice.</p>
                <p>Once an order is confirmed and production begins, cancellations are subject to the terms below.</p>
            </section>

            <section id="cancellation">
                <h2>2. Cancellation policy</h2>
                <h3>2.1 Cancellation before production</h3>
                <p>Customers may request cancellation within 24 hours of placing the order only if production has not started. A 100% refund will be issued for cancellations made within this period.</p>
                <h3>2.2 Cancellation after production begins</h3>
                <p>Once production, fabrication or material procurement has commenced:</p>
                <ul>
                    <li>Cancellation is not allowed, as our products are manufactured to order.</li>
                    <li>No refund of advance or payment will be issued.</li>
                </ul>
                <h3>2.3 Cancellation of customised or made-to-order products</h3>
                <p>Orders involving custom sizes, custom paint, branding or non-standard specifications are non-cancellable and non-refundable from the moment order processing begins.</p>
            </section>

            <section id="refunds">
                <h2>3. Refund policy</h2>
                <p>Refunds are only applicable in the following cases.</p>
                <h3>3.1 Duplicate payment</h3>
                <p>If a customer is charged twice due to a system or banking error, the excess amount will be refunded within 7–10 working days after verification.</p>
                <h3>3.2 Order cancellation</h3>
                <p>Eligible cancellations under section 2.1 will be refunded within 7–10 working days.</p>
                <h3>3.3 Non-deliverable orders</h3>
                <p>If the Company is unable to deliver the order due to unavailability of raw material, manufacturing issues or regulatory restrictions, a full refund will be processed.</p>
            </section>

            <section id="replacement">
                <h2>4. Replacement policy</h2>
                <p>We offer replacement, not refund, for manufacturing defects or product damage attributable to us. Replacement is applicable only if:</p>
                <ul>
                    <li>The issue is reported within 48 hours of delivery.</li>
                    <li>The customer shares clear photos or videos of the defect.</li>
                    <li>The product is unused and in original condition.</li>
                </ul>
                <p>No replacements will be accepted for:</p>
                <ul>
                    <li>Physical damage caused by mishandling</li>
                    <li>Installation damage</li>
                    <li>Improper storage by the customer</li>
                    <li>Wear and tear</li>
                    <li>Fire or accidental incidents</li>
                    <li>Unauthorised modifications</li>
                </ul>
            </section>

            <section id="installed">
                <h2>5. Installed or used products</h2>
                <p>Due to the nature of fire safety equipment, products that have been installed, used, tested, refilled or commissioned are not eligible for refund or replacement except in cases of confirmed manufacturing defects.</p>
            </section>

            <section id="shipping">
                <h2>6. Shipping, delivery &amp; returns</h2>
                <h3>6.1 Delivery delays</h3>
                <p>The Company is not liable for delays caused by transporters, strikes, natural calamities, government restrictions or customer unavailability.</p>
                <h3>6.2 Return shipment</h3>
                <p>If a product needs to be returned for inspection:</p>
                <ul>
                    <li>The product must be safely packaged by the customer.</li>
                    <li>Return shipping cost is borne by the customer unless the defect is confirmed and attributable to Ronak Fire India Pvt. Ltd.</li>
                </ul>
            </section>

            <section id="refund-mode">
                <h2>7. Refund mode &amp; timeline</h2>
                <p>Approved refunds will be processed through NEFT/RTGS, account transfer or the original mode of payment. The refund timeline is 7–10 working days after approval.</p>
            </section>

            <section id="non-refundable">
                <h2>8. Non-refundable items</h2>
                <p>The following are strictly non-refundable:</p>
                <ul>
                    <li>Delivery charges</li>
                    <li>Installation charges</li>
                    <li>Custom manufacturing orders</li>
                    <li>Goods damaged after delivery</li>
                    <li>Products misplaced by the customer</li>
                    <li>Products kept in improper storage leading to deterioration</li>
                </ul>
            </section>

            <section id="dispute">
                <h2>9. Dispute</h2>
                <p>All disputes arising from refunds, cancellations or product purchases shall be resolved under the jurisdiction of Mumbai Courts, Maharashtra, India.</p>
            </section>

            <section id="contact">
                <h2>10. Refund &amp; cancellation requests</h2>
                <p>Send refund or cancellation requests with your order or invoice number to:</p>
                <ul class="legal-contact">
                    <li><strong>Ronak Fire India Pvt. Ltd.</strong></li>
                    <li><strong>Address:</strong> E001, Kasturi Vandana Complex, Off Kasturi Vandana Industrial Estate, Goddev Phatak Road, Bhayandar East, Maharashtra 401105</li>
                    <li><strong>Phone:</strong> <a href="tel:+919323947512">+91 93239 47512</a></li>
                    <li><strong>Email:</strong> <a href="mailto:info@ronakfire.com">info@ronakfire.com</a> / <a href="mailto:sales@ronakfire.com">sales@ronakfire.com</a></li>
                    <li><strong>Contact page:</strong> <a href="/contact">ronakfire.com/contact</a></li>
                </ul>
            </section>
        </article>
    </div>
</section>

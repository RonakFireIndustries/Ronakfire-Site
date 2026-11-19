<?php
/** @var array $enquiry */
/** @var array|null $product */
/** @var array|null $customer */
/** @var array|null $proforma */
/** @var array $proformaLines */
/** @var array $proformaForm */
/** @var array $proformaErrors */
/** @var array $proformaSends */
$proformaTotals = \App\Proforma::calculate(
    $proformaLines,
    parse_money_to_paise((string) ($proformaForm['freight'] ?? '0')),
    parse_money_to_paise((string) ($proformaForm['discount'] ?? '0'))
);
?>
<div class="admin-page-head">
    <div>
        <p class="admin-eyebrow"><a href="/admin/enquiries">Quote requests</a> / #<?= (int) $enquiry['id'] ?></p>
        <h2><?= e($enquiry['product_name'] ?: 'General requirement') ?></h2>
        <p>Received <?= e(format_datetime($enquiry['created_at'])) ?> from <?= e($enquiry['company'] ?: $enquiry['name']) ?>.</p>
    </div>
    <div class="admin-page-head__actions">
        <a class="admin-btn admin-btn--ghost" href="/admin/enquiries">Back</a>
        <a class="admin-btn" href="mailto:<?= e($enquiry['email']) ?>?subject=<?= rawurlencode('Your quote request #' . $enquiry['id'] . ' — Ronak Fire') ?>"><?= icon('mail') ?> Reply by email</a>
    </div>
</div>

<div class="admin-grid admin-grid--main">
    <div class="admin-stack">
        <section class="admin-panel">
            <div class="admin-panel__head"><div><h2>Requirement</h2><p>The specification supplied by the customer.</p></div></div>
            <div class="admin-panel__body">
                <dl class="admin-meta-list admin-meta-list--wide">
                    <div><dt>Product</dt><dd><?= e($enquiry['product_name'] ?: 'General enquiry') ?></dd></div>
                    <div><dt>Quantity</dt><dd><?= (int) $enquiry['qty'] ?></dd></div>
                    <div><dt>Status</dt><dd><span class="admin-status admin-status--<?= e($enquiry['status']) ?>"><?= e(ucfirst($enquiry['status'])) ?></span></dd></div>
                    <div><dt>Received</dt><dd><?= e(format_datetime($enquiry['created_at'])) ?></dd></div>
                    <?php
                    $config = enquiry_configuration((string) $enquiry['configuration']);
                    $hasConfig = $config['text'] !== '' || $config['po_number'] !== '' || $config['notes'] !== '' || $config['items'] !== [];
                    ?>
                    <?php if ($hasConfig): ?>
                        <div class="admin-detail-grid__full admin-enquiry-config">
                            <dt>Configuration</dt>
                            <dd>
                                <?php if ($config['text'] !== ''): ?>
                                    <?= nl2br(e($config['text'])) ?>
                                <?php else: ?>
                                    <?php if ($config['po_number'] !== ''): ?>
                                        <p><strong>PO number:</strong> <?= e($config['po_number']) ?></p>
                                    <?php endif; ?>
                                    <?php if ($config['notes'] !== ''): ?>
                                        <p><strong>Notes:</strong> <?= e($config['notes']) ?></p>
                                    <?php endif; ?>
                                    <?php foreach ($config['items'] as $item): ?>
                                        <div class="admin-enquiry-config__item">
                                            <strong><?= e($item['name'] ?: 'Item') ?></strong>
                                            <small><?= e($item['sku']) ?><?= $item['sku'] !== '' ? ' · ' : '' ?><?= $item['qty'] ?> × <?= money($item['unit_price_paise']) ?> · <?= money($item['line_total_paise']) ?></small>
                                            <?php if ($item['options']): ?>
                                                <ul class="admin-option-list">
                                                    <?php foreach ($item['options'] as $option): ?><li><?= e($option) ?></li><?php endforeach; ?>
                                                </ul>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </dd>
                        </div>
                    <?php endif; ?>
                </dl>
                <div class="admin-message">
                    <h3>Message</h3>
                    <p><?= $enquiry['message'] ? nl2br(e($enquiry['message'])) : 'No additional message supplied.' ?></p>
                </div>
                <?php if ($product): ?>
                    <div class="admin-quote-product">
                        <?php if (!empty($product['image'])): ?><img src="<?= e($product['image']) ?>" alt=""><?php else: ?><?= icon('package') ?><?php endif; ?>
                        <div>
                            <strong><?= e($product['name']) ?></strong>
                            <small><?= e($product['sku']) ?> · <?= e($product['category_name']) ?></small>
                        </div>
                        <a class="admin-link" href="/product/<?= e($product['slug']) ?>" target="_blank" rel="noopener">View product <?= icon('arrow-right') ?></a>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="admin-panel admin-panel--form">
            <div class="admin-panel__head"><div><h2>Internal note</h2><p>Keep the customer-facing reply in email; use this for the store record.</p></div></div>
            <div class="admin-form-body">
                <p class="admin-hint-text"><?= icon('alert') ?> Notes are not currently stored. Add a status change to reflect progress, then reply by email.</p>
            </div>
        </section>

        <section class="admin-panel admin-panel--form" id="proformaPanel">
            <div class="admin-panel__head">
                <div>
                    <h2>Proforma invoice</h2>
                    <p>Set prices and quantities, then email a PDF to the customer.</p>
                </div>
                <?php if ($proforma && !empty($proforma['pdf_path'])): ?>
                    <a class="admin-btn admin-btn--ghost admin-btn--sm" href="/admin/enquiries/<?= (int) $enquiry['id'] ?>/proforma/pdf" target="_blank" rel="noopener"><?= icon('download') ?> Download PDF</a>
                <?php endif; ?>
            </div>
            <div class="admin-form-body">
                <?php if (!$customer): ?>
                    <p class="admin-hint-text"><?= icon('alert') ?> This visitor has no customer account yet. Sending the proforma will create one using the email below.</p>
                <?php endif; ?>
                <?php if ($proforma): ?>
                    <p class="admin-hint-text">Proforma <?= e($proforma['proforma_no']) ?> · Order <?= e($proformaOrder['order_no'] ?? '—') ?> · Grand total <?= money((int) $proforma['grand_total_paise']) ?></p>
                <?php endif; ?>
                <?php if ($proformaErrors): ?>
                    <div class="admin-alert admin-alert--error">
                        <ul><?php foreach ($proformaErrors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
                    </div>
                <?php endif; ?>

                <form method="post" action="/admin/enquiries/<?= (int) $enquiry['id'] ?>/proforma" data-proforma-form>
                    <?= csrf_field() ?>

                    <div class="admin-proforma-lines" data-proforma-lines>
                        <?php foreach ($proformaLines as $index => $line): ?>
                            <div class="admin-proforma-line" data-proforma-line>
                                <input type="hidden" name="lines[<?= (int) $index ?>][product_id]" value="<?= (int) ($line['product_id'] ?? 0) ?>">
                                <input type="hidden" name="lines[<?= (int) $index ?>][options_json]" value="<?= e($line['options_json'] ?? '') ?>">
                                <label class="admin-field admin-proforma-line__description">
                                    <span>Description</span>
                                    <input type="text" name="lines[<?= (int) $index ?>][name]" value="<?= e($line['name'] ?? '') ?>" required>
                                </label>
                                <label class="admin-field admin-proforma-line__sku">
                                    <span>SKU</span>
                                    <input type="text" name="lines[<?= (int) $index ?>][sku]" value="<?= e($line['sku'] ?? '') ?>">
                                </label>
                                <label class="admin-field admin-proforma-line__qty">
                                    <span>Qty</span>
                                    <input type="number" name="lines[<?= (int) $index ?>][qty]" value="<?= (int) ($line['qty'] ?? 1) ?>" min="1" step="1" data-line-qty required>
                                </label>
                                <label class="admin-field admin-proforma-line__price">
                                    <span>Rate (₹)</span>
                                    <input type="number" name="lines[<?= (int) $index ?>][unit_price]" value="<?= e($line['unit_price'] ?? '0.00') ?>" min="0" step="0.01" data-line-price required>
                                </label>
                                <label class="admin-field admin-proforma-line__gst">
                                    <span>GST %</span>
                                    <input type="number" name="lines[<?= (int) $index ?>][gst_rate]" value="<?= e($line['gst_rate'] ?? '18') ?>" min="0" max="100" step="0.01" data-line-gst>
                                </label>
                                <div class="admin-proforma-line__total">
                                    <span>Line total</span>
                                    <strong data-line-total><?= money((int) ($line['line_total_paise'] ?? 0)) ?></strong>
                                </div>
                                <button class="admin-icon-btn admin-icon-btn--danger" type="button" data-remove-line title="Remove line" aria-label="Remove line"><?= icon('trash') ?></button>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <template id="proformaLineTemplate">
                        <div class="admin-proforma-line" data-proforma-line>
                            <input type="hidden" name="lines[__INDEX__][product_id]" value="0">
                            <input type="hidden" name="lines[__INDEX__][options_json]" value="">
                            <label class="admin-field admin-proforma-line__description">
                                <span>Description</span>
                                <input type="text" name="lines[__INDEX__][name]" value="" required>
                            </label>
                            <label class="admin-field admin-proforma-line__sku">
                                <span>SKU</span>
                                <input type="text" name="lines[__INDEX__][sku]" value="">
                            </label>
                            <label class="admin-field admin-proforma-line__qty">
                                <span>Qty</span>
                                <input type="number" name="lines[__INDEX__][qty]" value="1" min="1" step="1" data-line-qty required>
                            </label>
                            <label class="admin-field admin-proforma-line__price">
                                <span>Rate (₹)</span>
                                <input type="number" name="lines[__INDEX__][unit_price]" value="0.00" min="0" step="0.01" data-line-price required>
                            </label>
                            <label class="admin-field admin-proforma-line__gst">
                                <span>GST %</span>
                                <input type="number" name="lines[__INDEX__][gst_rate]" value="18" min="0" max="100" step="0.01" data-line-gst>
                            </label>
                            <div class="admin-proforma-line__total">
                                <span>Line total</span>
                                <strong data-line-total><?= money(0) ?></strong>
                            </div>
                            <button class="admin-icon-btn admin-icon-btn--danger" type="button" data-remove-line title="Remove line" aria-label="Remove line"><?= icon('trash') ?></button>
                        </div>
                    </template>

                    <button class="admin-btn admin-btn--ghost admin-btn--sm" type="button" data-add-line><?= icon('plus') ?> Add line</button>

                    <div class="admin-field-grid admin-proforma-meta">
                        <label class="admin-field">
                            <span>Customer email</span>
                            <input type="email" name="email" value="<?= e($proformaForm['email'] ?? $enquiry['email']) ?>" required>
                        </label>
                        <label class="admin-field">
                            <span>PO number <small>optional</small></span>
                            <input type="text" name="po_number" value="<?= e($proformaForm['po_number'] ?? '') ?>">
                        </label>
                        <label class="admin-field">
                            <span>Valid until</span>
                            <input type="date" name="valid_until" value="<?= e($proformaForm['valid_until'] ?? '') ?>">
                        </label>
                        <label class="admin-field">
                            <span>Freight (₹)</span>
                            <input type="number" name="freight" value="<?= e($proformaForm['freight'] ?? '0.00') ?>" min="0" step="0.01" data-proforma-freight>
                        </label>
                        <label class="admin-field">
                            <span>Discount (₹)</span>
                            <input type="number" name="discount" value="<?= e($proformaForm['discount'] ?? '0.00') ?>" min="0" step="0.01" data-proforma-discount>
                        </label>
                        <label class="admin-field admin-field--full">
                            <span>Customer-facing notes <small>optional</small></span>
                            <textarea name="notes" rows="2"><?= e($proformaForm['notes'] ?? '') ?></textarea>
                        </label>
                    </div>

                    <div class="admin-proforma-summary">
                        <div><span>Subtotal</span><strong data-summary-subtotal><?= money($proformaTotals['subtotal_paise']) ?></strong></div>
                        <div><span>GST</span><strong data-summary-gst><?= money($proformaTotals['gst_paise']) ?></strong></div>
                        <div><span>Freight</span><strong data-summary-freight><?= money($proformaTotals['freight_paise']) ?></strong></div>
                        <div><span>Discount</span><strong data-summary-discount>&minus; <?= money($proformaTotals['discount_paise']) ?></strong></div>
                        <div class="is-total"><span>Grand total</span><strong data-summary-total><?= money($proformaTotals['grand_total_paise']) ?></strong></div>
                    </div>

                    <div class="admin-form-actions">
                        <button class="admin-btn admin-btn--primary" type="submit"><?= $proforma ? 'Update & resend proforma' : 'Create & send proforma' ?></button>
                    </div>
                </form>

                <?php if ($proformaSends): ?>
                    <div class="admin-proforma-history">
                        <h3>Send history</h3>
                        <ul>
                            <?php foreach ($proformaSends as $send): ?>
                                <li>
                                    <strong><?= e(format_datetime($send['sent_at'])) ?></strong>
                                    <span><?= e($send['email']) ?> · <?= money((int) $send['total_paise']) ?> · <?= e(ucfirst($send['status'])) ?><?= !empty($send['sent_by_name']) ? ' · ' . e($send['sent_by_name']) : '' ?></span>
                                    <?php if (!empty($send['error'])): ?><small><?= e($send['error']) ?></small><?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <aside class="admin-stack">
        <section class="admin-panel">
            <div class="admin-panel__head"><div><h2>Progress</h2></div></div>
            <div class="admin-panel__body">
                <form action="/admin/enquiries/<?= (int) $enquiry['id'] ?>/status" method="post" class="admin-inline-form admin-inline-form--stacked">
                    <?= csrf_field() ?>
                    <label class="admin-field">
                        <span>Status</span>
                        <select name="status">
                            <?php foreach (['new' => 'New', 'contacted' => 'Contacted', 'quoted' => 'Quoted', 'closed' => 'Closed'] as $value => $label): ?>
                                <option value="<?= e($value) ?>" <?= $enquiry['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <button class="admin-btn admin-btn--primary admin-btn--block" type="submit">Save status</button>
                </form>
            </div>
        </section>

        <section class="admin-panel">
            <div class="admin-panel__head"><div><h2>Contact</h2></div></div>
            <div class="admin-panel__body">
                <div class="admin-address">
                    <strong><?= e($enquiry['name']) ?></strong>
                    <p><?= e($enquiry['company'] ?: '') ?><br>
                        <?= e($enquiry['phone'] ?: '') ?><br>
                        <a href="mailto:<?= e($enquiry['email']) ?>"><?= e($enquiry['email']) ?></a></p>
                    <?php if (!empty($enquiry['gstin'])): ?><p><span class="admin-tag">GSTIN <?= e($enquiry['gstin']) ?></span></p><?php endif; ?>
                </div>
                <?php if ($customer): ?>
                    <p class="admin-hint-text">Sales owner: <?= e($customer['sales_owner_name'] ?: 'Unassigned') ?></p>
                    <a class="admin-btn admin-btn--block" href="/admin/customers/<?= (int) $customer['id'] ?>"><?= icon('users') ?> Open customer account</a>
                <?php else: ?>
                    <p class="admin-hint-text"><?= icon('alert') ?> This enquiry was sent by a visitor who was not signed in.</p>
                <?php endif; ?>
            </div>
        </section>
    </aside>
</div>

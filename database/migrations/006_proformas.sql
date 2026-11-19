-- MySQL / MariaDB: proforma invoices created from quote requests.
-- Run once against an existing installation.

ALTER TABLE orders
    ADD COLUMN discount_paise INT NOT NULL DEFAULT 0 AFTER freight_paise,
    ADD COLUMN valid_until DATE NULL AFTER discount_paise,
    ADD COLUMN source_enquiry_id INT NULL AFTER valid_until;

CREATE TABLE IF NOT EXISTS proformas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    enquiry_id INT NOT NULL,
    order_id INT NOT NULL,
    customer_id INT NOT NULL,
    proforma_no VARCHAR(64) NOT NULL UNIQUE,
    email VARCHAR(191) NOT NULL,
    po_number VARCHAR(191) NULL,
    notes TEXT NULL,
    valid_until DATE NULL,
    freight_paise INT NOT NULL DEFAULT 0,
    discount_paise INT NOT NULL DEFAULT 0,
    subtotal_paise INT NOT NULL DEFAULT 0,
    gst_paise INT NOT NULL DEFAULT 0,
    grand_total_paise INT NOT NULL DEFAULT 0,
    pdf_path VARCHAR(512) NULL,
    created_by INT NULL,
    last_sent_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uniq_proformas_enquiry (enquiry_id),
    KEY idx_proformas_customer (customer_id, created_at),
    CONSTRAINT fk_proformas_enquiry FOREIGN KEY (enquiry_id) REFERENCES enquiries(id),
    CONSTRAINT fk_proformas_order FOREIGN KEY (order_id) REFERENCES orders(id),
    CONSTRAINT fk_proformas_customer FOREIGN KEY (customer_id) REFERENCES customers(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS proforma_sends (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proforma_id INT NOT NULL,
    sent_at DATETIME NOT NULL,
    sent_by INT NULL,
    email VARCHAR(191) NOT NULL,
    total_paise INT NOT NULL DEFAULT 0,
    pdf_path VARCHAR(512) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'sent',
    error TEXT NULL,
    created_at DATETIME NOT NULL,
    KEY idx_proforma_sends_proforma (proforma_id, sent_at),
    CONSTRAINT fk_proforma_sends_proforma FOREIGN KEY (proforma_id) REFERENCES proformas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

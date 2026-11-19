-- SQLite: proforma invoices created from quote requests.
-- Run once against an existing installation.

ALTER TABLE orders ADD COLUMN discount_paise INTEGER NOT NULL DEFAULT 0;
ALTER TABLE orders ADD COLUMN valid_until TEXT;
ALTER TABLE orders ADD COLUMN source_enquiry_id INTEGER;

CREATE TABLE IF NOT EXISTS proformas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    enquiry_id INTEGER NOT NULL UNIQUE,
    order_id INTEGER NOT NULL,
    customer_id INTEGER NOT NULL,
    proforma_no TEXT NOT NULL UNIQUE,
    email TEXT NOT NULL,
    po_number TEXT,
    notes TEXT,
    valid_until TEXT,
    freight_paise INTEGER NOT NULL DEFAULT 0,
    discount_paise INTEGER NOT NULL DEFAULT 0,
    subtotal_paise INTEGER NOT NULL DEFAULT 0,
    gst_paise INTEGER NOT NULL DEFAULT 0,
    grand_total_paise INTEGER NOT NULL DEFAULT 0,
    pdf_path TEXT,
    created_by INTEGER,
    last_sent_at TEXT,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY (enquiry_id) REFERENCES enquiries(id),
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);

CREATE TABLE IF NOT EXISTS proforma_sends (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    proforma_id INTEGER NOT NULL,
    sent_at TEXT NOT NULL,
    sent_by INTEGER,
    email TEXT NOT NULL,
    total_paise INTEGER NOT NULL DEFAULT 0,
    pdf_path TEXT,
    status TEXT NOT NULL DEFAULT 'sent',
    error TEXT,
    created_at TEXT NOT NULL,
    FOREIGN KEY (proforma_id) REFERENCES proformas(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_proformas_customer ON proformas (customer_id, created_at);
CREATE INDEX IF NOT EXISTS idx_proforma_sends_proforma ON proforma_sends (proforma_id, sent_at);

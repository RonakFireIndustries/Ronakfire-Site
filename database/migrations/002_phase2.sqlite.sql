-- Phase 2 migrations (SQLite)
-- Safe to run multiple times

CREATE TABLE IF NOT EXISTS customer_addresses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_id INTEGER NOT NULL,
    label TEXT NOT NULL DEFAULT 'Default',
    company_name TEXT,
    contact_name TEXT,
    phone TEXT,
    address_line1 TEXT NOT NULL,
    address_line2 TEXT,
    city TEXT NOT NULL,
    state TEXT NOT NULL,
    pincode TEXT NOT NULL,
    is_default INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

ALTER TABLE customers ADD COLUMN portal_synced_at TEXT;
ALTER TABLE products ADD COLUMN stock_qty INTEGER;
ALTER TABLE products ADD COLUMN stock_updated_at TEXT;
ALTER TABLE orders ADD COLUMN courier_name TEXT;
ALTER TABLE orders ADD COLUMN tracking_no TEXT;
ALTER TABLE orders ADD COLUMN tracking_url TEXT;

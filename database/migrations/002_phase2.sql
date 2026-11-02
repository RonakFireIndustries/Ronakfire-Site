-- Phase 2 migrations (MySQL)
-- Safe to run multiple times (migrate.php ignores duplicate-column errors)

CREATE TABLE IF NOT EXISTS customer_addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    label VARCHAR(64) NOT NULL DEFAULT 'Default',
    company_name VARCHAR(191) NULL,
    contact_name VARCHAR(191) NULL,
    phone VARCHAR(64) NULL,
    address_line1 VARCHAR(255) NOT NULL,
    address_line2 VARCHAR(255) NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    pincode VARCHAR(16) NOT NULL,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_addr_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE customers ADD COLUMN portal_synced_at DATETIME NULL AFTER updated_at;
ALTER TABLE products ADD COLUMN stock_qty INT NULL AFTER lead_time;
ALTER TABLE products ADD COLUMN stock_updated_at DATETIME NULL AFTER stock_qty;
ALTER TABLE orders ADD COLUMN courier_name VARCHAR(100) NULL AFTER delivered_at;
ALTER TABLE orders ADD COLUMN tracking_no VARCHAR(191) NULL AFTER courier_name;
ALTER TABLE orders ADD COLUMN tracking_url VARCHAR(512) NULL AFTER tracking_no;

-- MySQL 8 / MariaDB schema (production cPanel)
-- Import this file, then run: php bin/install.php --seed-only

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(191) NOT NULL UNIQUE,
    name VARCHAR(191) NOT NULL,
    short_name VARCHAR(191) NULL,
    tagline VARCHAR(255) NULL,
    description TEXT NULL,
    image VARCHAR(255) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    slug VARCHAR(191) NOT NULL UNIQUE,
    name VARCHAR(191) NOT NULL,
    sku VARCHAR(191) NULL,
    product_type VARCHAR(32) NOT NULL DEFAULT 'simple',
    short_description TEXT NULL,
    description MEDIUMTEXT NULL,
    specs_json TEXT NULL,
    image VARCHAR(255) NULL,
    gallery_json TEXT NULL,
    datasheet VARCHAR(255) NULL,
    base_price_paise INT NOT NULL DEFAULT 0,
    dim_rate_paise_sqm INT NOT NULL DEFAULT 0,
    price_note VARCHAR(255) NULL,
    unit VARCHAR(32) NOT NULL DEFAULT 'Nos',
    hsn_code VARCHAR(32) NULL,
    gst_rate_bp INT NOT NULL DEFAULT 1800,
    min_qty INT NOT NULL DEFAULT 1,
    lead_time VARCHAR(191) NULL,
    stock_qty INT NULL,
    stock_updated_at DATETIME NULL,
    warranty VARCHAR(191) NULL,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    meta_title VARCHAR(255) NULL,
    meta_description VARCHAR(255) NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS option_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    name VARCHAR(191) NOT NULL,
    slug VARCHAR(191) NOT NULL,
    help_text VARCHAR(255) NULL,
    input_type VARCHAR(32) NOT NULL DEFAULT 'radio',
    is_required TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    min_value INT NULL,
    max_value INT NULL,
    step_value INT NULL,
    default_w INT NULL,
    default_h INT NULL,
    default_d INT NULL,
    unit_label VARCHAR(32) NULL,
    CONSTRAINT fk_groups_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS option_values (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    label VARCHAR(191) NOT NULL,
    sku_suffix VARCHAR(64) NULL,
    price_delta_paise INT NOT NULL DEFAULT 0,
    price_multiplier_bp INT NOT NULL DEFAULT 0,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    meta_json TEXT NULL,
    CONSTRAINT fk_values_group FOREIGN KEY (group_id) REFERENCES option_groups(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bundle_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    component_product_id INT NOT NULL,
    qty INT NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    CONSTRAINT fk_bundle_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT fk_bundle_component FOREIGN KEY (component_product_id) REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    portal_id VARCHAR(191) NOT NULL UNIQUE,
    customer_code VARCHAR(64) NULL,
    company_name VARCHAR(191) NOT NULL,
    contact_name VARCHAR(191) NULL,
    email VARCHAR(191) NULL,
    phone VARCHAR(64) NULL,
    gstin VARCHAR(32) NULL,
    address_line1 VARCHAR(255) NULL,
    address_line2 VARCHAR(255) NULL,
    city VARCHAR(100) NULL,
    state VARCHAR(100) NULL,
    pincode VARCHAR(16) NULL,
    password_hash VARCHAR(255) NULL,
    password_set_at DATETIME NULL,
    price_tier VARCHAR(64) NOT NULL DEFAULT 'standard',
    discount_bp INT NOT NULL DEFAULT 0,
    sales_owner_id INT NULL,
    credit_days INT NOT NULL DEFAULT 0,
    credit_limit_paise INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    portal_synced_at DATETIME NULL,
    KEY idx_customers_sales_owner (sales_owner_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS carts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL UNIQUE,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_carts_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cart_id INT NOT NULL,
    product_id INT NOT NULL,
    qty INT NOT NULL DEFAULT 1,
    options_json TEXT NULL,
    config_hash VARCHAR(64) NOT NULL,
    unit_price_paise INT NOT NULL,
    gst_rate_bp INT NOT NULL DEFAULT 1800,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY cart_items_unique (cart_id, product_id, config_hash),
    CONSTRAINT fk_cart_items_cart FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
    CONSTRAINT fk_cart_items_product FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_no VARCHAR(64) NOT NULL UNIQUE,
    customer_id INT NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'placed',
    po_number VARCHAR(191) NULL,
    notes TEXT NULL,
    subtotal_paise INT NOT NULL DEFAULT 0,
    gst_paise INT NOT NULL DEFAULT 0,
    freight_paise INT NOT NULL DEFAULT 0,
    discount_paise INT NOT NULL DEFAULT 0,
    valid_until DATE NULL,
    source_enquiry_id INT NULL,
    grand_total_paise INT NOT NULL DEFAULT 0,
    billing_json TEXT NULL,
    shipping_json TEXT NULL,
    admin_note TEXT NULL,
    placed_at DATETIME NULL,
    confirmed_at DATETIME NULL,
    invoiced_at DATETIME NULL,
    dispatched_at DATETIME NULL,
    courier_name VARCHAR(100) NULL,
    tracking_no VARCHAR(191) NULL,
    tracking_url VARCHAR(512) NULL,
    delivered_at DATETIME NULL,
    cancelled_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    KEY idx_orders_customer (customer_id, placed_at),
    KEY idx_orders_status (status),
    CONSTRAINT fk_orders_customer FOREIGN KEY (customer_id) REFERENCES customers(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NULL,
    product_name VARCHAR(191) NOT NULL,
    sku VARCHAR(191) NULL,
    options_json TEXT NULL,
    qty INT NOT NULL,
    unit_price_paise INT NOT NULL,
    gst_rate_bp INT NOT NULL DEFAULT 1800,
    line_subtotal_paise INT NOT NULL,
    line_gst_paise INT NOT NULL,
    line_total_paise INT NOT NULL,
    CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enquiries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NULL,
    name VARCHAR(191) NOT NULL,
    company VARCHAR(191) NULL,
    email VARCHAR(191) NOT NULL,
    phone VARCHAR(64) NULL,
    gstin VARCHAR(32) NULL,
    product_id INT NULL,
    product_name VARCHAR(191) NULL,
    qty INT NULL,
    configuration TEXT NULL,
    message TEXT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'new',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    KEY idx_enquiries_status (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(191) NOT NULL,
    email VARCHAR(191) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(32) NOT NULL DEFAULT 'admin',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS settings (
    `key` VARCHAR(191) PRIMARY KEY,
    `value` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

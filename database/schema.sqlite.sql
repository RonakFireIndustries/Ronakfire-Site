-- SQLite schema (local development)
-- Production uses schema.mysql.sql.

CREATE TABLE IF NOT EXISTS categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    slug TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    short_name TEXT,
    tagline TEXT,
    description TEXT,
    image TEXT,
    sort_order INTEGER NOT NULL DEFAULT 0,
    is_active INTEGER NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    category_id INTEGER NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    sku TEXT,
    product_type TEXT NOT NULL DEFAULT 'simple',
    short_description TEXT,
    description TEXT,
    specs_json TEXT,
    image TEXT,
    gallery_json TEXT,
    datasheet TEXT,
    base_price_paise INTEGER NOT NULL DEFAULT 0,
    dim_rate_paise_sqm INTEGER NOT NULL DEFAULT 0,
    price_note TEXT,
    unit TEXT NOT NULL DEFAULT 'Nos',
    hsn_code TEXT,
    gst_rate_bp INTEGER NOT NULL DEFAULT 1800,
    min_qty INTEGER NOT NULL DEFAULT 1,
    lead_time TEXT,
    stock_qty INTEGER,
    stock_updated_at TEXT,
    warranty TEXT,
    is_featured INTEGER NOT NULL DEFAULT 0,
    is_active INTEGER NOT NULL DEFAULT 1,
    sort_order INTEGER NOT NULL DEFAULT 0,
    meta_title TEXT,
    meta_description TEXT,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

CREATE TABLE IF NOT EXISTS option_groups (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    slug TEXT NOT NULL,
    help_text TEXT,
    input_type TEXT NOT NULL DEFAULT 'radio',
    is_required INTEGER NOT NULL DEFAULT 1,
    sort_order INTEGER NOT NULL DEFAULT 0,
    min_value INTEGER,
    max_value INTEGER,
    step_value INTEGER,
    default_w INTEGER,
    default_h INTEGER,
    default_d INTEGER,
    unit_label TEXT,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS option_values (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    group_id INTEGER NOT NULL,
    label TEXT NOT NULL,
    sku_suffix TEXT,
    price_delta_paise INTEGER NOT NULL DEFAULT 0,
    price_multiplier_bp INTEGER NOT NULL DEFAULT 0,
    is_default INTEGER NOT NULL DEFAULT 0,
    sort_order INTEGER NOT NULL DEFAULT 0,
    meta_json TEXT,
    FOREIGN KEY (group_id) REFERENCES option_groups(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS bundle_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL,
    component_product_id INTEGER NOT NULL,
    qty INTEGER NOT NULL DEFAULT 1,
    sort_order INTEGER NOT NULL DEFAULT 0,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (component_product_id) REFERENCES products(id)
);

CREATE TABLE IF NOT EXISTS customers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    portal_id TEXT NOT NULL UNIQUE,
    customer_code TEXT,
    company_name TEXT NOT NULL,
    contact_name TEXT,
    email TEXT,
    phone TEXT,
    gstin TEXT,
    address_line1 TEXT,
    address_line2 TEXT,
    city TEXT,
    state TEXT,
    pincode TEXT,
    price_tier TEXT NOT NULL DEFAULT 'standard',
    discount_bp INTEGER NOT NULL DEFAULT 0,
    sales_owner_id INTEGER,
    credit_days INTEGER NOT NULL DEFAULT 0,
    credit_limit_paise INTEGER NOT NULL DEFAULT 0,
    is_active INTEGER NOT NULL DEFAULT 1,
    last_login_at TEXT,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    portal_synced_at TEXT
);

CREATE TABLE IF NOT EXISTS carts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_id INTEGER NOT NULL UNIQUE,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS cart_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    cart_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    qty INTEGER NOT NULL DEFAULT 1,
    options_json TEXT,
    config_hash TEXT NOT NULL,
    unit_price_paise INTEGER NOT NULL,
    gst_rate_bp INTEGER NOT NULL DEFAULT 1800,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

CREATE UNIQUE INDEX IF NOT EXISTS cart_items_unique ON cart_items (cart_id, product_id, config_hash);

CREATE TABLE IF NOT EXISTS orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_no TEXT NOT NULL UNIQUE,
    customer_id INTEGER NOT NULL,
    status TEXT NOT NULL DEFAULT 'placed',
    po_number TEXT,
    notes TEXT,
    subtotal_paise INTEGER NOT NULL DEFAULT 0,
    gst_paise INTEGER NOT NULL DEFAULT 0,
    freight_paise INTEGER NOT NULL DEFAULT 0,
    grand_total_paise INTEGER NOT NULL DEFAULT 0,
    billing_json TEXT,
    shipping_json TEXT,
    admin_note TEXT,
    placed_at TEXT,
    confirmed_at TEXT,
    invoiced_at TEXT,
    dispatched_at TEXT,
    courier_name TEXT,
    tracking_no TEXT,
    tracking_url TEXT,
    delivered_at TEXT,
    cancelled_at TEXT,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);

CREATE TABLE IF NOT EXISTS order_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL,
    product_id INTEGER,
    product_name TEXT NOT NULL,
    sku TEXT,
    options_json TEXT,
    qty INTEGER NOT NULL,
    unit_price_paise INTEGER NOT NULL,
    gst_rate_bp INTEGER NOT NULL DEFAULT 1800,
    line_subtotal_paise INTEGER NOT NULL,
    line_gst_paise INTEGER NOT NULL,
    line_total_paise INTEGER NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS enquiries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_id INTEGER,
    name TEXT NOT NULL,
    company TEXT,
    email TEXT NOT NULL,
    phone TEXT,
    gstin TEXT,
    product_id INTEGER,
    product_name TEXT,
    qty INTEGER,
    configuration TEXT,
    message TEXT,
    status TEXT NOT NULL DEFAULT 'new',
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS admin_users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'admin',
    is_active INTEGER NOT NULL DEFAULT 1,
    last_login_at TEXT,
    created_at TEXT NOT NULL
);

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

CREATE TABLE IF NOT EXISTS settings (
    key TEXT PRIMARY KEY,
    value TEXT
);

CREATE INDEX IF NOT EXISTS idx_products_category ON products (category_id, is_active);
CREATE INDEX IF NOT EXISTS idx_option_groups_product ON option_groups (product_id);
CREATE INDEX IF NOT EXISTS idx_option_values_group ON option_values (group_id);
CREATE INDEX IF NOT EXISTS idx_orders_customer ON orders (customer_id, placed_at);
CREATE INDEX IF NOT EXISTS idx_customers_sales_owner ON customers (sales_owner_id);
CREATE INDEX IF NOT EXISTS idx_orders_status ON orders (status);
CREATE INDEX IF NOT EXISTS idx_enquiries_status ON enquiries (status, created_at);

-- Add guest support to carts
ALTER TABLE carts ADD COLUMN guest_id TEXT;
CREATE UNIQUE INDEX IF NOT EXISTS idx_carts_guest ON carts(guest_id);

-- MySQL / MariaDB: link each customer to the sales user who owns the account.
-- Safe to skip the column if it already exists (check information_schema first).
ALTER TABLE customers ADD COLUMN sales_owner_id INT NULL AFTER discount_bp;
ALTER TABLE customers ADD INDEX idx_customers_sales_owner (sales_owner_id);

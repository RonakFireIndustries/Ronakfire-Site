-- SQLite: link each customer to the sales user who owns the account.
-- ALTER TABLE ADD COLUMN cannot include a foreign key, so the link is enforced in PHP.
ALTER TABLE customers ADD COLUMN sales_owner_id INTEGER;
CREATE INDEX IF NOT EXISTS idx_customers_sales_owner ON customers (sales_owner_id);

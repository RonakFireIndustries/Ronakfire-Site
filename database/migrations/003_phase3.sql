-- Phase 3 migrations (MySQL)
-- Safe to run multiple times (migrate.php ignores duplicate-column errors)

ALTER TABLE customers ADD COLUMN password_hash VARCHAR(255) NULL AFTER pincode;
ALTER TABLE customers ADD COLUMN password_set_at DATETIME NULL AFTER password_hash;

-- Phase 3 migrations (SQLite)
ALTER TABLE customers ADD COLUMN password_hash VARCHAR(255) NULL;
ALTER TABLE customers ADD COLUMN password_set_at DATETIME NULL;

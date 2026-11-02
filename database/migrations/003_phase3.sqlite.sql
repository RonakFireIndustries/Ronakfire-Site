-- Phase 3 migrations (SQLite)
ALTER TABLE customers ADD COLUMN password_hash VARCHAR(255) NULL;
ALTER TABLE customers ADD COLUMN password_set_at DATETIME NULL;

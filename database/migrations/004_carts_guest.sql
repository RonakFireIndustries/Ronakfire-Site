ALTER TABLE carts MODIFY customer_id INT NULL;
ALTER TABLE carts ADD COLUMN guest_id VARCHAR(64) NULL AFTER customer_id;
CREATE UNIQUE INDEX idx_carts_guest ON carts(guest_id);

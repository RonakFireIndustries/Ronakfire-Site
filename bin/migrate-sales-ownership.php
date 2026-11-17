<?php
/**
 * Idempotent migration: add customers.sales_owner_id to the configured database.
 *
 * Usage: php bin/migrate-sales-ownership.php
 *
 * The SQL equivalent lives in database/migrations/005_sales_ownership.sql
 * (MySQL) and 005_sales_ownership.sqlite.sql (SQLite). This runner is handy
 * when the host does not offer a SQL console.
 */
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

$db = db();
$driver = $db->driver();

$exists = $driver === 'mysql'
    ? (bool) $db->value(
        'SELECT COUNT(*) FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?',
        ['customers', 'sales_owner_id']
    )
    : (bool) $db->value(
        "SELECT COUNT(*) FROM pragma_table_info('customers') WHERE name = ?",
        ['sales_owner_id']
    );

if ($exists) {
    echo "customers.sales_owner_id already exists — nothing to do.\n";
    exit(0);
}

if ($driver === 'mysql') {
    $db->pdo()->exec('ALTER TABLE customers ADD COLUMN sales_owner_id INT NULL AFTER discount_bp');
    $db->pdo()->exec('ALTER TABLE customers ADD INDEX idx_customers_sales_owner (sales_owner_id)');
} else {
    $db->pdo()->exec('ALTER TABLE customers ADD COLUMN sales_owner_id INTEGER');
    $db->pdo()->exec('CREATE INDEX IF NOT EXISTS idx_customers_sales_owner ON customers (sales_owner_id)');
}

echo "Added customers.sales_owner_id ({$driver}).\n";

#!/usr/bin/env php
<?php
/**
 * bin/convert-sqlite-to-mysql.php
 *
 * Copies the local SQLite store database into a MySQL/MariaDB database
 * (e.g. the one bundled with XAMPP), then verifies row counts.
 *
 * Usage:
 *   php bin/convert-sqlite-to-mysql.php
 *   php bin/convert-sqlite-to-mysql.php --force
 *   php bin/convert-sqlite-to-mysql.php --host=127.0.0.1 --user=root --password= --database=ronak_store
 *
 * Options:
 *   --sqlite=PATH    Source SQLite file (default: config db.path)
 *   --host=HOST      MySQL host (default: 127.0.0.1)
 *   --port=PORT      MySQL port (default: 3306)
 *   --database=NAME  Target database (default: ronak_store)
 *   --user=USER      MySQL user (default: root)
 *   --password=PASS  MySQL password (default: empty)
 *   --force          Overwrite the target even if it already contains data
 */
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

/* -------------------------------------------------------------------------
   Options
   ------------------------------------------------------------------------- */
$options = [];
foreach (array_slice($argv, 1) as $arg) {
    if (!str_starts_with($arg, '--')) {
        continue;
    }
    [$key, $value] = array_pad(explode('=', substr($arg, 2), 2), 2, true);
    $options[$key] = $value;
}

$sqlitePath = (string) ($options['sqlite'] ?? config('db.path'));
$host       = (string) ($options['host'] ?? '127.0.0.1');
$port       = (int) ($options['port'] ?? 3306);
$dbName     = (string) ($options['database'] ?? 'ronak_store');
$user       = (string) ($options['user'] ?? 'root');
$password   = (string) ($options['password'] ?? '');
$force      = isset($options['force']);

if (!is_file($sqlitePath)) {
    fwrite(STDERR, "SQLite file not found: {$sqlitePath}\n");
    exit(1);
}

/* -------------------------------------------------------------------------
   Connections
   ------------------------------------------------------------------------- */
$sqlite = new PDO('sqlite:' . $sqlitePath, null, null, [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

try {
    $server = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    fwrite(STDERR, "Cannot connect to MySQL at {$host}:{$port}: {$e->getMessage()}\n");
    fwrite(STDERR, "Is MySQL running in the XAMPP control panel?\n");
    exit(1);
}

echo "Ronak Fire: SQLite -> MySQL conversion\n";
echo "  source: {$sqlitePath}\n";
echo "  target: mysql://{$user}@{$host}:{$port}/{$dbName}\n\n";

$server->exec(
    "CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
);
$server->exec("USE `{$dbName}`");

/* -------------------------------------------------------------------------
   Helpers
   ------------------------------------------------------------------------- */
function split_sql(string $sql): array
{
    $statements = [];
    $current = '';
    $length = strlen($sql);
    $quote = null;

    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];

        if ($quote !== null) {
            $current .= $char;
            if ($char === '\\' && $quote !== '`') {
                if ($i + 1 < $length) {
                    $current .= $sql[++$i];
                }
                continue;
            }
            if ($char === $quote) {
                if ($i + 1 < $length && $sql[$i + 1] === $quote) {
                    $current .= $sql[++$i];
                    continue;
                }
                $quote = null;
            }
            continue;
        }

        if ($char === "'" || $char === '"' || $char === '`') {
            $quote = $char;
            $current .= $char;
            continue;
        }

        if ($char === '-' && ($sql[$i + 1] ?? '') === '-' && (($sql[$i + 2] ?? ' ') === ' ' || ($sql[$i + 2] ?? '') === "\n")) {
            while ($i < $length && $sql[$i] !== "\n") {
                $i++;
            }
            continue;
        }

        if ($char === '#') {
            while ($i < $length && $sql[$i] !== "\n") {
                $i++;
            }
            continue;
        }

        if ($char === ';') {
            if (trim($current) !== '') {
                $statements[] = trim($current);
            }
            $current = '';
            continue;
        }

        $current .= $char;
    }

    if (trim($current) !== '') {
        $statements[] = trim($current);
    }

    return $statements;
}

function ignore_duplicate(PDOException $e): bool
{
    $code = (int) ($e->errorInfo[1] ?? 0);
    // Duplicate column / table / index / key, or missing object on a re-run.
    return in_array($code, [1022, 1050, 1060, 1061, 1091, 1826], true);
}

function table_rows(PDO $pdo, string $table): int
{
    return (int) $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
}

/* -------------------------------------------------------------------------
   Guard: do not clobber an existing database without --force
   ------------------------------------------------------------------------- */
$existingTables = $server->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
$nonEmpty = [];
foreach ($existingTables as $table) {
    if (table_rows($server, (string) $table) > 0) {
        $nonEmpty[] = $table;
    }
}

if ($nonEmpty && !$force) {
    fwrite(STDERR, "Target database `{$dbName}` already contains data in: " . implode(', ', $nonEmpty) . "\n");
    fwrite(STDERR, "Re-run with --force to replace it.\n");
    exit(1);
}

/* -------------------------------------------------------------------------
   Schema + migrations
   ------------------------------------------------------------------------- */
$schemaFile = BASE_PATH . '/database/schema.mysql.sql';
if (!is_file($schemaFile)) {
    fwrite(STDERR, "Schema file not found: {$schemaFile}\n");
    exit(1);
}

echo "Applying schema.mysql.sql...\n";
foreach (split_sql((string) file_get_contents($schemaFile)) as $statement) {
    $server->exec($statement);
}

$migrationFiles = glob(BASE_PATH . '/database/migrations/*.sql') ?: [];
$migrationFiles = array_values(array_filter(
    $migrationFiles,
    static fn (string $file): bool => !str_ends_with($file, '.sqlite.sql')
));
sort($migrationFiles);

$appliedMigrations = [];
foreach ($migrationFiles as $file) {
    $name = basename($file);
    echo "Applying migration {$name}...\n";
    foreach (split_sql((string) file_get_contents($file)) as $statement) {
        try {
            $server->exec($statement);
        } catch (PDOException $e) {
            if (ignore_duplicate($e)) {
                continue;
            }
            fwrite(STDERR, "Migration {$name} failed: {$e->getMessage()}\n");
            fwrite(STDERR, "Statement: {$statement}\n");
            exit(1);
        }
    }
    $appliedMigrations[] = $name;
}

$server->exec(
    'CREATE TABLE IF NOT EXISTS migrations (' .
    'name VARCHAR(191) PRIMARY KEY, run_at DATETIME NOT NULL' .
    ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
);

/* -------------------------------------------------------------------------
   Copy data (parent tables first)
   ------------------------------------------------------------------------- */
$tables = [
    'categories',
    'products',
    'option_groups',
    'option_values',
    'bundle_items',
    'customers',
    'carts',
    'cart_items',
    'orders',
    'order_items',
    'enquiries',
    'admin_users',
    'customer_addresses',
    'settings',
];

$sourceTables = $sqlite
    ->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'")
    ->fetchAll(PDO::FETCH_COLUMN);
$sourceTables = array_map('strval', $sourceTables);

foreach ($sourceTables as $extra) {
    if (!in_array($extra, $tables, true) && $extra !== 'migrations') {
        $tables[] = $extra;
    }
}

$server->exec('SET FOREIGN_KEY_CHECKS=0');

echo "\nCopying data...\n";
$copied = [];
foreach ($tables as $table) {
    if (!in_array($table, $sourceTables, true)) {
        continue;
    }

    $targetColumns = $server
        ->query("SHOW COLUMNS FROM `{$table}`")
        ->fetchAll(PDO::FETCH_COLUMN);

    $server->exec("DELETE FROM `{$table}`");

    $rows = $sqlite->query("SELECT * FROM \"{$table}\"")->fetchAll();
    if ($rows === []) {
        $copied[$table] = 0;
        continue;
    }

    $sourceColumns = array_keys($rows[0]);
    $columns = array_values(array_intersect($targetColumns, $sourceColumns));
    if ($columns === []) {
        fwrite(STDERR, "No shared columns for {$table}; skipped.\n");
        continue;
    }

    $columnList = implode(', ', array_map(static fn (string $c): string => '`' . $c . '`', $columns));
    $placeholders = implode(', ', array_map(static fn (string $c): string => ':' . $c, $columns));
    $insert = $server->prepare("INSERT INTO `{$table}` ({$columnList}) VALUES ({$placeholders})");

    foreach ($rows as $row) {
        $params = [];
        foreach ($columns as $column) {
            $params[':' . $column] = $row[$column] ?? null;
        }
        $insert->execute($params);
    }

    $copied[$table] = count($rows);

    if (in_array('id', $columns, true)) {
        $max = (int) $server->query("SELECT COALESCE(MAX(id), 0) FROM `{$table}`")->fetchColumn();
        if ($max > 0) {
            $server->exec("ALTER TABLE `{$table}` AUTO_INCREMENT = " . ($max + 1));
        }
    }
}

$server->exec('SET FOREIGN_KEY_CHECKS=1');

$now = date('Y-m-d H:i:s');
$record = $server->prepare('INSERT IGNORE INTO migrations (name, run_at) VALUES (?, ?)');
foreach ($appliedMigrations as $name) {
    $record->execute([$name, $now]);
}

/* -------------------------------------------------------------------------
   Verify
   ------------------------------------------------------------------------- */
echo "\nVerifying row counts...\n";
$failed = 0;
foreach ($copied as $table => $sourceCount) {
    $targetCount = table_rows($server, $table);
    $ok = $sourceCount === $targetCount;
    if (!$ok) {
        $failed++;
    }
    printf("  %-22s %6d -> %6d  %s\n", $table, $sourceCount, $targetCount, $ok ? 'OK' : 'MISMATCH');
}

if ($failed > 0) {
    fwrite(STDERR, "\n{$failed} table(s) did not match. Check the output above.\n");
    exit(1);
}

echo "\nDone. MySQL database `{$dbName}` now matches the SQLite data.\n";
echo "Next: create config.local.php with driver=mysql (see config.example.php).\n";

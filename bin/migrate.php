#!/usr/bin/env php
<?php
/**
 * bin/migrate.php — run pending Phase 2 SQL migrations.
 *
 * Usage:
 *   php bin/migrate.php            # dry-run (shows what would run)
 *   php bin/migrate.php --run      # executes all pending migrations
 *   php bin/migrate.php --sqlite   # force SQLite driver (auto-detected otherwise)
 */
declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}
require BASE_PATH . '/app/bootstrap.php';

$run    = in_array('--run', $argv, true);
$sqlite = in_array('--sqlite', $argv, true) || (config('db.driver', 'sqlite') === 'sqlite');

$ext  = $sqlite ? '.sqlite.sql' : '.sql';
$dir  = BASE_PATH . '/database/migrations';
$glob = glob($dir . '/*' . $ext);
if ($glob === false) {
    fwrite(STDERR, "Migration directory not found: $dir\n");
    exit(1);
}
sort($glob);

// Filter out .sqlite.sql when running MySQL mode
if (!$sqlite) {
    $glob = array_filter($glob, fn($f) => !str_ends_with($f, '.sqlite.sql'));
}

// Ensure migration tracking table exists
if ($run) {
    try {
        if ($sqlite) {
            db()->run("CREATE TABLE IF NOT EXISTS migrations (name TEXT PRIMARY KEY, run_at TEXT NOT NULL)");
        } else {
            db()->run("CREATE TABLE IF NOT EXISTS migrations (name VARCHAR(191) PRIMARY KEY, run_at DATETIME NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    } catch (Throwable $e) {
        fwrite(STDERR, "Cannot create migrations table: " . $e->getMessage() . "\n");
        exit(1);
    }
}

$applied = [];
try {
    $rows = db()->all('SELECT name FROM migrations');
    foreach ($rows as $row) {
        $applied[$row['name']] = true;
    }
} catch (Throwable) {
    // table may not exist yet on very first run
}

$pending = 0;
foreach (array_values($glob) as $file) {
    $name = basename($file);
    $alreadyRun = isset($applied[$name]);

    if (!$run) {
        echo ($alreadyRun ? '  [applied] ' : '  [PENDING] ') . $name . "\n";
        if (!$alreadyRun) {
            $pending++;
        }
        continue;
    }

    if ($alreadyRun) {
        echo "  skip     $name\n";
        continue;
    }

    $sql = file_get_contents($file);
    if ($sql === false) {
        fwrite(STDERR, "Cannot read $file\n");
        exit(1);
    }

    // Split on semicolons (skip comment-only chunks)
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        fn($s) => $s !== '' && !preg_match('/^--/', $s)
    );

    echo "  running  $name … ";
    $ok = true;
    foreach ($statements as $stmt) {
        try {
            db()->run($stmt);
        } catch (Throwable $e) {
            // MySQL ADD COLUMN IF NOT EXISTS doesn't exist in older versions;
            // duplicate-column errors on ALTER are acceptable on re-runs.
            $msg = $e->getMessage();
            $ignorable = str_contains($msg, 'Duplicate column') || str_contains($msg, 'already exists');
            if (!$ignorable) {
                echo "FAILED\n";
                fwrite(STDERR, "  error: $msg\n");
                $ok = false;
                break;
            }
        }
    }

    if ($ok) {
        db()->insert('migrations', ['name' => $name, 'run_at' => date('Y-m-d H:i:s')]);
        echo "done\n";
    } else {
        exit(1);
    }
}

if (!$run) {
    echo "\n$pending pending migration(s). Run with --run to apply.\n";
} else {
    echo "\nAll migrations complete.\n";
}

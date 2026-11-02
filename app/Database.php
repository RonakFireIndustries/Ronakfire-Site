<?php

namespace App;

use PDO;
use PDOException;

final class Database
{
    private PDO $pdo;

    public function __construct(array $config)
    {
        $driver = $config['driver'] ?? 'sqlite';

        if ($driver === 'sqlite') {
            $path = $config['path'];
            $dir = dirname($path);
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            $this->pdo = new PDO('sqlite:' . $path);
            $this->pdo->exec('PRAGMA foreign_keys = ON');
            $this->pdo->exec('PRAGMA busy_timeout = 5000');
        } else {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                $config['host'],
                (int) ($config['port'] ?? 3306),
                $config['name']
            );
            $this->pdo = new PDO($dsn, $config['user'], $config['password']);
        }

        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function driver(): string
    {
        return $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    public function run(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function all(string $sql, array $params = []): array
    {
        return $this->run($sql, $params)->fetchAll();
    }

    public function one(string $sql, array $params = []): ?array
    {
        $row = $this->run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public function value(string $sql, array $params = []): mixed
    {
        $value = $this->run($sql, $params)->fetchColumn();
        return $value === false ? null : $value;
    }

    public function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_map(static fn ($c) => ':' . $c, $columns);
        $quoted = array_map(static fn ($c) => '`' . $c . '`', $columns);
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', $quoted),
            implode(', ', $placeholders)
        );
        $this->run($sql, $data);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $sets = [];
        foreach (array_keys($data) as $column) {
            $sets[] = '`' . $column . '` = :' . $column;
        }
        $sql = sprintf('UPDATE %s SET %s WHERE %s', $table, implode(', ', $sets), $where);
        return $this->run($sql, array_merge($data, $whereParams))->rowCount();
    }

    public function transaction(callable $callback): mixed
    {
        $this->pdo->beginTransaction();
        try {
            $result = $callback($this);
            $this->pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function tableExists(string $table): bool
    {
        try {
            if ($this->driver() === 'sqlite') {
                return (bool) $this->value(
                    "SELECT name FROM sqlite_master WHERE type = 'table' AND name = ?",
                    [$table]
                );
            }
            return (bool) $this->value(
                'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
                [$table]
            );
        } catch (PDOException) {
            return false;
        }
    }
}

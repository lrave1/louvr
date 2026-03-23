<?php
namespace App;

use PDO;
use PDOStatement;

/**
 * PDO database wrapper. Uses prepared statements exclusively.
 * Supports SQLite (dev) and MySQL (production) via config.
 */
class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;

    private function __construct(array $config)
    {
        if ($config['driver'] === 'sqlite') {
            $dir = dirname($config['path']);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $dsn = 'sqlite:' . $config['path'];
            $this->pdo = new PDO($dsn, null, null, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            $this->pdo->exec('PRAGMA journal_mode=WAL');
            $this->pdo->exec('PRAGMA foreign_keys=ON');
        } else {
            $dsn = "mysql:host={$config['host']};dbname={$config['name']};charset=utf8mb4";
            $this->pdo = new PDO($dsn, $config['user'], $config['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }
    }

    public static function getInstance(array $config = []): self
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    /** Run a query with bound parameters, return PDOStatement */
    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /** Fetch all rows */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    /** Fetch single row */
    public function fetch(string $sql, array $params = []): ?array
    {
        $row = $this->query($sql, $params)->fetch();
        return $row ?: null;
    }

    /** Fetch single column value */
    public function fetchColumn(string $sql, array $params = []): mixed
    {
        return $this->query($sql, $params)->fetchColumn();
    }

    /** Insert and return last insert ID */
    public function insert(string $sql, array $params = []): int
    {
        $this->query($sql, $params);
        return (int)$this->pdo->lastInsertId();
    }

    /** Execute and return affected row count */
    public function execute(string $sql, array $params = []): int
    {
        return $this->query($sql, $params)->rowCount();
    }
}

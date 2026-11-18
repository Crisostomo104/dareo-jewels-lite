<?php
declare(strict_types=1);

namespace App;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $pdo = null;

    public static function path(): string
    {
        $configured = Env::get('DB_PATH', 'data/app.sqlite');
        if ($configured === '') {
            $configured = 'data/app.sqlite';
        }
        $configured = str_replace('\\', '/', $configured);
        if (str_starts_with($configured, '/') || preg_match('/^[A-Za-z]:\//D', $configured)) {
            return $configured;
        }
        return dirname(__DIR__) . '/' . ltrim($configured, '/');
    }

    private static function driver(): string
    {
        return strtolower(Env::get('DB_DRIVER', 'sqlite')) === 'mysql' ? 'mysql' : 'sqlite';
    }

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            if (self::driver() === 'mysql') {
                $dsn = sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                    Env::get('DB_HOST', 'localhost'),
                    Env::get('DB_PORT', '3306'),
                    Env::get('DB_NAME', 'dareo_jewels'),
                    Env::get('DB_CHARSET', 'utf8mb4')
                );
                self::$pdo = new PDO($dsn, Env::get('DB_USER'), Env::get('DB_PASS'), $options);
            } else {
                $path = self::path();
                if (!is_dir(dirname($path))) {
                    mkdir(dirname($path), 0775, true);
                }
                self::$pdo = new PDO('sqlite:' . $path, null, null, $options);
                self::$pdo->exec('PRAGMA foreign_keys = ON');
                self::$pdo->exec('PRAGMA journal_mode = WAL');
            }
        }
        return self::$pdo;
    }

    public static function init(): void
    {
        if (self::driver() === 'mysql') {
            // Import database/mysql.sql once during deployment.
            return;
        }
        $pdo = self::pdo();
        $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    name TEXT NOT NULL,
    email TEXT NOT NULL DEFAULT '',
    created_at TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sku TEXT NOT NULL DEFAULT '',
    name TEXT NOT NULL,
    category TEXT NOT NULL DEFAULT 'Other Gold Jewelry',
    karat TEXT NOT NULL DEFAULT '',
    grams TEXT NOT NULL DEFAULT '',
    description TEXT NOT NULL DEFAULT '',
    acquisition_cents INTEGER NOT NULL DEFAULT 0,
    direct_cents INTEGER NOT NULL DEFAULT 0,
    status TEXT NOT NULL DEFAULT 'Available',
    images TEXT NOT NULL DEFAULT '[]',
    acquired_on TEXT NULL,
    acquisition_paid_on TEXT NULL,
    direct_paid_on TEXT NULL,
    created_at TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS sales (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL,
    customer_name TEXT NOT NULL,
    total_cents INTEGER NOT NULL,
    cost_cents INTEGER NOT NULL,
    downpayment_cents INTEGER NOT NULL DEFAULT 0,
    payment_type TEXT NOT NULL DEFAULT 'cash',
    status TEXT NOT NULL DEFAULT 'Confirmed',
    sold_on TEXT NOT NULL,
    installment_count INTEGER NOT NULL DEFAULT 0,
    recognized_at TEXT,
    created_at TEXT NOT NULL,
    FOREIGN KEY (product_id) REFERENCES products(id)
);
CREATE TABLE IF NOT EXISTS payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sale_id INTEGER NOT NULL,
    amount_cents INTEGER NOT NULL,
    paid_on TEXT NOT NULL,
    installment_no INTEGER NULL,
    created_at TEXT NOT NULL,
    FOREIGN KEY (sale_id) REFERENCES sales(id)
);
CREATE TABLE IF NOT EXISTS expenses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    category TEXT NOT NULL,
    description TEXT NOT NULL DEFAULT '',
    amount_cents INTEGER NOT NULL,
    spent_on TEXT NOT NULL,
    created_at TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS settings (
    key TEXT PRIMARY KEY,
    value TEXT NOT NULL DEFAULT ''
);
SQL);
        $paymentColumns = array_column($pdo->query('PRAGMA table_info(payments)')->fetchAll(PDO::FETCH_ASSOC), 'name');
        if (!in_array('installment_no', $paymentColumns, true)) {
            $pdo->exec('ALTER TABLE payments ADD COLUMN installment_no INTEGER NULL');
        }
        $salesColumns = array_column($pdo->query('PRAGMA table_info(sales)')->fetchAll(PDO::FETCH_ASSOC), 'name');
        if (!in_array('installment_count', $salesColumns, true)) {
            $pdo->exec('ALTER TABLE sales ADD COLUMN installment_count INTEGER NOT NULL DEFAULT 0');
        }
        $pdo->exec("UPDATE sales SET installment_count = 2 WHERE payment_type = 'installment' AND installment_count = 0");
        foreach (['users' => ['email'], 'products' => ['acquired_on', 'acquisition_paid_on', 'direct_paid_on']] as $table => $needed) {
            $existing = array_column($pdo->query('PRAGMA table_info(' . $table . ')')->fetchAll(PDO::FETCH_ASSOC), 'name');
            foreach ($needed as $column) {
                if (!in_array($column, $existing, true)) {
                    $pdo->exec('ALTER TABLE ' . $table . ' ADD COLUMN ' . $column . ' TEXT NOT NULL DEFAULT \'\'');
                }
            }
        }
        $defaults = [
            'business_name' => 'DaREO Jewels',
            'messenger' => '',
            'target_profit_percent' => '25',
            'installment_markup_percent' => '15',
            'powered_by' => '1',
        ];
        $insert = $pdo->prepare('INSERT OR IGNORE INTO settings (key, value) VALUES (?, ?)');
        foreach ($defaults as $key => $value) {
            $insert->execute([$key, $value]);
        }
    }

    public static function setting(string $key, string $default = ''): string
    {
        $stmt = self::pdo()->prepare('SELECT value FROM settings WHERE key = ?');
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();
        return $value === false ? $default : (string) $value;
    }

    public static function saveSetting(string $key, string $value): void
    {
        $stmt = self::pdo()->prepare('INSERT INTO settings (key, value) VALUES (?, ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value');
        $stmt->execute([$key, $value]);
    }

    public static function one(string $sql, array $params = []): ?array
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public static function scalar(string $sql, array $params = []): mixed
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    public static function all(string $sql, array $params = []): array
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function execute(string $sql, array $params = []): void
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
    }
}

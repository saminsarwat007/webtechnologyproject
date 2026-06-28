<?php
declare(strict_types=1);

namespace App\Config;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Database — Singleton PDO wrapper.
 *
 * Reads connection info from $_ENV (loaded by phpdotenv in public/index.php)
 * and exposes one shared PDO instance with safe defaults:
 *   - utf8mb4 charset
 *   - ERRMODE_EXCEPTION
 *   - FETCH_ASSOC default fetch mode
 *   - native prepared statements (no emulation)
 */
final class Database
{
    private static ?PDO $instance = null;

    private function __construct() {}
    private function __clone() {}

    public static function getConnection(): PDO
    {
        if (self::$instance instanceof PDO) {
            return self::$instance;
        }

        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $name = $_ENV['DB_NAME'] ?? 'careerbridge';
        $user = $_ENV['DB_USER'] ?? 'root';
        $pass = $_ENV['DB_PASS'] ?? '';

        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            $host,
            $name
        );

        try {
            self::$instance = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException(
                'Database connection failed: ' . $e->getMessage(),
                (int) $e->getCode()
            );
        }

        return self::$instance;
    }
}

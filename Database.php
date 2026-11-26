<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;
use Throwable;

final class Database
{
    private static ?PDO $pdo = null;
    private static array $config = [];

    public static function configure(array $config): void
    {
        self::$config = $config;
        self::$pdo = null;
    }

    public static function connection(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        foreach (['host', 'name', 'user'] as $key) {
            if (!isset(self::$config[$key]) || trim((string) self::$config[$key]) === '') {
                throw new RuntimeException("Database configuration is missing: {$key}");
            }
        }

        $host = (string) self::$config['host'];
        $port = (int) (self::$config['port'] ?? 3306);
        $name = (string) self::$config['name'];
        $charset = (string) (self::$config['charset'] ?? 'utf8mb4');
        $user = (string) self::$config['user'];
        $pass = (string) (self::$config['pass'] ?? '');

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $host,
            $port,
            $name,
            $charset
        );

        self::$pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ]);

        return self::$pdo;
    }

    public static function transaction(callable $callback): mixed
    {
        $pdo = self::connection();
        $pdo->beginTransaction();

        try {
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }
}

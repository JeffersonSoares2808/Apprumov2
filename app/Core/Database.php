<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use Throwable;

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $config = App::config('database');

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        self::$connection = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        $collation = $config['collation'] ?? 'utf8mb4_unicode_ci';
        self::$connection->exec("SET NAMES '{$config['charset']}' COLLATE '{$collation}'");

        return self::$connection;
    }

    public static function select(string $sql, array $params = []): array
    {
        $statement = self::connection()->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public static function selectOne(string $sql, array $params = []): ?array
    {
        $statement = self::connection()->prepare($sql);
        $statement->execute($params);
        $record = $statement->fetch();

        return $record ?: null;
    }

    public static function statement(string $sql, array $params = []): bool
    {
        $statement = self::connection()->prepare($sql);

        return $statement->execute($params);
    }

    public static function lastInsertId(): int
    {
        return (int) self::connection()->lastInsertId();
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
            $pdo->rollBack();
            throw $exception;
        }
    }
}

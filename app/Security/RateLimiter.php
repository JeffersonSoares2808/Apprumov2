<?php

declare(strict_types=1);

namespace App\Security;

final class RateLimiter
{
    public static function attempt(string $key, int $maxAttempts, int $windowSeconds): bool
    {
        $bucket = self::bucket($key);
        $now = time();
        $attempts = array_values(array_filter($bucket['attempts'] ?? [], static fn (int $ts): bool => $ts > ($now - $windowSeconds)));

        if (count($attempts) >= $maxAttempts) {
            self::store($key, ['attempts' => $attempts]);
            return false;
        }

        $attempts[] = $now;
        self::store($key, ['attempts' => $attempts]);

        return true;
    }

    public static function rememberOnce(string $key, int $ttlSeconds): bool
    {
        $bucket = self::bucket($key);
        $now = time();
        $expiresAt = (int) ($bucket['expires_at'] ?? 0);

        if ($expiresAt > $now) {
            return false;
        }

        self::store($key, [
            'attempts' => [],
            'expires_at' => $now + $ttlSeconds,
        ]);

        return true;
    }

    public static function clear(string $key): void
    {
        $file = self::filePath($key);
        if (file_exists($file)) {
            unlink($file);
        }
    }

    private static function bucket(string $key): array
    {
        $file = self::filePath($key);
        if (!file_exists($file)) {
            return ['attempts' => [], 'expires_at' => 0];
        }

        $decoded = json_decode((string) file_get_contents($file), true);

        return is_array($decoded) ? $decoded : ['attempts' => [], 'expires_at' => 0];
    }

    private static function store(string $key, array $payload): void
    {
        file_put_contents(self::filePath($key), json_encode($payload, JSON_THROW_ON_ERROR));
    }

    private static function filePath(string $key): string
    {
        return BASE_PATH . '/storage/rate-limits/' . hash('sha256', $key) . '.json';
    }
}

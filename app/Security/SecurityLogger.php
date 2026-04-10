<?php

declare(strict_types=1);

namespace App\Security;

final class SecurityLogger
{
    public static function warning(string $event, array $context = []): void
    {
        $line = json_encode([
            'ts' => date('c'),
            'event' => $event,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'ua' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'context' => $context,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($line === false) {
            return;
        }

        file_put_contents(BASE_PATH . '/storage/logs/security.log', $line . PHP_EOL, FILE_APPEND);
    }
}

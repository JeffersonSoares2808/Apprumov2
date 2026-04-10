<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class Csrf
{
    public static function token(): string
    {
        if (!isset($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf_token'];
    }

    public static function validate(?string $token): void
    {
        if (!hash_equals((string) ($_SESSION['_csrf_token'] ?? ''), (string) $token)) {
            throw new RuntimeException('Token CSRF inválido.');
        }
    }
}

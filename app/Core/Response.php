<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    public static function redirect(string $path): void
    {
        header('Location: ' . base_url($path));
        exit;
    }

    public static function externalRedirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}

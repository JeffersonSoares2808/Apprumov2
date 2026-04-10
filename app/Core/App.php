<?php

declare(strict_types=1);

namespace App\Core;

final class App
{
    private static array $config = [];

    public static function bootstrap(array $config): void
    {
        self::$config = $config;
    }

    public static function config(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return self::$config;
        }

        $segments = explode('.', $key);
        $value = self::$config;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }
}

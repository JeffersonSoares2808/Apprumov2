<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class View
{
    public static function render(string $view, array $data = [], string $layout = 'app'): void
    {
        $content = self::capture($view, $data);
        $layoutPath = BASE_PATH . '/app/Views/layouts/' . $layout . '.php';

        if (!file_exists($layoutPath)) {
            throw new RuntimeException('Layout não encontrado: ' . $layout);
        }

        extract($data, EXTR_SKIP);
        require $layoutPath;
    }

    public static function partial(string $view, array $data = []): void
    {
        $path = BASE_PATH . '/app/Views/' . $view . '.php';

        if (!file_exists($path)) {
            throw new RuntimeException('Partial não encontrada: ' . $view);
        }

        extract($data, EXTR_SKIP);
        require $path;
    }

    private static function capture(string $view, array $data): string
    {
        $path = BASE_PATH . '/app/Views/' . $view . '.php';

        if (!file_exists($path)) {
            throw new RuntimeException('View não encontrada: ' . $view);
        }

        ob_start();
        extract($data, EXTR_SKIP);
        require $path;

        return (string) ob_get_clean();
    }
}

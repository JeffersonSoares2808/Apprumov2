<?php

declare(strict_types=1);

namespace App\Core;

use Closure;
use RuntimeException;
use Throwable;

final class Router
{
    private array $routes = [];

    public function get(string $path, callable|array $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function add(string $method, string $path, callable|array $handler): void
    {
        $pattern = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $path) ?: $path;

        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => '#^' . rtrim($pattern, '/') . '/?$#',
            'handler' => $handler,
        ];
    }

    public function dispatch(Request $request): void
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method()) {
                continue;
            }

            $normalizedPath = rtrim($request->path(), '/') ?: '/';
            if (!preg_match($route['pattern'], $normalizedPath, $matches)) {
                continue;
            }

            $params = array_filter($matches, static fn (string|int $key): bool => is_string($key), ARRAY_FILTER_USE_KEY);

            try {
                $this->invoke($route['handler'], $request, $params);
            } catch (Throwable $exception) {
                http_response_code(500);
                echo app_config('app.debug')
                    ? '<pre>' . e($exception->getMessage() . PHP_EOL . $exception->getTraceAsString()) . '</pre>'
                    : 'Ocorreu um erro inesperado.';
            }

            return;
        }

        http_response_code(404);
        echo 'Página não encontrada.';
    }

    private function invoke(callable|array $handler, Request $request, array $params): void
    {
        if (is_array($handler) && isset($handler[0], $handler[1])) {
            $controller = new $handler[0]();
            $method = $handler[1];

            if (!method_exists($controller, $method)) {
                throw new RuntimeException('Método de controller não encontrado.');
            }

            $controller->{$method}($request, ...array_values($params));
            return;
        }

        if ($handler instanceof Closure || is_callable($handler)) {
            $handler($request, ...array_values($params));
            return;
        }

        throw new RuntimeException('Rota inválida.');
    }
}

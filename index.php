<?php

declare(strict_types=1);

use App\Core\Request;
use App\Core\Router;

if (PHP_VERSION_ID < 80000) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Apprumo requer PHP 8.0 ou superior. Versao detectada: ' . PHP_VERSION;
    exit;
}

try {
    // Evita "headers already sent" por espaço/BOM acidental em algum include antes das sessões/headers.
    if (ob_get_level() === 0) {
        ob_start();
    }

    require __DIR__ . '/app/bootstrap.php';
    $router = new Router();
    require __DIR__ . '/routes/web.php';
    $router->dispatch(Request::capture());
} catch (Throwable $e) {
    $logLine = sprintf(
        '[Apprumo] %s in %s:%d',
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    );
    $full = $logLine . "\n" . $e->getTraceAsString();

    error_log($full);

    $logDir = __DIR__ . '/storage/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0775, true);
    }
    $logFile = $logDir . '/last-error.log';
    @file_put_contents($logFile, date('c') . "\n" . $full . "\n\n", FILE_APPEND | LOCK_EX);

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    if (!headers_sent()) {
        http_response_code(500);
    }

    $debug = filter_var(getenv('APP_DEBUG') ?: ($_ENV['APP_DEBUG'] ?? 'false'), FILTER_VALIDATE_BOOLEAN);
    if ($debug) {
        if (!headers_sent()) {
            header('Content-Type: text/plain; charset=utf-8');
        }
        echo $full;
        exit;
    }

    if (!headers_sent()) {
        header('Content-Type: text/html; charset=utf-8');
    }
    echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="utf-8"><title>Erro</title></head><body>';
    echo '<p>Não foi possível carregar o sistema.</p>';
    echo '<p><strong>Para ver o erro:</strong> no arquivo <code>.env</code> do servidor, coloque <code>APP_DEBUG=true</code>, salve e recarregue esta página (depois volte para <code>false</code>).</p>';
    echo '<p>Ou abra no gerenciador de arquivos da hospedagem: <code>storage/logs/last-error.log</code> (últimas linhas mostram a causa).</p>';
    echo '</body></html>';
    exit;
}

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

    $errorCode = 500;
    $errorTitle = 'Erro inesperado';
    $errorMessage = 'Não foi possível carregar o sistema.';

    // Provide more specific user-friendly messages based on error type
    $msg = $e->getMessage();
    if (stripos($msg, 'SQLSTATE') !== false || stripos($msg, 'database') !== false || stripos($msg, 'mysql') !== false) {
        $errorTitle = 'Erro de conexão com o banco de dados';
        $errorMessage = 'O sistema não conseguiu conectar ao banco de dados. Verifique as configurações de banco no arquivo <code>.env</code>.';
    } elseif (stripos($msg, 'sessao') !== false || stripos($msg, 'session') !== false) {
        $errorTitle = 'Erro de sessão';
        $errorMessage = 'Não foi possível iniciar a sessão. Verifique se a pasta <code>storage/sessions/</code> existe e tem permissão de escrita (chmod 775).';
    } elseif (stripos($msg, 'permission') !== false || stripos($msg, 'permissao') !== false) {
        $errorTitle = 'Erro de permissão';
        $errorMessage = 'O sistema não tem permissão para acessar alguns arquivos. Verifique as permissões das pastas <code>storage/</code> e <code>uploads/</code>.';
    } elseif ($e->getCode() === 403 || stripos($msg, 'forbidden') !== false) {
        $errorCode = 403;
        $errorTitle = 'Acesso negado';
        $errorMessage = 'Você não tem permissão para acessar esta página. Se está usando o atalho da tela inicial, tente remover e adicionar novamente.';
    }

    if (!headers_sent()) {
        http_response_code($errorCode);
    }

    echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>' . $errorTitle . ' | Apprumo</title>';
    echo '<style>body{font-family:"Plus Jakarta Sans",system-ui,sans-serif;background:#f8fafc;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:16px;}';
    echo '.error-card{background:#fff;border-radius:18px;box-shadow:0 8px 30px rgba(14,43,71,.08);max-width:480px;width:100%;padding:32px;text-align:center;}';
    echo '.error-card h1{color:#1e293b;font-size:1.4rem;margin:0 0 12px;}.error-card p{color:#64748b;font-size:0.95rem;line-height:1.6;margin:8px 0;}';
    echo '.error-card code{background:#f1f5f9;padding:2px 6px;border-radius:4px;font-size:0.85rem;}';
    echo '.error-card .btn{display:inline-block;background:#1AB2C7;color:#fff;padding:10px 24px;border-radius:10px;text-decoration:none;font-weight:600;margin-top:16px;}';
    echo '.error-card .debug-hint{margin-top:20px;padding-top:16px;border-top:1px solid #eee;font-size:0.82rem;color:#94a3b8;}</style></head><body>';
    echo '<div class="error-card">';
    echo '<h1>⚠️ ' . $errorTitle . '</h1>';
    echo '<p>' . $errorMessage . '</p>';
    echo '<a class="btn" href="' . ($_SERVER['REQUEST_URI'] ?? '/') . '">Tentar novamente</a>';
    echo '<div class="debug-hint">';
    echo '<p>Para ver detalhes do erro: no arquivo <code>.env</code>, coloque <code>APP_DEBUG=true</code> e recarregue.</p>';
    echo '<p>Ou verifique: <code>storage/logs/last-error.log</code></p>';
    echo '</div></div></body></html>';
    exit;
}

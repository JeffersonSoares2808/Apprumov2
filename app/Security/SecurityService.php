<?php

declare(strict_types=1);

namespace App\Security;

use App\Core\Response;

final class SecurityService
{
    public static function bootstrap(): void
    {
        self::configureRuntime();
        self::ensureStorageDirectories();
        self::startSecureSession();
        self::enforceTransportSecurity();
        self::sendHeaders();
    }

    public static function isProduction(): bool
    {
        return app_config('app.env') === 'production';
    }

    public static function isHttps(): bool
    {
        if (PHP_SAPI === 'cli') {
            return false;
        }

        if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            return true;
        }

        if ((int) ($_SERVER['SERVER_PORT'] ?? 80) === 443) {
            return true;
        }

        if (app_config('app.trust_proxy') && !empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            return strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https';
        }

        return false;
    }

    private static function configureRuntime(): void
    {
        @ini_set('session.use_strict_mode', '1');
        @ini_set('session.use_only_cookies', '1');
        @ini_set('session.cookie_httponly', '1');
        @ini_set('session.sid_length', '48');
        @ini_set('session.sid_bits_per_character', '6');
        @ini_set('expose_php', '0');
    }

    private static function startSecureSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $sessionPath = self::resolveWritableSessionPath();
        if ($sessionPath !== '') {
            @ini_set('session.save_path', $sessionPath);
        }

        session_name('apprumo_session');
        $cookiePath = self::sessionCookiePath();
        $secure = (bool) app_config('app.session_cookie_secure', false) || self::isHttps();
        $sameSite = (string) app_config('app.session_same_site', 'Lax');

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => $cookiePath,
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => $sameSite,
        ]);

        try {
            session_start();
        } catch (\Throwable $e) {
            throw new \RuntimeException('Falha ao iniciar sessao PHP: ' . $e->getMessage(), 0, $e);
        }
    }

    private static function enforceTransportSecurity(): void
    {
        if (PHP_SAPI === 'cli') {
            return;
        }

        if (!app_config('app.force_https', false)) {
            return;
        }

        if (self::isHttps()) {
            return;
        }

        $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        Response::externalRedirect('https://' . $host . $uri);
    }

    private static function sendHeaders(): void
    {
        if (headers_sent()) {
            return;
        }

        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), geolocation=(), microphone=()');
        header('Cross-Origin-Opener-Policy: same-origin');
        header('Cross-Origin-Resource-Policy: same-origin');
        header('Cache-Control: private, no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');

        if (self::isHttps()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }

        header("Content-Security-Policy: default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'; img-src 'self' data: https:; script-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' data: https://fonts.gstatic.com; connect-src 'self';");
    }

    private static function ensureStorageDirectories(): void
    {
        foreach ([
            BASE_PATH . '/storage',
            BASE_PATH . '/storage/rate-limits',
            BASE_PATH . '/storage/logs',
            BASE_PATH . '/storage/sessions',
        ] as $path) {
            if (is_dir($path)) {
                continue;
            }
            @mkdir($path, 0775, true);
        }
    }

    private static function resolveWritableSessionPath(): string
    {
        $preferred = BASE_PATH . '/storage/sessions';
        if (is_dir($preferred) && is_writable($preferred)) {
            return $preferred;
        }

        $fallback = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'apprumo_sessions';
        if (!is_dir($fallback)) {
            @mkdir($fallback, 0775, true);
        }

        if (is_dir($fallback) && is_writable($fallback)) {
            return $fallback;
        }

        return '';
    }

    private static function sessionCookiePath(): string
    {
        $base = (string) app_config('app.base_url', '');
        if ($base === '') {
            return '/';
        }

        $path = parse_url($base, PHP_URL_PATH);
        if (!is_string($path) || $path === '' || $path === '/') {
            return '/';
        }

        return rtrim($path, '/') . '/';
    }
}


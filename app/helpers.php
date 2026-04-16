<?php

declare(strict_types=1);

use App\Core\App;
use App\Core\Csrf;
use App\Core\Session;
use App\Core\View;
use App\Services\AuthService;

function app_config(?string $key = null, mixed $default = null): mixed
{
    return App::config($key, $default);
}

function inferred_base_url(): string
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $configured = trim((string) app_config('app.base_url', ''));
    if ($configured !== '') {
        $cached = rtrim($configured, '/');
        return $cached;
    }

    $trustProxy = (bool) app_config('app.trust_proxy', false);
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443)
        || ($trustProxy && (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'));

    $scheme = $https ? 'https' : 'http';
    $host = $trustProxy && !empty($_SERVER['HTTP_X_FORWARDED_HOST'])
        ? (string) $_SERVER['HTTP_X_FORWARDED_HOST']
        : (string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost');

    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $dir = trim(dirname($scriptName), '/.');
    $path = $dir !== '' ? '/' . $dir : '';

    $cached = $scheme . '://' . $host . $path;

    return $cached;
}

function base_url(string $path = ''): string
{
    $base = inferred_base_url();
    $path = ltrim($path, '/');

    if ($path === '') {
        return $base !== '' ? $base . '/' : '/';
    }

    if ($base === '') {
        return '/' . $path;
    }

    return rtrim($base, '/') . '/' . $path;
}

function app_url_path_prefix(): string
{
    $base = inferred_base_url();
    if ($base === '') {
        return '';
    }

    $path = parse_url($base, PHP_URL_PATH);
    if (!is_string($path) || $path === '/' || $path === '') {
        return '';
    }

    return rtrim($path, '/');
}

function normalize_app_path(?string $path): string
{
    $path = $path ?: '/';
    $path = parse_url($path, PHP_URL_PATH) ?: '/';
    $prefix = app_url_path_prefix();

    if ($prefix !== '' && ($path === $prefix || str_starts_with($path, $prefix . '/'))) {
        $path = substr($path, strlen($prefix)) ?: '/';
    }

    return rtrim($path, '/') ?: '/';
}

function asset(string $path): string
{
    return base_url($path);
}

function brand_logo_url(): string
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $png = BASE_PATH . '/assets/img/logo.png';
    $cached = asset(is_file($png) ? 'assets/img/logo.png' : 'assets/img/logo.svg');

    return $cached;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function money(float|int|string|null $value): string
{
    return 'R$ ' . number_format((float) $value, 2, ',', '.');
}

function old(string $key, mixed $default = ''): mixed
{
    return Session::old($key, $default);
}

function flash(string $key, mixed $default = null): mixed
{
    return Session::pullFlash($key, $default);
}

function csrf_token(): string
{
    return Csrf::token();
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function current_user(): ?array
{
    return AuthService::user();
}

function current_vendor(): ?array
{
    return AuthService::vendor();
}

function is_current_path(string $path): bool
{
    return normalize_app_path($_SERVER['REQUEST_URI'] ?? '/') === normalize_app_path($path);
}

function path_starts_with(string $path): bool
{
    return str_starts_with(normalize_app_path($_SERVER['REQUEST_URI'] ?? '/'), normalize_app_path($path));
}

function status_label(string $status): string
{
    return match ($status) {
        'active' => 'Ativo',
        'pending' => 'Pendente',
        'suspended' => 'Suspenso',
        'expired' => 'Expirado',
        'confirmed' => 'Confirmado',
        'completed' => 'Concluído',
        'cancelled' => 'Cancelado',
        'no_show' => 'No-show',
        'paid' => 'Pago',
        'open' => 'Em aberto',
        default => ucfirst(str_replace('_', ' ', $status)),
    };
}

function status_class(string $status): string
{
    return match ($status) {
        'active', 'confirmed', 'completed', 'paid' => 'is-success',
        'pending', 'open' => 'is-warning',
        'suspended', 'expired', 'cancelled', 'no_show' => 'is-danger',
        default => 'is-neutral',
    };
}

function lower_text(string $value): string
{
    return function_exists('mb_strtolower')
        ? mb_strtolower($value, 'UTF-8')
        : strtolower($value);
}

function text_first_char(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    if (function_exists('mb_substr')) {
        return (string) mb_substr($value, 0, 1, 'UTF-8');
    }

    return substr($value, 0, 1) ?: '';
}

function upper_text(string $value): string
{
    return function_exists('mb_strtoupper')
        ? mb_strtoupper($value, 'UTF-8')
        : strtoupper($value);
}

function slugify(string $value): string
{
    $value = trim(lower_text($value));
    $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?: '';
    $value = trim($value, '-');

    return $value !== '' ? $value : 'perfil';
}

function format_date(?string $date, string $format = 'd/m/Y'): string
{
    if (!$date) {
        return '-';
    }

    return date($format, strtotime($date));
}

function format_time(?string $time, string $format = 'H:i'): string
{
    if (!$time) {
        return '-';
    }

    return date($format, strtotime($time));
}

function format_datetime(?string $dateTime, string $format = 'd/m/Y H:i'): string
{
    if (!$dateTime) {
        return '-';
    }

    return date($format, strtotime($dateTime));
}

function support_whatsapp_url(string $message = ''): string
{
    $phone = preg_replace('/\D+/', '', app_config('app.support_whatsapp', '')) ?: '';

    return 'https://api.whatsapp.com/send?phone=' . $phone . ($message !== '' ? '&text=' . rawurlencode($message) : '');
}

function days_until(?string $date): ?int
{
    if (!$date) {
        return null;
    }

    $target = strtotime($date . ' 23:59:59');
    if ($target === false) {
        return null;
    }

    $today = strtotime(date('Y-m-d') . ' 00:00:00');
    if ($today === false) {
        return null;
    }

    return (int) ceil(($target - $today) / 86400);
}

function whatsapp_link(string $phone, string $message = ''): string
{
    $sanitized = preg_replace('/\D+/', '', $phone) ?: '';

    // Ensure Brazilian numbers have country code prefix
    if ($sanitized !== '' && !str_starts_with($sanitized, '55') && strlen($sanitized) <= 11) {
        $sanitized = '55' . $sanitized;
    }

    $query = $message !== '' ? '?text=' . rawurlencode($message) : '';

    return 'https://api.whatsapp.com/send?phone=' . $sanitized . ($message !== '' ? '&text=' . rawurlencode($message) : '');
}

function page_back_url(string $fallback = '/vendor/dashboard'): string
{
    $current = normalize_app_path($_SERVER['REQUEST_URI'] ?? '/');

    $map = [
        '/vendor/dashboard' => '/vendor/dashboard',
        '/vendor/agenda' => '/vendor/dashboard',
        '/vendor/services' => '/vendor/dashboard',
        '/vendor/products' => '/vendor/menu',
        '/vendor/finance' => '/vendor/dashboard',
        '/vendor/reports' => '/vendor/menu',
        '/vendor/clients' => '/vendor/menu',
        '/vendor/settings' => '/vendor/menu',
        '/vendor/professionals' => '/vendor/menu',
        '/vendor/advanced-agenda' => '/vendor/menu',
        '/vendor/menu' => '/vendor/dashboard',
    ];

    foreach ($map as $prefix => $target) {
        if ($current === normalize_app_path($prefix) || str_starts_with($current, normalize_app_path($prefix) . '/')) {
            return base_url($target);
        }
    }

    return base_url($fallback);
}

function vendor_initials(?string $name): string
{
    $name = trim((string) $name);
    if ($name === '') {
        return 'AP';
    }

    $parts = preg_split('/\s+/', $name) ?: [];
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $initials .= upper_text(text_first_char($part));
    }

    return $initials !== '' ? $initials : 'AP';
}

function partial(string $view, array $data = []): void
{
    View::partial($view, $data);
}

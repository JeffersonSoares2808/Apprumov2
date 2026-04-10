<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Response;
use App\Core\Session;

final class AuthService
{
    private const SESSION_KEY = 'auth_user_id';
    private const FINGERPRINT_KEY = 'auth_fingerprint';
    private const LAST_ACTIVITY_KEY = 'auth_last_activity';
    private const ACTIVE_VENDOR_KEY = 'active_vendor_id';

    public static function loginByUserId(int $userId): void
    {
        session_regenerate_id(true);
        Session::put(self::SESSION_KEY, $userId);
        Session::put(self::FINGERPRINT_KEY, self::fingerprint());
        Session::put(self::LAST_ACTIVITY_KEY, time());
        // Active vendor é resolvido lazy em resolveLanding/requireActiveVendor.
    }

    public static function logout(): void
    {
        Session::flush();

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'] ?: '/',
                'domain' => $params['domain'] ?: '',
                'secure' => (bool) $params['secure'],
                'httponly' => (bool) $params['httponly'],
                'samesite' => $params['samesite'] ?? app_config('app.session_same_site', 'Lax'),
            ]);
        }

        session_regenerate_id(true);
    }

    public static function user(): ?array
    {
        if (!self::assertSessionIntegrity()) {
            return null;
        }

        $userId = (int) Session::get(self::SESSION_KEY, 0);
        if ($userId <= 0) {
            return null;
        }

        return Database::selectOne('SELECT * FROM platform_users WHERE id = :id LIMIT 1', [
            'id' => $userId,
        ]);
    }

    public static function vendor(): ?array
    {
        $user = self::user();
        if (!$user) {
            return null;
        }

        $activeVendorId = (int) Session::get(self::ACTIVE_VENDOR_KEY, 0);
        if ($activeVendorId > 0 && VendorService::userHasAccess((int) $user['id'], $activeVendorId)) {
            return VendorService::findById($activeVendorId);
        }

        $vendor = VendorService::findByUserId((int) $user['id']);
        if ($vendor) {
            self::setActiveVendorId((int) $vendor['id']);
        }

        return $vendor;
    }

    public static function setActiveVendorId(int $vendorId): void
    {
        Session::put(self::ACTIVE_VENDOR_KEY, $vendorId);
    }

    public static function clearActiveVendor(): void
    {
        Session::forget(self::ACTIVE_VENDOR_KEY);
    }

    public static function requireAuthenticated(): array
    {
        $user = self::user();
        if (!$user) {
            Response::redirect('/login');
        }

        return $user;
    }

    public static function requireAdmin(): array
    {
        $user = self::requireAuthenticated();

        if (($user['role'] ?? 'vendor') !== 'admin') {
            Response::redirect('/');
        }

        return $user;
    }

    public static function requireActiveVendor(): array
    {
        $user = self::requireAuthenticated();
        if (($user['role'] ?? 'vendor') === 'admin') {
            Response::redirect('/admin');
        }

        $vendors = VendorService::listForUser((int) $user['id']);
        if (count($vendors) === 0) {
            Response::redirect('/onboarding');
        }

        $activeVendorId = (int) Session::get(self::ACTIVE_VENDOR_KEY, 0);
        if ($activeVendorId <= 0) {
            if (count($vendors) > 1) {
                Response::redirect('/select-vendor');
            }
            $activeVendorId = (int) ($vendors[0]['id'] ?? 0);
            if ($activeVendorId > 0) {
                self::setActiveVendorId($activeVendorId);
            }
        }

        if ($activeVendorId > 0 && !VendorService::userHasAccess((int) $user['id'], $activeVendorId)) {
            self::clearActiveVendor();
            Response::redirect('/select-vendor');
        }

        $vendor = $activeVendorId > 0 ? VendorService::findById($activeVendorId) : null;
        if (!$vendor) {
            self::clearActiveVendor();
            Response::redirect('/select-vendor');
        }

        $status = VendorService::effectiveStatus($vendor);

        if ($status === 'pending') {
            Response::redirect('/pending');
        }

        if ($status === 'suspended') {
            Response::redirect('/suspended');
        }

        if ($status === 'expired') {
            Response::redirect('/plan-expired');
        }

        return $vendor;
    }

    public static function resolveLanding(): string
    {
        $user = self::user();

        if (!$user) {
            return '/login';
        }

        if (($user['role'] ?? 'vendor') === 'admin') {
            return '/admin';
        }

        $vendors = VendorService::listForUser((int) $user['id']);
        if (count($vendors) === 0) {
            return '/onboarding';
        }

        $activeVendorId = (int) Session::get(self::ACTIVE_VENDOR_KEY, 0);
        if ($activeVendorId <= 0) {
            if (count($vendors) > 1) {
                return '/select-vendor';
            }
            $activeVendorId = (int) ($vendors[0]['id'] ?? 0);
            if ($activeVendorId > 0) {
                self::setActiveVendorId($activeVendorId);
            }
        }

        $vendor = $activeVendorId > 0 ? VendorService::findById($activeVendorId) : null;
        if (!$vendor) {
            self::clearActiveVendor();
            return '/select-vendor';
        }

        return match (VendorService::effectiveStatus($vendor)) {
            'pending' => '/pending',
            'suspended' => '/suspended',
            'expired' => '/plan-expired',
            default => '/vendor/dashboard',
        };
    }

    public static function upsertExternalUser(array $payload): array
    {
        $email = trim((string) ($payload['email'] ?? ''));
        $externalId = trim((string) ($payload['external_id'] ?? ''));
        $fullName = trim((string) ($payload['full_name'] ?? ''));
        $requestedRole = trim((string) ($payload['role'] ?? 'vendor'));

        $user = null;
        if ($externalId !== '') {
            $user = Database::selectOne('SELECT * FROM platform_users WHERE external_id = :external_id LIMIT 1', [
                'external_id' => $externalId,
            ]);
        }

        if (!$user && $email !== '') {
            $user = Database::selectOne('SELECT * FROM platform_users WHERE email = :email LIMIT 1', [
                'email' => $email,
            ]);
        }

        if ($user) {
            $nextRole = self::resolveAllowedRole($email, $requestedRole, (string) $user['role']);
            Database::statement(
                'UPDATE platform_users
                 SET external_id = :external_id, email = :email, full_name = :full_name, role = :role, updated_at = NOW()
                 WHERE id = :id',
                [
                    'external_id' => $externalId !== '' ? $externalId : $user['external_id'],
                    'email' => $email !== '' ? $email : $user['email'],
                    'full_name' => $fullName !== '' ? $fullName : $user['full_name'],
                    'role' => $nextRole,
                    'id' => $user['id'],
                ]
            );

            return Database::selectOne('SELECT * FROM platform_users WHERE id = :id LIMIT 1', ['id' => $user['id']]) ?? $user;
        }

        $role = self::resolveAllowedRole($email, $requestedRole);
        Database::statement(
            'INSERT INTO platform_users (external_id, email, full_name, role, created_at, updated_at)
             VALUES (:external_id, :email, :full_name, :role, NOW(), NOW())',
            [
                'external_id' => $externalId !== '' ? $externalId : null,
                'email' => $email,
                'full_name' => $fullName,
                'role' => $role,
            ]
        );

        $id = Database::lastInsertId();

        return Database::selectOne('SELECT * FROM platform_users WHERE id = :id LIMIT 1', ['id' => $id]) ?? [];
    }

    private static function assertSessionIntegrity(): bool
    {
        $userId = (int) Session::get(self::SESSION_KEY, 0);
        if ($userId <= 0) {
            return true;
        }

        $storedFingerprint = (string) Session::get(self::FINGERPRINT_KEY, '');
        if ($storedFingerprint === '' || !hash_equals($storedFingerprint, self::fingerprint())) {
            self::logout();
            return false;
        }

        $lastActivity = (int) Session::get(self::LAST_ACTIVITY_KEY, 0);
        $idleTimeout = (int) app_config('app.session_idle_timeout', 7200);

        if ($lastActivity > 0 && (time() - $lastActivity) > $idleTimeout) {
            self::logout();
            return false;
        }

        Session::put(self::LAST_ACTIVITY_KEY, time());

        return true;
    }

    private static function fingerprint(): string
    {
        return hash('sha256', (string) ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'));
    }

    private static function resolveAllowedRole(string $email, string $requestedRole, string $currentRole = 'vendor'): string
    {
        if (app_config('app.env') !== 'production' && $requestedRole === 'admin') {
            return 'admin';
        }

        $allowedAdminEmails = array_map('strtolower', (array) app_config('app.admin_emails', []));
        $normalizedEmail = strtolower($email);

        if ($currentRole === 'admin') {
            return 'admin';
        }

        if ($requestedRole === 'admin' && in_array($normalizedEmail, $allowedAdminEmails, true)) {
            return 'admin';
        }

        return 'vendor';
    }

    public static function registerWithPassword(string $email, string $fullName, string $password): array
    {
        $email = trim($email);
        $fullName = trim($fullName);
        $password = (string) $password;

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Informe um e-mail válido.');
        }

        if ($fullName === '') {
            throw new \RuntimeException('Informe seu nome.');
        }

        if (mb_strlen($password) < 8) {
            throw new \RuntimeException('Sua senha deve ter pelo menos 8 caracteres.');
        }

        $existing = Database::selectOne('SELECT * FROM platform_users WHERE email = :email LIMIT 1', ['email' => $email]);
        if ($existing) {
            throw new \RuntimeException('Este e-mail já está cadastrado. Faça login.');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        if (!$hash) {
            throw new \RuntimeException('Não foi possível gerar a senha. Tente novamente.');
        }

        Database::statement(
            'INSERT INTO platform_users (external_id, google_sub, email, full_name, password_hash, role, created_at, updated_at)
             VALUES (NULL, NULL, :email, :full_name, :password_hash, "vendor", NOW(), NOW())',
            [
                'email' => $email,
                'full_name' => $fullName,
                'password_hash' => $hash,
            ]
        );

        $id = Database::lastInsertId();

        return Database::selectOne('SELECT * FROM platform_users WHERE id = :id LIMIT 1', ['id' => $id]) ?? [];
    }

    public static function attemptPasswordLogin(string $email, string $password): array
    {
        $email = trim($email);
        $password = (string) $password;

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Informe um e-mail válido.');
        }

        $user = Database::selectOne('SELECT * FROM platform_users WHERE email = :email LIMIT 1', ['email' => $email]);
        if (!$user) {
            throw new \RuntimeException('E-mail ou senha inválidos.');
        }

        $hash = (string) ($user['password_hash'] ?? '');
        if ($hash === '' || !password_verify($password, $hash)) {
            throw new \RuntimeException('E-mail ou senha inválidos.');
        }

        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            $nextHash = password_hash($password, PASSWORD_DEFAULT);
            if ($nextHash) {
                Database::statement(
                    'UPDATE platform_users SET password_hash = :hash, updated_at = NOW() WHERE id = :id',
                    ['hash' => $nextHash, 'id' => $user['id']]
                );
            }
        }

        return $user;
    }
}

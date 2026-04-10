<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Security\RateLimiter;
use App\Security\SecurityLogger;
use App\Services\AuthService;
use App\Services\VendorService;
use RuntimeException;

final class AuthController extends Controller
{
    public function home(Request $request): void
    {
        $this->redirect(AuthService::resolveLanding());
    }

    public function login(Request $request): void
    {
        if (AuthService::user()) {
            $this->redirect(AuthService::resolveLanding());
        }

        $this->render('auth/login', [
            'title' => 'Entrar',
            'show_demo_links' => app_config('app.env') !== 'production',
        ], 'auth');
    }

    public function passwordLogin(Request $request): void
    {
        $this->validateCsrf($request);

        if (!RateLimiter::attempt('auth-password:' . $request->ip(), 15, 300)) {
            SecurityLogger::warning('auth_password_rate_limited');
            $this->flashError('Muitas tentativas. Aguarde alguns minutos.');
            $this->redirect('/login');
        }

        try {
            $email = trim((string) $request->input('email', ''));
            $password = (string) $request->input('password', '');

            $user = AuthService::attemptPasswordLogin($email, $password);
            AuthService::loginByUserId((int) $user['id']);
            Session::clearOld();
            $this->redirect(AuthService::resolveLanding());
        } catch (RuntimeException $exception) {
            SecurityLogger::warning('auth_password_failed', ['message' => $exception->getMessage()]);
            $safeInput = $request->input();
            unset($safeInput['password'], $safeInput['_token']);
            Session::rememberInput($safeInput);
            $this->flashError($exception->getMessage());
            $this->redirect('/login');
        }
    }

    public function register(Request $request): void
    {
        if (AuthService::user()) {
            $this->redirect(AuthService::resolveLanding());
        }

        $this->render('auth/register', [
            'title' => 'Criar conta',
        ], 'auth');
    }

    public function storeRegister(Request $request): void
    {
        $this->validateCsrf($request);

        if (!RateLimiter::attempt('auth-register:' . $request->ip(), 8, 600)) {
            SecurityLogger::warning('auth_register_rate_limited');
            $this->flashError('Muitas tentativas. Aguarde alguns minutos.');
            $this->redirect('/register');
        }

        try {
            $email = trim((string) $request->input('email', ''));
            $fullName = trim((string) $request->input('full_name', ''));
            $password = (string) $request->input('password', '');
            $passwordConfirm = (string) $request->input('password_confirm', '');

            if ($password !== $passwordConfirm) {
                throw new RuntimeException('As senhas não conferem.');
            }

            $user = AuthService::registerWithPassword($email, $fullName, $password);
            AuthService::loginByUserId((int) $user['id']);
            Session::clearOld();
            $this->redirect('/onboarding');
        } catch (RuntimeException $exception) {
            SecurityLogger::warning('auth_register_failed', ['message' => $exception->getMessage()]);
            $safeInput = $request->input();
            unset($safeInput['password'], $safeInput['password_confirm'], $safeInput['_token']);
            Session::rememberInput($safeInput);
            $this->flashError($exception->getMessage());
            $this->redirect('/register');
        }
    }

    public function simpleLogin(Request $request): void
    {
        $this->validateCsrf($request);

        if (app_config('app.env') === 'production') {
            $this->flashError('Use o login com senha.');
            $this->redirect('/login');
        }

        if (!RateLimiter::attempt('auth-simple:' . $request->ip(), 25, 300)) {
            SecurityLogger::warning('auth_simple_rate_limited');
            $this->flashError('Muitas tentativas. Aguarde alguns minutos.');
            $this->redirect('/login');
        }

        try {
            $email = trim((string) $request->input('email', ''));
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Informe um e-mail válido.');
            }

            $fullName = trim((string) $request->input('full_name', ''));
            if ($fullName === '') {
                $fullName = explode('@', $email)[0] ?: 'Usuario';
            }

            $externalId = trim((string) $request->input('external_id', ''));
            if ($externalId === '') {
                $externalId = 'simple-' . md5(strtolower($email));
            }

            $user = AuthService::upsertExternalUser([
                'external_id' => $externalId,
                'email' => $email,
                'full_name' => $fullName,
                'role' => trim((string) $request->input('role', 'vendor')),
            ]);

            AuthService::loginByUserId((int) $user['id']);
            Session::clearOld();
            $this->redirect(AuthService::resolveLanding());
        } catch (RuntimeException $exception) {
            SecurityLogger::warning('auth_simple_failed', ['message' => $exception->getMessage()]);
            $safeInput = $request->input();
            unset($safeInput['_token']);
            Session::rememberInput($safeInput);
            $this->flashError($exception->getMessage());
            $this->redirect('/login');
        }
    }

    public function logout(Request $request): void
    {
        $this->validateCsrf($request);
        AuthService::logout();
        $this->redirect('/login');
    }

    public function selectVendor(Request $request): void
    {
        $user = AuthService::requireAuthenticated();
        if (($user['role'] ?? 'vendor') === 'admin') {
            $this->redirect('/admin');
        }

        $vendors = VendorService::listForUser((int) $user['id']);
        if (count($vendors) === 0) {
            $this->redirect('/onboarding');
        }

        if (count($vendors) === 1) {
            AuthService::setActiveVendorId((int) $vendors[0]['id']);
            $this->redirect(AuthService::resolveLanding());
        }

        $this->render('auth/select-vendor', [
            'title' => 'Selecionar negócio',
            'vendors' => $vendors,
        ], 'auth');
    }

    public function setVendor(Request $request): void
    {
        $this->validateCsrf($request);
        $user = AuthService::requireAuthenticated();
        if (($user['role'] ?? 'vendor') === 'admin') {
            $this->redirect('/admin');
        }

        $vendorId = (int) $request->input('vendor_id', 0);
        if ($vendorId <= 0 || !VendorService::userHasAccess((int) $user['id'], $vendorId)) {
            $this->flashError('Selecione um negócio válido.');
            $this->redirect('/select-vendor');
        }

        AuthService::setActiveVendorId($vendorId);
        $this->redirect(AuthService::resolveLanding());
    }

    public function devLogin(Request $request): void
    {
        if (app_config('app.env') === 'production') {
            $this->redirect('/login');
        }

        $email = (string) $request->query('email', 'admin@apprumo.local');
        $role = (string) $request->query('role', str_contains($email, 'admin') ? 'admin' : 'vendor');
        $name = (string) $request->query('name', $role === 'admin' ? 'Administrador Demo' : 'Profissional Demo');
        $status = (string) $request->query('status', 'active');

        $user = AuthService::upsertExternalUser([
            'email' => $email,
            'external_id' => 'dev-' . md5($email),
            'full_name' => $name,
            'role' => $role,
        ]);

        if ($role !== 'admin') {
            $vendor = VendorService::findByUserId((int) $user['id']);
            if (!$vendor && $status !== 'new') {
                Database::statement(
                    'INSERT INTO plans (name, price, duration_days, description, is_active, created_at, updated_at)
                     SELECT "Plano Demo", 99.90, 30, "Plano de demonstracao", 1, NOW(), NOW()
                     FROM DUAL
                     WHERE NOT EXISTS (SELECT 1 FROM plans WHERE name = "Plano Demo")'
                );

                $plan = Database::selectOne('SELECT * FROM plans WHERE name = "Plano Demo" LIMIT 1');
                $vendorId = VendorService::createOnboarding((int) $user['id'], [
                    'business_name' => 'Studio Demo',
                    'category' => 'Estetica',
                    'phone' => '5511999999999',
                ]);

                if ($status !== 'pending') {
                    Database::statement(
                        'UPDATE vendors
                         SET status = :status, plan_id = :plan_id, plan_started_at = CURDATE(),
                             plan_expires_at = :plan_expires_at, updated_at = NOW()
                         WHERE id = :id',
                        [
                            'status' => in_array($status, ['suspended', 'expired'], true) ? $status : 'active',
                            'plan_id' => $plan['id'] ?? null,
                            'plan_expires_at' => $status === 'expired' ? date('Y-m-d', strtotime('-1 day')) : date('Y-m-d', strtotime('+30 days')),
                            'id' => $vendorId,
                        ]
                    );
                }
            }
        }

        AuthService::loginByUserId((int) $user['id']);
        $this->redirect(AuthService::resolveLanding());
    }

    public function devSetTestPasswords(Request $request): void
    {
        if (app_config('app.env') === 'production') {
            $this->redirect('/login');
        }

        $testPassword = '@teste12345';
        $hash = password_hash($testPassword, PASSWORD_DEFAULT);
        if (!$hash) {
            $this->flashError('Não foi possível gerar hash de senha.');
            $this->redirect('/login');
        }

        $emails = [
            'admin@apprumo.local',
            'demo@apprumo.local',
            'pending@apprumo.local',
        ];

        foreach ($emails as $email) {
            \App\Core\Database::statement(
                'UPDATE platform_users SET password_hash = :hash, updated_at = NOW() WHERE email = :email',
                ['hash' => $hash, 'email' => $email]
            );
        }

        $this->flashSuccess('Senha de teste aplicada: ' . $testPassword);
        $this->redirect('/login');
    }
}

<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Security\RateLimiter;
use App\Security\SecurityLogger;
use App\Services\AuthService;
use App\Services\VendorService;
use RuntimeException;

final class OnboardingController extends Controller
{
    public function show(Request $request): void
    {
        $user = AuthService::requireAuthenticated();
        if (($user['role'] ?? 'vendor') === 'admin') {
            $this->redirect('/admin');
        }

        $vendors = VendorService::listForUser((int) $user['id']);
        if (count($vendors) > 0) {
            $this->redirect(AuthService::resolveLanding());
        }

        $this->render('auth/onboarding', [
            'title' => 'Onboarding',
        ], 'auth');
    }

    public function store(Request $request): void
    {
        $this->validateCsrf($request);
        $user = AuthService::requireAuthenticated();

        try {
            if (!RateLimiter::attempt('onboarding:' . $request->ip(), 5, 600)) {
                SecurityLogger::warning('onboarding_rate_limited');
                throw new RuntimeException('Muitas tentativas. Aguarde alguns minutos para enviar novamente.');
            }

            VendorService::createOnboarding((int) $user['id'], [
                'business_name' => $request->input('business_name'),
                'category' => $request->input('category'),
                'phone' => $request->input('phone'),
            ]);

            Session::clearOld();
            $this->flashSuccess('Cadastro enviado. Agora escolha o plano pelo WhatsApp e aguarde a aprovação do admin.');
            $this->redirect('/pending');
        } catch (RuntimeException $exception) {
            Session::rememberInput($request->input());
            $this->flashError($exception->getMessage());
            $this->redirect('/onboarding');
        }
    }
}

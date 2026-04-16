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

            $vendorId = VendorService::createOnboarding((int) $user['id'], [
                'business_name' => $request->input('business_name'),
                'category' => $request->input('category'),
                'phone' => $request->input('phone'),
            ]);

            // Auto-activate with 2-day free trial
            $trialEndDate = date('Y-m-d', strtotime('+2 days'));
            Database::statement(
                'UPDATE vendors
                 SET status = :status, trial_ends_at = :trial_ends_at,
                     plan_started_at = CURDATE(), plan_expires_at = :plan_expires_at,
                     updated_at = NOW()
                 WHERE id = :id',
                [
                    'status' => 'active',
                    'trial_ends_at' => $trialEndDate,
                    'plan_expires_at' => $trialEndDate,
                    'id' => $vendorId,
                ]
            );

            Session::clearOld();
            $this->flashSuccess('Conta criada! Você tem 2 dias de teste grátis. Aproveite!');
            $this->redirect('/vendor/dashboard');
        } catch (RuntimeException $exception) {
            Session::rememberInput($request->input());
            $this->flashError($exception->getMessage());
            $this->redirect('/onboarding');
        }
    }
}

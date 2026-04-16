<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\VendorService;

final class StatusController extends Controller
{
    public function pending(Request $request): void
    {
        $user = AuthService::requireAuthenticated();
        $vendor = AuthService::vendor();
        $business = $vendor['business_name'] ?? '';
        $email = $user['email'] ?? '';
        $plans = VendorService::listPlans(true);

        $this->render('status/panel', [
            'title' => 'Aguardando aprovação',
            'heading' => 'Seu cadastro está em análise',
            'description' => 'Recebemos os dados do seu negócio. Escolha um plano abaixo para ativar sua conta automaticamente, ou entre em contato via WhatsApp.',
            'cta_label' => 'Escolher plano no WhatsApp',
            'support_message' => 'Olá! Quero escolher meu plano na Apprumo. Meu negócio: ' . $business . '. Meu e-mail: ' . $email . '.',
            'plans' => $plans,
            'show_trial_expired' => false,
        ], 'status');
    }

    public function suspended(Request $request): void
    {
        AuthService::requireAuthenticated();

        $this->render('status/panel', [
            'title' => 'Conta suspensa',
            'heading' => 'Sua conta está suspensa',
            'description' => 'Entre em contato com nosso suporte para regularizar o acesso ao sistema e voltar a operar normalmente.',
            'cta_label' => 'Regularizar via WhatsApp',
            'support_message' => 'Olá! Quero regularizar uma conta suspensa na Apprumo.',
            'plans' => [],
            'show_trial_expired' => false,
        ], 'status');
    }

    public function expired(Request $request): void
    {
        AuthService::requireAuthenticated();
        $vendor = AuthService::vendor();
        $plans = VendorService::listPlans(true);
        $wasTrial = !empty($vendor['trial_ends_at']) && empty($vendor['plan_id']);

        $this->render('status/panel', [
            'title' => $wasTrial ? 'Teste grátis encerrado' : 'Plano expirado',
            'heading' => $wasTrial ? 'Seu teste grátis de 2 dias acabou!' : 'Seu plano venceu',
            'description' => $wasTrial
                ? 'Você aproveitou o teste grátis e agora é hora de escolher o plano ideal para continuar usando todas as funcionalidades do Apprumo.'
                : 'Renove seu plano abaixo para continuar gerenciando agenda, financeiro, estoque e seu perfil público.',
            'cta_label' => 'Contratar plano',
            'support_message' => 'Olá! Quero renovar meu plano na Apprumo.',
            'plans' => $plans,
            'show_trial_expired' => $wasTrial,
        ], 'status');
    }
}

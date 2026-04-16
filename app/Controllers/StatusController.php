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
        ], 'status');
    }

    public function expired(Request $request): void
    {
        AuthService::requireAuthenticated();
        $plans = VendorService::listPlans(true);

        $this->render('status/panel', [
            'title' => 'Plano expirado',
            'heading' => 'Seu plano venceu',
            'description' => 'Renove seu plano abaixo para continuar gerenciando agenda, financeiro, estoque e seu perfil público.',
            'cta_label' => 'Contratar plano',
            'support_message' => 'Olá! Quero renovar meu plano na Apprumo.',
            'plans' => $plans,
        ], 'status');
    }
}

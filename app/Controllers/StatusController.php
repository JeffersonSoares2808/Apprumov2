<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Services\AuthService;

final class StatusController extends Controller
{
    public function pending(Request $request): void
    {
        $user = AuthService::requireAuthenticated();
        $vendor = \App\Services\AuthService::vendor();
        $business = $vendor['business_name'] ?? '';
        $email = $user['email'] ?? '';

        $this->render('status/panel', [
            'title' => 'Aguardando aprovação',
            'heading' => 'Seu cadastro está em análise',
            'description' => 'Recebemos os dados do seu negócio. Para acelerar, escolha o plano pelo WhatsApp. O acesso ao painel completo será liberado assim que o admin aprovar.',
            'cta_label' => 'Escolher plano no WhatsApp',
            'support_message' => 'Olá! Quero escolher meu plano na Apprumo. Meu negócio: ' . $business . '. Meu e-mail: ' . $email . '.',
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
        ], 'status');
    }

    public function expired(Request $request): void
    {
        AuthService::requireAuthenticated();

        $this->render('status/panel', [
            'title' => 'Plano expirado',
            'heading' => 'Seu plano venceu',
            'description' => 'Renove o plano para continuar gerenciando agenda, financeiro, estoque e seu perfil público.',
            'cta_label' => 'Contratar plano',
            'support_message' => 'Olá! Quero renovar meu plano na Apprumo.',
        ], 'status');
    }
}

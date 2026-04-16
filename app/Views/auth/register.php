<?php
$supportUrl = support_whatsapp_url('Olá! Quero criar minha conta na Apprumo e preciso de ajuda.');
?>

<div class="login-screen">
    <div class="login-screen__particles" aria-hidden="true"></div>

    <div class="login-card">
        <div class="login-card__inner">
            <header class="login-card__header">
                <img class="login-card__logo" src="<?= e(brand_logo_url()) ?>" alt="Apprumo" width="200" height="72" decoding="async">
                <p class="login-card__tagline">Crie sua conta e comece a gerenciar seu negócio</p>
            </header>

            <h1 class="login-card__welcome">Criar conta no <strong>Apprumo</strong></h1>
            <p class="login-card__desc">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5" style="vertical-align:-2px;margin-right:2px;"><polyline points="20 6 9 17 4 12"/></svg>
                Ganhe <strong>2 dias grátis</strong> para testar todas as funcionalidades!
            </p>

            <form class="login-form" method="post" action="<?= base_url('register') ?>" data-disable-on-submit>
                <?= csrf_field() ?>

                <div class="login-field">
                    <span class="login-field__icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="1.8"/></svg>
                    </span>
                    <input id="reg_full_name" name="full_name" type="text" required autocomplete="name" placeholder="Nome completo" value="<?= e(old('full_name', '')) ?>">
                </div>

                <div class="login-field">
                    <span class="login-field__icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" stroke="currentColor" stroke-width="1.8"/><path d="m22 6-10 7L2 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <input id="reg_email" name="email" type="email" required autocomplete="email" placeholder="Seu melhor e-mail" value="<?= e(old('email', '')) ?>">
                </div>

                <div class="login-field login-field--password">
                    <span class="login-field__icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><rect x="3" y="11" width="18" height="11" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M7 11V7a5 5 0 0 1 10 0v4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    </span>
                    <input id="reg_password" name="password" type="password" required autocomplete="new-password" placeholder="Senha (mín. 8 caracteres)">
                    <button class="login-field__toggle" type="button" data-toggle-password aria-label="mostrar senha">
                        <svg class="eye-open" width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.8"/></svg>
                        <svg class="eye-closed" width="20" height="20" viewBox="0 0 24 24" fill="none" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><line x1="1" y1="1" x2="23" y2="23" stroke="currentColor" stroke-width="1.8"/></svg>
                    </button>
                    <span class="login-field__hint" data-toggle-password-label>mostrar senha</span>
                </div>

                <div class="login-field login-field--password">
                    <span class="login-field__icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M9 12l2 2 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><rect x="3" y="11" width="18" height="11" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M7 11V7a5 5 0 0 1 10 0v4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    </span>
                    <input id="reg_password_confirm" name="password_confirm" type="password" required autocomplete="new-password" placeholder="Confirme a senha">
                    <button class="login-field__toggle" type="button" data-toggle-password aria-label="mostrar senha">
                        <svg class="eye-open" width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.8"/></svg>
                        <svg class="eye-closed" width="20" height="20" viewBox="0 0 24 24" fill="none" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><line x1="1" y1="1" x2="23" y2="23" stroke="currentColor" stroke-width="1.8"/></svg>
                    </button>
                    <span class="login-field__hint" data-toggle-password-label>mostrar senha</span>
                </div>

                <button class="login-btn" type="submit" data-loading-label="Criando conta...">
                    <span>CRIAR CONTA</span>
                </button>
            </form>

            <div class="login-card__links">
                <a href="<?= base_url('login') ?>">Já tem conta? Fazer login</a>
                <a href="<?= e($supportUrl) ?>" target="_blank" rel="noopener">Precisa de ajuda? Fale com suporte</a>
            </div>
        </div>
    </div>

    <footer class="login-footer">
        <a class="login-footer__item" href="<?= base_url('vendor/agenda') ?>">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none"><rect x="3" y="5" width="18" height="16" rx="3" stroke="currentColor" stroke-width="1.5"/><path d="M8 3v4M16 3v4M3 10h18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
            <span>Agendamento</span>
        </a>
        <a class="login-footer__item" href="<?= base_url('vendor/products') ?>">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none"><path d="M5 8.5 12 4l7 4.5v7L12 20l-7-4.5v-7Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M5 8.5 12 13l7-4.5M12 13v7" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>
            <span>Estoque</span>
        </a>
        <a class="login-footer__item" href="<?= base_url('vendor/finance') ?>">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none"><path d="M12 3v18M16.5 7.5c0-1.933-2.015-3.5-4.5-3.5s-4.5 1.567-4.5 3.5 2.015 3.5 4.5 3.5 4.5 1.567 4.5 3.5-2.015 3.5-4.5 3.5-4.5-1.567-4.5-3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M6 18h12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
            <span>Financeiro</span>
        </a>
    </footer>
</div>


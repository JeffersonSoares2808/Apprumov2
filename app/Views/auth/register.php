<?php
$supportUrl = support_whatsapp_url('Olá! Quero criar minha conta na Apprumo e preciso de ajuda.');
?>

<div class="auth-page auth-page--premium">
    <div class="auth-page__glow" aria-hidden="true"></div>

    <div class="auth-page__inner auth-page__inner--wide">
        <header class="auth-brand auth-brand--premium">
            <div class="auth-brand__logo">
                <img src="<?= e(brand_logo_url()) ?>" alt="Apprumo" width="220" height="80" decoding="async">
            </div>
            <p class="auth-brand__tagline">Crie sua conta, configure seu estabelecimento e solicite seu plano.</p>
        </header>

        <div class="auth-layout auth-layout--premium">
            <section class="auth-panel auth-panel--story auth-panel--hero" aria-labelledby="register-story-title">
                <span class="auth-eyebrow">Primeiro acesso</span>
                <h1 id="register-story-title" class="auth-panel__title">Em 2 minutos você já deixa seu perfil pronto para vender.</h1>
                <p class="auth-panel__lead">Depois do cadastro, você configura seu estabelecimento e envia a solicitação do plano pelo WhatsApp. O admin libera o acesso e você já começa a operar.</p>

                <div class="auth-alert">
                    <strong>Dúvidas?</strong>
                    <p>Se precisar, fale com o suporte para acelerar sua liberação e escolher o melhor plano.</p>
                    <a class="btn btn-light" href="<?= e($supportUrl) ?>" target="_blank" rel="noopener">Falar com suporte</a>
                </div>
            </section>

            <section class="auth-panel auth-panel--form auth-panel--glass" aria-labelledby="register-form-title">
                <div class="auth-login-header auth-login-header--left">
                    <span class="soft-pill">Cadastro</span>
                    <h2 id="register-form-title" class="auth-form-title">Criar conta</h2>
                    <p class="auth-form-hint">Use um e-mail real. Você vai usar para entrar no painel.</p>
                </div>

                <form class="auth-form auth-form--premium" method="post" action="<?= base_url('register') ?>" data-disable-on-submit>
                    <?= csrf_field() ?>

                    <div class="field">
                        <label for="reg_full_name">Nome completo</label>
                        <input id="reg_full_name" name="full_name" type="text" required autocomplete="name" placeholder="Seu nome" value="<?= e(old('full_name', '')) ?>">
                    </div>

                    <div class="field">
                        <label for="reg_email">E-mail</label>
                        <input id="reg_email" name="email" type="email" required autocomplete="email" placeholder="voce@seudominio.com" value="<?= e(old('email', '')) ?>">
                    </div>

                    <div class="field">
                        <label for="reg_password">Senha</label>
                        <input id="reg_password" name="password" type="password" required autocomplete="new-password" placeholder="Mínimo 8 caracteres">
                    </div>

                    <div class="field">
                        <label for="reg_password_confirm">Confirmar senha</label>
                        <input id="reg_password_confirm" name="password_confirm" type="password" required autocomplete="new-password" placeholder="Repita a senha">
                    </div>

                    <button class="btn auth-btn-block auth-primary-button" type="submit" data-loading-label="Criando...">Criar conta e configurar</button>
                </form>

                <p class="auth-note auth-note--premium">
                    Já tem conta? <a class="auth-inline-link" href="<?= base_url('login') ?>">Entrar</a>
                </p>
            </section>
        </div>
    </div>
</div>


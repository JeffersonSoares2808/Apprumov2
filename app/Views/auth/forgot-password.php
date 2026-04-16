<?php
$supportUrl = support_whatsapp_url('Olá! Preciso de ajuda para recuperar minha senha na Apprumo.');
?>

<div class="login-screen">
    <div class="login-screen__particles" aria-hidden="true"></div>

    <div class="login-card">
        <div class="login-card__inner">
            <header class="login-card__header">
                <img class="login-card__logo" src="<?= e(brand_logo_url()) ?>" alt="Apprumo" width="200" height="72" decoding="async">
                <p class="login-card__tagline">Recupere o acesso à sua conta</p>
            </header>

            <h1 class="login-card__welcome">Esqueceu sua <strong>senha</strong>?</h1>
            <p class="login-card__desc">Informe seu e-mail cadastrado e enviaremos um link para redefinir sua senha.</p>

            <form class="login-form" method="post" action="<?= base_url('forgot-password') ?>" data-disable-on-submit>
                <?= csrf_field() ?>

                <div class="login-field">
                    <span class="login-field__icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" stroke="currentColor" stroke-width="1.8"/><path d="m22 6-10 7L2 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <input id="reset_email" name="email" type="email" required autocomplete="email" placeholder="Seu e-mail cadastrado" value="<?= e(old('email', '')) ?>">
                </div>

                <button class="login-btn" type="submit" data-loading-label="Enviando...">
                    <span>ENVIAR LINK DE REDEFINIÇÃO</span>
                </button>
            </form>

            <div class="login-card__links">
                <a href="<?= base_url('login') ?>">← Voltar ao login</a>
                <a href="<?= e($supportUrl) ?>" target="_blank" rel="noopener">Precisa de ajuda? Fale com suporte</a>
            </div>
        </div>
    </div>
</div>

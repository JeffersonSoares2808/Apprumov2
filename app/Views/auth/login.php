<?php
$supportUrl = support_whatsapp_url('Olá! Preciso de ajuda para acessar a Apprumo.');
?>

<div class="login-screen">
    <div class="login-screen__particles" aria-hidden="true"></div>

    <div class="login-card">
        <div class="login-card__inner">
            <header class="login-card__header">
                <img class="login-card__logo" src="<?= e(brand_logo_url()) ?>" alt="Apprumo" width="200" height="72" decoding="async">
                <p class="login-card__tagline">Sistema de Gestão Integrada | Agendamento · <strong>Estoque</strong> · Financeiro</p>
            </header>

            <h1 class="login-card__welcome">Bem-vindo de volta ao <strong>Apprumo</strong></h1>

            <form class="login-form" method="post" action="<?= base_url('auth/login') ?>" data-disable-on-submit>
                <?= csrf_field() ?>

                <div class="login-field">
                    <span class="login-field__icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" stroke="currentColor" stroke-width="1.8"/><path d="m22 6-10 7L2 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <input id="login_email" name="email" type="email" required autocomplete="email" placeholder="Seu e-mail corporativo" value="<?= e(old('email', '')) ?>">
                </div>

                <div class="login-field login-field--password">
                    <span class="login-field__icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><rect x="3" y="11" width="18" height="11" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M7 11V7a5 5 0 0 1 10 0v4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    </span>
                    <input id="login_password" name="password" type="password" required autocomplete="current-password" placeholder="Sua senha">
                    <button class="login-field__toggle" type="button" data-toggle-password aria-label="mostrar senha">
                        <svg class="eye-open" width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.8"/></svg>
                        <svg class="eye-closed" width="20" height="20" viewBox="0 0 24 24" fill="none" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><line x1="1" y1="1" x2="23" y2="23" stroke="currentColor" stroke-width="1.8"/></svg>
                    </button>
                    <span class="login-field__hint" data-toggle-password-label>mostrar senha</span>
                </div>

                <button class="login-btn" type="submit" data-loading-label="Entrando...">
                    <span>ENTRAR</span>
                </button>

                <label class="login-card__remember">
                    <input type="checkbox" name="remember">
                    <span>Manter conectado</span>
                </label>
            </form>

            <div class="login-card__links">
                <a href="<?= e($supportUrl) ?>" target="_blank" rel="noopener">Esqueceu a senha?</a>
                <a href="<?= base_url('register') ?>">Solicitar acesso (Criar conta)</a>
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

    <?php if (!empty($show_demo_links)): ?>
        <details class="login-demo">
            <summary>Acessos de demonstração</summary>
            <p class="login-demo__hint">Atalhos liberados apenas fora de produção.</p>
            <div class="login-demo__grid">
                <a class="btn btn-ghost" href="<?= base_url('dev/login?email=admin@apprumo.local&role=admin') ?>">Admin demo</a>
                <a class="btn btn-ghost" href="<?= base_url('dev/login?email=demo@apprumo.local&role=vendor&status=active') ?>">Profissional ativo</a>
                <a class="btn btn-ghost" href="<?= base_url('dev/login?email=pending@apprumo.local&role=vendor&status=pending') ?>">Cadastro pendente</a>
            </div>
        </details>
    <?php endif; ?>
</div>

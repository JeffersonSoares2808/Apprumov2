<?php
$supportUrl = support_whatsapp_url('Olá! Preciso de ajuda para acessar a Apprumo.');
?>

<div class="auth-page auth-page--premium">
    <div class="auth-page__glow" aria-hidden="true"></div>

    <div class="auth-page__inner auth-page__inner--wide">
        <header class="auth-brand auth-brand--premium">
            <div class="auth-brand__logo">
                <img src="<?= e(brand_logo_url()) ?>" alt="Apprumo" width="220" height="80" decoding="async">
            </div>
            <p class="auth-brand__tagline">Agenda · Estoque · Financeiro — gestão impecável para profissionais que querem vender melhor.</p>
        </header>

        <div class="auth-layout auth-layout--premium">
            <section class="auth-panel auth-panel--story auth-panel--hero" aria-labelledby="auth-story-title">
                <span class="auth-eyebrow">Experiência premium para o seu negócio</span>
                <h1 id="auth-story-title" class="auth-panel__title">Um painel com cara de produto sério, rápido e pronto para uso no celular.</h1>
                <p class="auth-panel__lead">O Apprumo centraliza agenda, financeiro, serviços, produtos e perfil público em uma experiência mais elegante, clara e confiável — sem cara de sistema improvisado.</p>

                <div class="auth-feature-grid auth-feature-grid--dense">
                    <div class="auth-feature-card">
                        <strong>Agenda inteligente</strong>
                        <span>Horários por serviço, status visuais, fila de espera e lembrete por WhatsApp.</span>
                    </div>
                    <div class="auth-feature-card">
                        <strong>Operação no mesmo fluxo</strong>
                        <span>Produtos, serviços, clientes e receitas conectados sem retrabalho.</span>
                    </div>
                    <div class="auth-feature-card">
                        <strong>Presença pública profissional</strong>
                        <span>Perfil público bonito e link direto para booking do cliente.</span>
                    </div>
                </div>

                <div class="auth-alert">
                    <strong>Fluxo de assinatura (SaaS)</strong>
                    <p>Crie sua conta com e-mail e senha, configure o estabelecimento e envie a solicitação do plano pelo WhatsApp. O acesso ao painel completo é liberado após aprovação do admin.</p>
                    <a class="btn btn-light" href="<?= e($supportUrl) ?>" target="_blank" rel="noopener">Falar com suporte</a>
                </div>
            </section>

            <section class="auth-panel auth-panel--form auth-panel--glass" aria-labelledby="auth-form-title">
                <div class="auth-login-header auth-login-header--left">
                    <span class="soft-pill">Acesso seguro</span>
                    <h2 id="auth-form-title" class="auth-form-title">Entrar no painel</h2>
                    <p class="auth-form-hint">Preencha os dados abaixo para abrir o painel certo automaticamente.</p>
                </div>

                <form class="auth-form auth-form--premium" method="post" action="<?= base_url('auth/login') ?>" data-disable-on-submit>
                    <?= csrf_field() ?>

                    <div class="field">
                        <label for="login_email">E-mail</label>
                        <input id="login_email" name="email" type="email" required autocomplete="email" placeholder="voce@seudominio.com" value="<?= e(old('email', '')) ?>">
                    </div>

                    <div class="field">
                        <label for="login_password">Senha</label>
                        <input id="login_password" name="password" type="password" required autocomplete="current-password" placeholder="Sua senha">
                    </div>

                    <button class="btn auth-btn-block auth-primary-button" type="submit" data-loading-label="Entrando...">Entrar no painel</button>
                </form>

                <p class="auth-note auth-note--premium">
                    Não tem conta? <a class="auth-inline-link" href="<?= base_url('register') ?>">Criar conta</a>
                </p>

                <div class="auth-trust-row">
                    <div>
                        <strong>Mobile first</strong>
                        <span>Funciona bem na rotina corrida.</span>
                    </div>
                    <div>
                        <strong>Subpasta friendly</strong>
                        <span>Pronto para Hostinger e deploy simples.</span>
                    </div>
                </div>

                <?php if (!empty($show_demo_links)): ?>
                    <details class="auth-demo">
                        <summary>Acessos de demonstração</summary>
                        <p class="auth-fine-print">Atalhos liberados apenas fora de produção.</p>
                        <div class="auth-demo__grid">
                            <a class="btn btn-ghost" href="<?= base_url('dev/login?email=admin@apprumo.local&role=admin') ?>">Admin demo</a>
                            <a class="btn btn-ghost" href="<?= base_url('dev/login?email=demo@apprumo.local&role=vendor&status=active') ?>">Profissional ativo</a>
                            <a class="btn btn-ghost" href="<?= base_url('dev/login?email=pending@apprumo.local&role=vendor&status=pending') ?>">Cadastro pendente</a>
                        </div>
                    </details>
                <?php endif; ?>
            </section>
        </div>
    </div>
</div>

<div class="login-screen">
    <div class="login-screen__particles" aria-hidden="true"></div>

    <div class="login-card" style="max-width:520px;">
        <div class="login-card__inner">
            <header class="login-card__header">
                <img class="login-card__logo" src="<?= e(brand_logo_url()) ?>" alt="Apprumo" width="200" height="72" decoding="async">
                <p class="login-card__tagline">Vamos criar seu espaço na plataforma</p>
            </header>

            <h1 class="login-card__welcome">Configure seu <strong>negócio</strong></h1>
            <p class="login-card__desc">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5" style="vertical-align:-2px;margin-right:4px;"><polyline points="20 6 9 17 4 12"/></svg>
                Você terá <strong>2 dias grátis</strong> para testar todas as funcionalidades!
            </p>

            <form class="login-form" method="post" action="<?= base_url('onboarding') ?>" data-disable-on-submit>
                <?= csrf_field() ?>
                <div class="login-field">
                    <span class="login-field__icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M19 21V5a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v16m14 0H5m14 0h2m-16 0H3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    </span>
                    <input id="business_name" name="business_name" type="text" value="<?= e(old('business_name')) ?>" required placeholder="Nome do seu negócio">
                </div>
                <div class="login-field">
                    <span class="login-field__icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M4 7h16M4 12h16M4 17h10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    </span>
                    <input id="category" name="category" type="text" value="<?= e(old('category')) ?>" placeholder="Categoria (ex: Estética, Barbearia, Manicure...)" required>
                </div>
                <div class="login-field">
                    <span class="login-field__icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.86 19.86 0 0 1 3.09 5.18 2 2 0 0 1 5.05 3h3a2 2 0 0 1 2 1.72c.13.81.36 1.6.66 2.35a2 2 0 0 1-.45 2.11L8.09 11.34a16 16 0 0 0 6.29 6.29l2.16-2.16a2 2 0 0 1 2.11-.45c.75.3 1.54.53 2.35.66a2 2 0 0 1 1.72 2z" stroke="currentColor" stroke-width="1.8"/></svg>
                    </span>
                    <input id="phone" name="phone" type="text" value="<?= e(old('phone')) ?>" placeholder="Telefone (5511999999999)" required>
                </div>

                <button class="login-btn" type="submit" data-loading-label="Ativando...">
                    <span>ATIVAR TESTE GRÁTIS</span>
                </button>
            </form>

            <div class="login-card__links">
                <a href="<?= e(support_whatsapp_url('Olá! Preciso de ajuda para concluir meu cadastro na Apprumo.')) ?>" target="_blank" rel="noopener">Falar com suporte</a>
                <form method="post" action="<?= base_url('auth/logout') ?>" style="display:inline;">
                    <?= csrf_field() ?>
                    <button type="submit" style="background:none;border:none;color:#1AB2C7;cursor:pointer;font-size:inherit;padding:0;">Sair</button>
                </form>
            </div>
        </div>
    </div>
</div>

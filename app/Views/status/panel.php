<div class="status-screen status-screen--premium">
    <div class="card status-panel status-panel--premium">
        <div class="brand-lockup" style="margin: 0 auto 18px;">
            <img class="brand-logo-image" src="<?= e(brand_logo_url()) ?>" alt="Apprumo" width="200" height="64" decoding="async">
        </div>
        <span class="soft-pill soft-pill--gold">Status da conta</span>
        <h1><?= e($heading) ?></h1>
        <p class="muted"><?= e($description) ?></p>

        <?php if (!empty($plans)): ?>
            <div class="checkout-plans" style="margin-top:20px;">
                <?php foreach ($plans as $plan): ?>
                    <?php if (!empty($plan['stripe_checkout_url'])): ?>
                        <div class="checkout-plan-card">
                            <div class="checkout-plan-card__info">
                                <strong class="checkout-plan-card__name"><?= e($plan['name']) ?></strong>
                                <?php if (!empty($plan['description'])): ?>
                                    <span class="muted checkout-plan-card__desc"><?= e($plan['description']) ?></span>
                                <?php endif; ?>
                                <div class="checkout-plan-card__meta">
                                    <span class="checkout-plan-card__price"><?= money($plan['price']) ?></span>
                                    <span class="muted"><?= (int) $plan['duration_days'] ?> dias</span>
                                </div>
                            </div>
                            <a class="btn checkout-plan-card__btn" href="<?= e($plan['stripe_checkout_url']) ?>" target="_blank" rel="noopener">
                                <svg viewBox="0 0 24 24" fill="none" width="18" height="18" style="vertical-align:middle;margin-right:4px;"><path d="M21 4H3a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h18a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2z" stroke="currentColor" stroke-width="1.5"/><path d="M1 10h22" stroke="currentColor" stroke-width="1.5"/></svg>
                                Pagar agora
                            </a>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <div class="checkout-divider">
                <span>ou</span>
            </div>
        <?php endif; ?>

        <div class="stack stack--spacious" style="margin-top:12px;">
            <a class="btn btn-secondary" href="<?= e(support_whatsapp_url($support_message ?? 'Olá! Preciso de suporte na Apprumo.')) ?>"><?= e($cta_label) ?></a>
            <form method="post" action="<?= base_url('auth/logout') ?>">
                <?= csrf_field() ?>
                <button class="btn btn-light btn-block" type="submit">Sair da conta</button>
            </form>
        </div>
        <p class="footer-note">Desenvolvido por JS Sistemas Inteligentes</p>
    </div>
</div>

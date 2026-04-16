<div class="status-screen status-screen--premium">
    <div class="card status-panel status-panel--premium">
        <div class="brand-lockup" style="margin: 0 auto 18px;">
            <img class="brand-logo-image" src="<?= e(brand_logo_url()) ?>" alt="Apprumo" width="200" height="64" decoding="async">
        </div>
        <span class="soft-pill soft-pill--gold">Status da conta</span>
        <h1><?= e($heading) ?></h1>
        <p class="muted" style="font-size:15px;line-height:1.6;max-width:500px;margin:0 auto 24px;"><?= e($description) ?></p>

        <?php if (!empty($plans)): ?>
            <div class="plans-showcase">
                <?php foreach ($plans as $index => $plan): ?>
                    <?php if (!empty($plan['stripe_checkout_url'])): ?>
                        <div class="plan-showcase-card <?= $index === 1 ? 'plan-showcase-card--featured' : '' ?>">
                            <?php if ($index === 0): ?>
                                <span class="plan-showcase-card__badge">Mais popular</span>
                            <?php elseif ($index === 1): ?>
                                <span class="plan-showcase-card__badge plan-showcase-card__badge--gold">Empresas</span>
                            <?php endif; ?>

                            <h3 class="plan-showcase-card__name"><?= e($plan['name']) ?></h3>

                            <div class="plan-showcase-card__price-wrap">
                                <span class="plan-showcase-card__currency">R$</span>
                                <span class="plan-showcase-card__amount"><?= number_format((float) $plan['price'], 2, ',', '.') ?></span>
                                <span class="plan-showcase-card__period">/mês</span>
                            </div>

                            <?php if (!empty($plan['description'])): ?>
                                <p class="plan-showcase-card__desc"><?= e($plan['description']) ?></p>
                            <?php endif; ?>

                            <ul class="plan-showcase-card__features">
                                <li>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                    Agenda inteligente com IA
                                </li>
                                <li>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                    Gestão financeira completa
                                </li>
                                <li>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                    Controle de estoque
                                </li>
                                <?php if ((int) ($plan['max_professionals'] ?? 0) > 1): ?>
                                <li>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                    Até <?= (int) $plan['max_professionals'] ?> profissionais
                                </li>
                                <?php endif; ?>
                                <li>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                    <?= (int) $plan['duration_days'] ?> dias de acesso
                                </li>
                            </ul>

                            <a class="btn plan-showcase-card__btn <?= $index === 1 ? 'plan-showcase-card__btn--featured' : '' ?>" href="<?= e($plan['stripe_checkout_url']) ?>" target="_blank" rel="noopener noreferrer">
                                <svg viewBox="0 0 24 24" fill="none" width="18" height="18" style="vertical-align:middle;margin-right:4px;"><path d="M21 4H3a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h18a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2z" stroke="currentColor" stroke-width="1.5"/><path d="M1 10h22" stroke="currentColor" stroke-width="1.5"/></svg>
                                Assinar agora
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

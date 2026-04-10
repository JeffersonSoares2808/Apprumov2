<div class="status-screen status-screen--premium">
    <div class="card status-panel status-panel--premium">
        <div class="brand-lockup" style="margin: 0 auto 18px;">
            <img class="brand-logo-image" src="<?= e(brand_logo_url()) ?>" alt="Apprumo" width="200" height="64" decoding="async">
        </div>
        <span class="soft-pill soft-pill--gold">Status da conta</span>
        <h1><?= e($heading) ?></h1>
        <p class="muted"><?= e($description) ?></p>
        <div class="stack stack--spacious" style="margin-top:20px;">
            <a class="btn btn-secondary" href="<?= e(support_whatsapp_url($support_message ?? 'Olá! Preciso de suporte na Apprumo.')) ?>"><?= e($cta_label) ?></a>
            <form method="post" action="<?= base_url('auth/logout') ?>">
                <?= csrf_field() ?>
                <button class="btn btn-light btn-block" type="submit">Sair da conta</button>
            </form>
        </div>
        <p class="footer-note">Desenvolvido por JS Sistemas Inteligentes</p>
    </div>
</div>

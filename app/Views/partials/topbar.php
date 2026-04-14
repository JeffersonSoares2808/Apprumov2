<?php
$currentPath = normalize_app_path($_SERVER['REQUEST_URI'] ?? '/');
$isDashboard = path_starts_with('/vendor/dashboard');
$shareUrl = base_url('p/' . ($vendor['slug'] ?? ''));
$pageTitle = $title ?? 'Painel';
$user = \App\Services\AuthService::user();
$canSwitchVendor = false;
if ($user && ($user['role'] ?? 'vendor') !== 'admin') {
    $canSwitchVendor = count(\App\Services\VendorService::listForUser((int) $user['id'])) > 1;
}
$daysToExpire = null;
$warningDays = (int) app_config('app.plan_expiry_warning_days', 7);
if (!empty($vendor['plan_expires_at']) && ($vendor['status'] ?? '') === 'active') {
    $daysToExpire = days_until((string) $vendor['plan_expires_at']);
}
?>
<header class="topbar topbar--premium">
    <div class="topbar-main">
        <div class="topbar-brand topbar-brand--premium">
            <?php if (!$isDashboard): ?>
                <button class="back-button" type="button" data-back-button data-fallback-url="<?= e(page_back_url()) ?>" aria-label="Voltar para a tela anterior">
                    <span aria-hidden="true">←</span>
                </button>
            <?php endif; ?>

            <div class="brand-chip brand-chip--premium">
                <img class="brand-chip-image" src="<?= e(brand_logo_url()) ?>" alt="Apprumo" width="120" height="40" decoding="async">
            </div>

            <div class="topbar-copy">
                <strong><?= e($vendor['business_name'] ?? 'Apprumo') ?></strong>
                <?php if ($daysToExpire !== null && $daysToExpire >= 0 && $daysToExpire <= $warningDays): ?>
                    <a class="topbar-renew-link" href="<?= e(support_whatsapp_url('Olá! Quero renovar meu plano na Apprumo. Meu negócio é: ' . ($vendor['business_name'] ?? ''))) ?>">
                        Plano vence em <?= (int) $daysToExpire ?> dia(s)
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="topbar-actions topbar-actions--desktop">
            <button class="btn btn-light btn--sm" type="button" data-copy-url="<?= e($shareUrl) ?>">Compartilhar</button>
            <?php if ($canSwitchVendor): ?>
                <a class="btn btn-light btn--sm" href="<?= base_url('select-vendor') ?>">Trocar</a>
            <?php endif; ?>
            <a class="btn btn-light btn--sm" href="<?= base_url('vendor/settings') ?>">
                <svg viewBox="0 0 24 24" fill="none" width="16" height="16" style="vertical-align:middle"><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.8"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
            </a>
            <form method="post" action="<?= base_url('auth/logout') ?>">
                <?= csrf_field() ?>
                <button class="btn btn-secondary btn--sm" type="submit">Sair</button>
            </form>
        </div>

        <button class="topbar-menu-toggle" type="button" data-menu-toggle aria-expanded="false" aria-controls="vendor-menu-sheet">
            <span></span><span></span><span></span>
            <span class="sr-only">Abrir ações rápidas</span>
        </button>
    </div>

    <div class="topbar-mobile-panel" id="vendor-menu-sheet" data-menu-panel hidden>
        <button class="btn btn-light btn-block" type="button" data-copy-url="<?= e($shareUrl) ?>">Compartilhar perfil público</button>
        <?php if ($canSwitchVendor): ?>
            <a class="btn btn-light btn-block" href="<?= base_url('select-vendor') ?>">Trocar negócio</a>
        <?php endif; ?>
        <a class="btn btn-light btn-block" href="<?= base_url('vendor/settings') ?>">Abrir configurações</a>
        <form method="post" action="<?= base_url('auth/logout') ?>">
            <?= csrf_field() ?>
            <button class="btn btn-secondary btn-block" type="submit">Encerrar sessão</button>
        </form>
    </div>
</header>

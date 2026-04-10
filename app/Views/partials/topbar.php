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
                <span class="topbar-kicker"><?= $isDashboard ? 'Painel profissional' : e($pageTitle) ?></span>
                <strong><?= e($vendor['business_name'] ?? 'Apprumo') ?></strong>
                <div class="muted"><?= e($vendor['category'] ?? 'Painel premium') ?></div>
            </div>
        </div>

        <div class="topbar-actions topbar-actions--desktop">
            <button class="btn btn-light" type="button" data-copy-url="<?= e($shareUrl) ?>">Compartilhar perfil</button>
            <?php if ($canSwitchVendor): ?>
                <a class="btn btn-light" href="<?= base_url('select-vendor') ?>">Trocar negócio</a>
            <?php endif; ?>
            <a class="btn btn-light" href="<?= base_url('vendor/settings') ?>">Configurações</a>
            <form method="post" action="<?= base_url('auth/logout') ?>">
                <?= csrf_field() ?>
                <button class="btn btn-secondary" type="submit">Sair</button>
            </form>
        </div>

        <button class="topbar-menu-toggle" type="button" data-menu-toggle aria-expanded="false" aria-controls="vendor-menu-sheet">
            <span></span><span></span><span></span>
            <span class="sr-only">Abrir ações rápidas</span>
        </button>
    </div>

    <div class="topbar-meta-row">
        <span class="soft-pill">Slug público: /<?= e($vendor['slug'] ?? 'perfil') ?></span>
        <span class="soft-pill">Status premium mobile-first</span>
        <?php if ($daysToExpire !== null && $daysToExpire >= 0 && $daysToExpire <= $warningDays): ?>
            <a class="soft-pill soft-pill--gold" href="<?= e(support_whatsapp_url('Olá! Quero renovar meu plano na Apprumo. Meu negócio é: ' . ($vendor['business_name'] ?? ''))) ?>">
                Plano vence em <?= (int) $daysToExpire ?> dia(s) — renovar
            </a>
        <?php endif; ?>
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

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
$whatsappShareText = urlencode('Olá! Conheça meu perfil e agende online: ' . $shareUrl);
$whatsappShareLink = 'https://api.whatsapp.com/send?text=' . $whatsappShareText;
?>
<header class="topbar topbar--premium">
    <div class="topbar-main">
        <div class="topbar-brand topbar-brand--premium">
            <?php if (!$isDashboard): ?>
                <button class="back-button" type="button" data-back-button data-fallback-url="<?= e(page_back_url()) ?>" aria-label="Voltar para a tela anterior">
                    <span aria-hidden="true">←</span>
                </button>
            <?php endif; ?>

            <a class="brand-chip brand-chip--premium" href="<?= base_url('vendor/dashboard') ?>" aria-label="Ir para o início">
                <img class="brand-chip-image" src="<?= e(brand_logo_url()) ?>" alt="Apprumo" width="120" height="40" decoding="async">
            </a>

            <div class="topbar-copy">
                <strong title="<?= e($vendor['business_name'] ?? 'Apprumo') ?>"><?= e($vendor['business_name'] ?? 'Apprumo') ?></strong>
                <?php if ($daysToExpire !== null && $daysToExpire >= 0 && $daysToExpire <= $warningDays): ?>
                    <a class="topbar-renew-link" href="<?= e(support_whatsapp_url('Olá! Quero renovar meu plano na Apprumo. Meu negócio é: ' . ($vendor['business_name'] ?? ''))) ?>">
                        Plano vence em <?= (int) $daysToExpire ?> dia(s)
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="topbar-actions topbar-actions--desktop">
            <button class="btn btn-light btn--sm" type="button" data-native-share data-share-url="<?= e($shareUrl) ?>" data-share-title="<?= e($vendor['business_name'] ?? 'Apprumo') ?>" data-share-text="Conheça meu perfil e agende online">📤 Compartilhar</button>
            <a class="btn btn-light btn--sm" href="<?= e($shareUrl) ?>" target="_blank" rel="noopener">🌐 Ver perfil</a>
            <?php if ($canSwitchVendor): ?>
                <a class="btn btn-light btn--sm" href="<?= base_url('select-vendor') ?>">🔄 Trocar</a>
            <?php endif; ?>
            <a class="btn btn-light btn--sm" href="<?= base_url('vendor/settings') ?>" aria-label="Configurações">
                <svg viewBox="0 0 24 24" fill="none" width="16" height="16" style="vertical-align:middle"><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.8"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
            </a>
            <a class="topbar-avatar" href="<?= base_url('vendor/settings') ?>" title="<?= e($vendor['business_name'] ?? 'Perfil') ?>">
                <?php if (!empty($vendor['profile_image'])): ?>
                    <img class="topbar-avatar__img" src="<?= e(asset(ltrim($vendor['profile_image'], '/'))) ?>" alt="<?= e($vendor['business_name'] ?? '') ?>" loading="lazy" decoding="async">
                <?php else: ?>
                    <span class="topbar-avatar__initials"><?= e(vendor_initials($vendor['business_name'] ?? '')) ?></span>
                <?php endif; ?>
                <span class="topbar-avatar__status" aria-label="Online"></span>
            </a>
            <form method="post" action="<?= base_url('auth/logout') ?>">
                <?= csrf_field() ?>
                <button class="btn btn-secondary btn--sm" type="submit">Sair</button>
            </form>
        </div>

        <div class="topbar-right-group">
            <a class="topbar-avatar topbar-avatar--mobile" href="<?= base_url('vendor/settings') ?>" title="<?= e($vendor['business_name'] ?? 'Perfil') ?>">
                <?php if (!empty($vendor['profile_image'])): ?>
                    <img class="topbar-avatar__img" src="<?= e(asset(ltrim($vendor['profile_image'], '/'))) ?>" alt="<?= e($vendor['business_name'] ?? '') ?>" loading="lazy" decoding="async">
                <?php else: ?>
                    <span class="topbar-avatar__initials"><?= e(vendor_initials($vendor['business_name'] ?? '')) ?></span>
                <?php endif; ?>
                <span class="topbar-avatar__status" aria-label="Online"></span>
            </a>
            <button class="topbar-menu-toggle" type="button" data-menu-toggle aria-expanded="false" aria-controls="vendor-menu-sheet">
                <span></span><span></span><span></span>
                <span class="sr-only">Abrir ações rápidas</span>
            </button>
        </div>
    </div>

    <div class="topbar-mobile-panel" id="vendor-menu-sheet" data-menu-panel hidden>
        <div class="quick-actions-grid">
            <button class="quick-action-btn" type="button" data-native-share data-share-url="<?= e($shareUrl) ?>" data-share-title="<?= e($vendor['business_name'] ?? 'Apprumo') ?>" data-share-text="Conheça meu perfil e agende online">
                <svg viewBox="0 0 24 24" fill="none" width="22" height="22"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><polyline points="16 6 12 2 8 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><line x1="12" y1="2" x2="12" y2="15" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                <span>Compartilhar</span>
            </button>
            <button class="quick-action-btn" type="button" data-copy-url="<?= e($shareUrl) ?>">
                <svg viewBox="0 0 24 24" fill="none" width="22" height="22"><rect x="9" y="9" width="13" height="13" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                <span>Copiar link</span>
            </button>
            <a class="quick-action-btn" href="<?= e($whatsappShareLink) ?>" target="_blank" rel="noopener">
                <svg viewBox="0 0 24 24" fill="none" width="22" height="22"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z" fill="currentColor"/><path d="M12 2C6.477 2 2 6.477 2 12c0 1.89.525 3.66 1.438 5.168L2 22l4.832-1.438A9.955 9.955 0 0 0 12 22c5.523 0 10-4.477 10-10S17.523 2 12 2z" stroke="currentColor" stroke-width="1.5"/></svg>
                <span>Via WhatsApp</span>
            </a>
            <a class="quick-action-btn" href="<?= e($shareUrl) ?>" target="_blank" rel="noopener">
                <svg viewBox="0 0 24 24" fill="none" width="22" height="22"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.8"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" stroke="currentColor" stroke-width="1.8"/></svg>
                <span>Ver perfil</span>
            </a>
        </div>
        <div class="quick-actions-links">
            <a class="btn btn-light btn-block" href="<?= base_url('vendor/settings') ?>">⚙️ Configurações</a>
            <?php if ($canSwitchVendor): ?>
                <a class="btn btn-light btn-block" href="<?= base_url('select-vendor') ?>">🔄 Trocar negócio</a>
            <?php endif; ?>
            <form method="post" action="<?= base_url('auth/logout') ?>">
                <?= csrf_field() ?>
                <button class="btn btn-secondary btn-block" type="submit">🚪 Encerrar sessão</button>
            </form>
        </div>
    </div>
</header>

<nav class="bottom-nav" aria-label="Navegação principal">
    <div class="bottom-nav__track">
        <a class="bottom-nav__link <?= path_starts_with('/vendor/dashboard') ? 'is-active' : '' ?>" href="<?= base_url('vendor/dashboard') ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1v-9.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
            <span>Início</span>
        </a>
        <a class="bottom-nav__link <?= path_starts_with('/vendor/agenda') || path_starts_with('/vendor/advanced-agenda') ? 'is-active' : '' ?>" href="<?= base_url('vendor/agenda') ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="3" stroke="currentColor" stroke-width="1.8"/><path d="M8 3v4M16 3v4M3 10h18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            <span>Agenda</span>
        </a>
        <a class="bottom-nav__link <?= path_starts_with('/vendor/services') || path_starts_with('/vendor/products') ? 'is-active' : '' ?>" href="<?= base_url('vendor/services') ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><circle cx="18" cy="17" r="2" fill="currentColor"/></svg>
            <span>Catálogo</span>
        </a>
        <a class="bottom-nav__link <?= path_starts_with('/vendor/finance') || path_starts_with('/vendor/reports') ? 'is-active' : '' ?>" href="<?= base_url('vendor/finance') ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3v18M16.5 7.5c0-1.933-2.015-3.5-4.5-3.5s-4.5 1.567-4.5 3.5 2.015 3.5 4.5 3.5 4.5 1.567 4.5 3.5-2.015 3.5-4.5 3.5-4.5-1.567-4.5-3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            <span>Finanças</span>
        </a>
        <a class="bottom-nav__link <?= path_starts_with('/vendor/menu') || path_starts_with('/vendor/clients') || path_starts_with('/vendor/settings') || path_starts_with('/vendor/professionals') ? 'is-active' : '' ?>" href="<?= base_url('vendor/menu') ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="5" r="1.5" fill="currentColor"/><circle cx="12" cy="12" r="1.5" fill="currentColor"/><circle cx="12" cy="19" r="1.5" fill="currentColor"/></svg>
            <span>Mais</span>
        </a>
    </div>
</nav>

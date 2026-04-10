<nav class="bottom-nav" aria-label="Navegação principal">
    <div class="bottom-nav__track">
        <a class="bottom-nav__link <?= path_starts_with('/vendor/dashboard') ? 'is-active' : '' ?>" href="<?= base_url('vendor/dashboard') ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1v-9.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
            <span>Início</span>
        </a>
        <a class="bottom-nav__link <?= path_starts_with('/vendor/agenda') ? 'is-active' : '' ?>" href="<?= base_url('vendor/agenda') ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="3" stroke="currentColor" stroke-width="1.8"/><path d="M8 3v4M16 3v4M3 10h18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            <span>Agenda</span>
        </a>
        <a class="bottom-nav__link <?= path_starts_with('/vendor/finance') ? 'is-active' : '' ?>" href="<?= base_url('vendor/finance') ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3v18M16.5 7.5c0-1.933-2.015-3.5-4.5-3.5s-4.5 1.567-4.5 3.5 2.015 3.5 4.5 3.5 4.5 1.567 4.5 3.5-2.015 3.5-4.5 3.5-4.5-1.567-4.5-3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            <span>Finanças</span>
        </a>
        <a class="bottom-nav__link <?= path_starts_with('/vendor/services') ? 'is-active' : '' ?>" href="<?= base_url('vendor/services') ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><circle cx="18" cy="17" r="2" fill="currentColor"/></svg>
            <span>Serviços</span>
        </a>
        <a class="bottom-nav__link <?= path_starts_with('/vendor/products') ? 'is-active' : '' ?>" href="<?= base_url('vendor/products') ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 8.5 12 4l7 4.5v7L12 20l-7-4.5v-7Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M5 8.5 12 13l7-4.5" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
            <span>Produtos</span>
        </a>
        <a class="bottom-nav__link <?= path_starts_with('/vendor/menu') || path_starts_with('/vendor/reports') || path_starts_with('/vendor/clients') || path_starts_with('/vendor/settings') ? 'is-active' : '' ?>" href="<?= base_url('vendor/menu') ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.8"/><path d="M12 5v2M12 17v2M5 12h2M17 12h2M7.05 7.05l1.42 1.42M15.54 15.54l1.41 1.41M7.05 16.95l1.42-1.41M15.54 8.46l1.41-1.41" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
            <span>Mais</span>
        </a>
    </div>
</nav>

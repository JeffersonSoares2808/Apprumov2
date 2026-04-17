<nav class="bottom-nav" aria-label="Navegação principal">
    <div class="bottom-nav__track">
        <a class="bottom-nav__link <?= path_starts_with('/vendor/dashboard') ? 'is-active' : '' ?>" href="<?= base_url('vendor/dashboard') ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1v-9.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
            <span>Início</span>
            <span class="bottom-nav__active-indicator" aria-hidden="true"></span>
        </a>
        <a class="bottom-nav__link <?= path_starts_with('/vendor/agenda') || path_starts_with('/vendor/advanced-agenda') ? 'is-active' : '' ?>" href="<?= base_url('vendor/agenda') ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="3" stroke="currentColor" stroke-width="1.8"/><path d="M8 3v4M16 3v4M3 10h18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            <span>Agenda</span>
            <span class="bottom-nav__active-indicator" aria-hidden="true"></span>
        </a>
        <a class="bottom-nav__fab-link" href="#ai-assistant" data-ai-open onclick="event.preventDefault();document.getElementById('ai-toggle')?.click();" aria-label="Abrir assistente IA" title="Assistente IA">
            <span class="bottom-nav__fab" aria-hidden="true">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 4c-4.418 0-8 2.91-8 6.5 0 2.248 1.406 4.228 3.548 5.399L7 20l3.196-2.029c.89.188 1.838.289 2.804.289 4.418 0 8-2.91 8-6.5S16.418 4 12 4Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M9 11h.01M12 11h.01M15 11h.01" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"/></svg>
            </span>
            <span class="bottom-nav__fab-label">IA</span>
        </a>
        <a class="bottom-nav__link <?= path_starts_with('/vendor/services') || path_starts_with('/vendor/products') ? 'is-active' : '' ?>" href="<?= base_url('vendor/services') ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><circle cx="18" cy="17" r="2" fill="currentColor"/></svg>
            <span>Catálogo</span>
            <span class="bottom-nav__active-indicator" aria-hidden="true"></span>
        </a>
        <a class="bottom-nav__link <?= path_starts_with('/vendor/finance') || path_starts_with('/vendor/reports') ? 'is-active' : '' ?>" href="<?= base_url('vendor/finance') ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3v18M16.5 7.5c0-1.933-2.015-3.5-4.5-3.5s-4.5 1.567-4.5 3.5 2.015 3.5 4.5 3.5 4.5 1.567 4.5 3.5-2.015 3.5-4.5 3.5-4.5-1.567-4.5-3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            <span>Finanças</span>
            <span class="bottom-nav__active-indicator" aria-hidden="true"></span>
        </a>
        <a class="bottom-nav__link <?= path_starts_with('/vendor/menu') || path_starts_with('/vendor/clients') || path_starts_with('/vendor/settings') || path_starts_with('/vendor/professionals') ? 'is-active' : '' ?>" href="<?= base_url('vendor/menu') ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="5" r="1.5" fill="currentColor"/><circle cx="12" cy="12" r="1.5" fill="currentColor"/><circle cx="12" cy="19" r="1.5" fill="currentColor"/></svg>
            <span>Mais</span>
            <span class="bottom-nav__active-indicator" aria-hidden="true"></span>
        </a>
    </div>
</nav>

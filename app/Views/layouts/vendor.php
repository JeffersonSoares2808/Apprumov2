<!doctype html>
<html lang="pt-BR">
<head>
    <?php partial('partials/head', ['title' => $title ?? 'Apprumo']); ?>
</head>
<body class="app-body app-body--vendor">
    <div class="app-shell app-shell--premium">
        <div class="container">
            <?php partial('partials/topbar', ['vendor' => $vendor, 'title' => $title ?? 'Painel']); ?>
            <?php partial('partials/flash'); ?>
            <main class="page-flow" id="conteudo-principal">
                <?= $content ?>
            </main>
            <?php partial('partials/bottom-nav'); ?>
            <?php partial('partials/ai-assistant'); ?>

            <!-- PWA Install Banner -->
            <div class="pwa-install-banner" id="pwa-install-banner">
                <div class="pwa-install-banner__icon">
                    <img src="<?= asset('assets/img/icon-192x192.png') ?>" alt="Apprumo" width="28" height="28" loading="lazy">
                </div>
                <div class="pwa-install-banner__content">
                    <div class="pwa-install-banner__title">Instalar Apprumo</div>
                    <div class="pwa-install-banner__desc">Acesse rápido como um app</div>
                </div>
                <div class="pwa-install-banner__actions">
                    <button class="pwa-install-banner__btn pwa-install-banner__btn--install" id="pwa-install-btn" type="button">Instalar</button>
                    <button class="pwa-install-banner__btn pwa-install-banner__btn--dismiss" id="pwa-dismiss-btn" type="button">×</button>
                </div>
            </div>

            <p class="footer-note footer-note--spaced">Desenvolvido por JS Sistemas Inteligentes</p>
        </div>
    </div>
    <script src="<?= asset('assets/js/app.js') ?>"></script>
</body>
</html>

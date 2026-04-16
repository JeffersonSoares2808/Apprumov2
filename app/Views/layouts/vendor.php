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
            <p class="footer-note footer-note--spaced">Desenvolvido por JS Sistemas Inteligentes</p>
        </div>
    </div>
    <script src="<?= asset('assets/js/app.js') ?>"></script>
</body>
</html>

<!doctype html>
<html lang="pt-BR">
<head>
    <?php partial('partials/head', ['title' => $title ?? 'Apprumo']); ?>
</head>
<body class="app-body app-body--public">
    <div class="app-shell">
        <div class="container">
            <?php partial('partials/flash'); ?>
            <?= $content ?>
            <p class="footer-note">Desenvolvido por JS Sistemas Inteligentes</p>
        </div>
    </div>
    <script src="<?= asset('assets/js/app.js') ?>"></script>
</body>
</html>

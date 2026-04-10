<!doctype html>
<html lang="pt-BR">
<head>
    <?php partial('partials/head', ['title' => $title ?? 'Apprumo']); ?>
</head>
<body class="app-body app-body--auth-v2">
    <?php partial('partials/flash'); ?>
    <div class="auth-content-wrap">
        <?= $content ?>
    </div>
    <p class="auth-footer-note">Desenvolvido por JS Sistemas Inteligentes</p>
    <script src="<?= asset('assets/js/app.js') ?>"></script>
</body>
</html>

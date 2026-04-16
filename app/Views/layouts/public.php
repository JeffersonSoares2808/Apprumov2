<!doctype html>
<html lang="pt-BR">
<head>
    <?php partial('partials/head', ['title' => $title ?? 'Apprumo']); ?>
</head>
<body class="app-body app-body--public">
    <div class="app-shell app-shell--public">
        <?php partial('partials/flash'); ?>
        <?= $content ?>
    </div>
    <script src="<?= asset('assets/js/app.js') ?>"></script>
</body>
</html>
